<?php
require_once("class.FlipSession.php");
require_once("class.FlipsideTicketDB.php");
require_once("class.Ticket.php");
require_once("class.FlipJax.php");
class TicketsAjax extends FlipJaxSecure
{
    function get_sold_ticket_count()
    {
        $db = new FlipsideTicketDB();
        $sold = $db->getTicketSoldCount();
        $unsold = $db->getTicketUnsoldCount();
        if($sold === FALSE)
        {
            return array('err_code' => self::INTERNAL_ERROR, 'reason' => "Failed to obtain sold ticket count!");
        }
        else if($unsold === FALSE)
        {
            return array('err_code' => self::INTERNAL_ERROR, 'reason' => "Failed to obtain unsold ticket count!");
        }
        else
        {
            return array('sold' => $sold, 'unsold' => $unsold);
        }
    }

    function get_type_counts($type = 'all')
    {
        $db = new FlipsideTicketDB();
        $counts = $db->getRequestedTickets();
        if($type != 'all')
        {
            $res = array();
            for($i = 0; count($counts); $i++)
            {
                if($counts[$i]['typeCode'] == $type)
                {
                    array_push($res, $counts[$i]);
                }
            }
            $counts = $res;
        }
        return $counts;
    }

    function get_all_tickets()
    {
        return array('data'=>Ticket::getAll());
    }

    function get($params)
    {
        if(!$this->is_logged_in())
        {
            return array('err_code' => self::ACCESS_DENIED, 'reason' => "Not Logged In!");
        }
        if(isset($params['sold']))
        {
            if(!$this->user_in_group("TicketAdmins") && !$this->user_in_group("TicketTeam"))
            {
                return array('err_code' => self::ACCESS_DENIED, 'reason' => "User must be a member of TicketAdmins or TicketTeam!");
            }
            return $this->get_sold_ticket_count();
        }
        else if(isset($params['requested_type']))
        {
            if(!$this->user_in_group("TicketAdmins") && !$this->user_in_group("TicketTeam"))
            {
                return array('err_code' => self::ACCESS_DENIED, 'reason' => "User must be a member of TicketAdmins or TicketTeam!");
            }
            return $this->get_type_counts($params['requested_type']);
        }
        else if(isset($params['hash_to_words']))
        {
            return array('data'=>Ticket::hash_to_words($params['hash_to_words']));
        }
        else if(isset($params['all']))
        {
            if(!$this->user_in_group("TicketAdmins") && !$this->user_in_group("TicketTeam"))
            {
                return array('err_code' => self::ACCESS_DENIED, 'reason' => "User must be a member of TicketAdmins or TicketTeam!");
            }
            return $this->get_all_tickets();
        }
        else
        {
            $data = Ticket::get_tickets_for_user($this->get_user());
            //Even if data is FALSE, return success because no tickets is fine
            return array('data'=>$data);
        }
    }
}

$ajax = new TicketsAjax();
$ajax->run();

/* vim: set tabstop=4 shiftwidth=4 expandtab: */
?>
