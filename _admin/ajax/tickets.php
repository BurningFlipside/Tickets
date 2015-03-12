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

function get_ticket_of_correct_type($type, $year, $db)
{
    $new_tickets = Ticket::select_from_db_multi_conditions($db, 
        array('sold'=>'=0', 'discretionary'=>'=0', 'pool_id'=>'=-1', 'year'=>'='.$year, 'type'=>"='$type'"));
    if(is_array($new_tickets))
    {
        return $new_tickets[0];
    }
    return $new_tickets;
}

function auto_pop_tickets($db, $limit)
{
    $year = $db->getVariable('year');
    $requests = FlipsideTicketRequest::select_from_db_multi_conditions($db, array('private_status'=>'=1', 'year'=>'='.$year));
    $count = count($requests);
    if($limit != false && $limit < $count)
    {
        $count = $limit;
    }
    $passed = 0;
    $failed = 0;
    for($i = 0; $i < $count; $i++)
    {
        $fail = false;
        $requested_tickets = $requests[$i]->tickets;
        foreach($requested_tickets as $requested_ticket)
        {
            $type = $requested_ticket->type->typeCode;
            $new_ticket = get_ticket_of_correct_type($type, $year, $db);
            if($new_ticket === false)
            {
                //TODO Backout any changes to the DB
                error_log($requests[$i]->request_id.': Failed to get ticket of correct type '.$type);
                $fail = true;
                break;
            }
            $new_ticket->firstName  = $requested_ticket->first;
            $new_ticket->lastName   = $requested_ticket->last;
            $new_ticket->request_id = $requested_ticket->request_id;
            $new_ticket->assigned   = 1;
            $new_ticket->sold       = 1;
            $new_ticket->email      = $requests[$i]->mail;
            if($requested_ticket->type->is_minor)
            {
                $new_ticket->guardian_first = $requests[$i]->givenName;
                $new_ticket->guardian_last  = $requests[$i]->sn;
            }
            if($new_ticket->replace_in_db($db) === false)
            {
                //TODO Backout any changes to the DB
                error_log($requests[$i]->request_id.': Failed to update ticket in DB');
                $fail = true;
                break;
            }
            if($new_ticket->queue_email() === false)
            {
                //TODO Backout any changes to the DB
                error_log($requests[$i]->request_id.': Failed to queue email');
                $fail = true;
                break;
            }
        }
        if($fail)
        {
            $failed++;
        }
        else
        {
            $requests[$i]->private_status = 6;
            $requests[$i]->status         = 6;
            if($requests[$i]->replace_in_db($db) === false)
            {
                 //TODO Backout any changes to the DB
                 error_log($requests[$i]->request_id.': Failed to update request in DB');
                 $failed++;
            }
            else
            {
                $passed++;
            }
        }
    }
    return array('passed'=>$passed, 'failed'=>$failed);
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

    function post_populate($params)
    {
        $db = new FlipsideTicketDB();
        $limit = false;
        if(isset($params['limit']))
        {
            $limit = $params['limit'];
        }
        return auto_pop_tickets($db, $limit);
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
                case 'populate':
                    unset($params['action']);
                    $res = $this->post_populate($params);
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
