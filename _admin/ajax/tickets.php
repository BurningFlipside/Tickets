<?php
require_once("class.FlipsideTicketDB.php");
require_once("class.Ticket.php");
require_once("class.FlipJax.php");

function filter($entry)
{
    return $entry['requested_ticket_id'];
}

function generate_tickets($type, $count, $auto_pop, $db)
{
    $passed = 0;
    $failed = 0;
    $extended = FALSE;
    $new_tickets = Ticket::create_new($count, $type, $db, FALSE);
    if($auto_pop)
    {
        $i = 0;

        $type_class = FlipsideTicketDB::getTicketTypeByType($type);

        $requested_ticket_ids = $db->select('vRequestWTickets', 'requested_ticket_id', array('private_status'=>'=1', 'type'=>"='$type'"));
        $ids = implode(',', array_map(filter, $requested_ticket_ids));
        $requested_tickets = FlipsideTicketRequestTicket::select_from_db_multi_conditions($db, array('requested_ticket_id'=>' IN ('.$ids.')'));
        if($count < count($requested_tickets))
        {
            $extended = "Not enough tickets for all received requests";
        }
        foreach($requested_tickets as $requested_ticket)
        {
            if($i >= $count) break;
            $new_tickets[$i]->firstName  = $requested_ticket->first;
            $new_tickets[$i]->lastName   = $requested_ticket->last;
            $new_tickets[$i]->request_id = $requested_ticket->request_id;
            $new_tickets[$i]->assigned   = 1;
            $request = new FlipsideTicketRequest($requested_ticket->request_id, FALSE);
            if($request !== FALSE)
            {
                $new_tickets[$i]->email   = $request->mail;
                if($type_class->is_minor)
                {
                    $new_tickets[$i]->guardian_first = $request->givenName;
                    $new_tickets[$i]->guardian_last  = $request->sn;
                }
                $new_tickets[$i]->queue_email();
                $request->private_status = 6;
                $request->replace_in_db($db);
            }
            $i++;
        }
    }
    foreach($new_tickets as $ticket)
    {
        if($ticket->insert_to_db($db) !== FALSE)
        {
            $passed++;
        }
        else
        {
            $failed++;
        }
    }
    if($extended === FALSE)
    {
        return array('passed'=>$passed, 'failed'=>$failed);
    }
    else
    {
        return array('passed'=>$passed, 'failed'=>$failed, 'extended'=>$extended);
    }
}

class TicketsAjax extends FlipJaxSecure
{
    function post_generate($params)
    {
        $auto_pop = false;
        if(isset($params['auto_populate']))
        {
            if($params['auto_populate'] == 'on')
            {
                $auto_pop = true;
            }
            unset($params['auto_populate']);
        }
        //The remaining values in $params should be $key =? $count
        $db = new FlipsideTicketDB();
        $res = array('passed'=>0, 'failed'=>0);
        foreach($params as $type => $count)
        {
            if($count == '')
            {
                continue;
            }
            $tmp = generate_tickets($type, $count, $auto_pop, $db);
            $res['passed'] += $tmp['passed'];
            $res['failed'] += $tmp['failed'];
            if(isset($tmp['extended']))
            {
                if(!isset($res['extended']))
                {
                    $res['extended'] = array();
                }
                $res['extended'][$type] = $tmp['extended'];
            }
        }
        return $res;
    }

    function post($params)
    {
        if(!$this->is_logged_in())
        {
            return array('err_code' => self::ACCESS_DENIED, 'reason' => "Not Logged In!");
        }
        if(!$this->user_in_group("TicketAdmins"))
        {
            return array('err_code' => self::ACCESS_DENIED, 'reason' => "User must be a member of TicketAdmins!");
        }
        $res = $this->validate_params($params, array('action'=>'string'));
        if($res == self::SUCCESS)
        {
            switch($params['action'])
            {
                case 'generate':
                    unset($params['action']);
                    $res = $this->post_generate($params);
                    break;
                default:
                    $res = array('err_code' => self::INVALID_PARAM, 'action_name' => $params['action']);
                    break;
            }
        }
        return $res;
    }
}

$ajax = new TicketsAjax();
$ajax->run();
/* vim: set tabstop=4 shiftwidth=4 expandtab: */
?>
