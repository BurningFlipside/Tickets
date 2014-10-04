<?php
require_once("class.FlipSession.php");
require_once("class.FlipsideTicketDB.php");
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
        else
        {
            return array('data'=>array());
        }
    }
}

$ajax = new TicketsAjax();
$ajax->run();

/* vim: set tabstop=4 shiftwidth=4 expandtab: */
?>
