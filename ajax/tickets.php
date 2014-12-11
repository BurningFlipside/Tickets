<?php
require_once("class.FlipSession.php");
require_once("class.FlipsideTicketDB.php");
require_once("class.Ticket.php");
require_once("class.FlipJax.php");
class TicketsAjax extends FlipJaxSecure
{
    function validate_user_can_read_hash($hash)
    {
        if($this->user_in_group("TicketAdmins") || $this->user_in_group("TicketTeam"))
        {
            return self::SUCCESS;
        }
        $tickets = Ticket::get_tickets_for_user($this->get_user());
        if($tickets == FALSE)
        {
            return array('err_code' => self::INTERNAL_ERROR, 'reason' => "Failed to obtain ticket IDs for user!");
        }
        for($i = 0; $i < count($tickets); $i++)
        {
            if($tickets[$i]->hash == $hash)
            {
                return self::SUCCESS;
            }
        }
        return array('err_code' => self::INTERNAL_ERROR, 'reason' => "Unauthorized!");
    }

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

    function get_used_ticket_count()
    {
        $db = new FlipsideTicketDB();
        $used = $db->getTicketUsedCount();
        $unused = $db->getTicketUnusedCount();
        if($used === FALSE)
        {
            return array('err_code' => self::INTERNAL_ERROR, 'reason' => "Failed to obtain used ticket count!");
        }
        else if($unused === FALSE)
        {
            return array('err_code' => self::INTERNAL_ERROR, 'reason' => "Failed to obtain unused ticket count!");
        }
        else
        {
            return array('used' => $used, 'unused' => $unused);
        }
    }

    function get_type_counts($type = 'all', $actual = TRUE)
    {
        $db = new FlipsideTicketDB();
        if($actual)
        {
            $counts = $db->getTickets();
        }
        else
        {
            $counts = $db->getRequestedTickets();
        }
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
        else if(isset($params['used']))
        {
            if(!$this->user_in_group("TicketAdmins") && !$this->user_in_group("TicketTeam"))
            {
                return array('err_code' => self::ACCESS_DENIED, 'reason' => "User must be a member of TicketAdmins or TicketTeam!");
            }
            return $this->get_used_ticket_count();
        }
        else if(isset($params['requested_type']))
        {
            if(!$this->user_in_group("TicketAdmins") && !$this->user_in_group("TicketTeam"))
            {
                return array('err_code' => self::ACCESS_DENIED, 'reason' => "User must be a member of TicketAdmins or TicketTeam!");
            }
            return $this->get_type_counts($params['requested_type'], FALSE);
        }
        else if(isset($params['type']))
        {
            if(!$this->user_in_group("TicketAdmins") && !$this->user_in_group("TicketTeam"))
            {
                return array('err_code' => self::ACCESS_DENIED, 'reason' => "User must be a member of TicketAdmins or TicketTeam!");
            }
            return $this->get_type_counts($params['type'], TRUE);
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

    function post_pdf($hash, $year)
    {
        $res = $this->validate_user_can_read_hash($hash);
        if($res != self::SUCCESS)
        {
            return $res;
        }
        $ticket = Ticket::get_ticket_by_hash($hash); 
        if($ticket == FALSE)
        {
            return array('err_code' => self::INTERNAL_ERROR, 'reason' => "Failed to obtain ticket!");
        }
        else
        {
            $file_name = $ticket[0]->generatePDF();
            if($file_name == FALSE)
            {
                return array('err_code' => self::INTERNAL_ERROR, 'reason' => "Failed to generate PDF!");
            }
            else
            {
                $file_name = substr($file_name, strpos($file_name, 'tmp/'));
                return array('pdf' => $file_name);
            }
        }
    }

    function do_post_ticket_edit($hash, $first, $last)
    {
        $res = $this->validate_user_can_read_hash($hash);
        if($res != self::SUCCESS)
        {
            return $res;
        }
        $ticket = Ticket::get_ticket_by_hash($hash);
        if($ticket == FALSE)
        {
            return array('err_code' => self::INTERNAL_ERROR, 'reason' => "Failed to obtain ticket!");
        }
        $ticket[0]->firstName = $first;
        $ticket[0]->lastName  = $last;
        if($ticket[0]->insert_to_db() === FALSE)
        {
            return array('err_code' => self::INTERNAL_ERROR, 'reason' => "Failed to update ticket!");
        }
        return self::SUCCESS;
    }

    function post_transfer($hash, $email)
    {
        $res = $this->validate_user_can_read_hash($hash);
        if($res != self::SUCCESS)
        {
            return $res;
        }
        $ticket = Ticket::get_ticket_by_hash($hash);
        if($ticket == FALSE)
        {
            return array('err_code' => self::INTERNAL_ERROR, 'reason' => "Failed to obtain ticket!");
        }
        if($ticket[0]->send_email($email, FALSE) === FALSE)
        {
            return array('err_code' => self::INTERNAL_ERROR, 'reason' => "Failed to send email about ticket!");
        }
        return self::SUCCESS;
    }

    function post_claim($hash, $first, $last)
    {
        $ticket = Ticket::get_ticket_by_hash($hash);
        if($ticket == FALSE)
        {
            return array('err_code' => self::INTERNAL_ERROR, 'reason' => "Failed to obtain ticket!");
        }
        $user = $this->get_user();
        if($user == FALSE)
        {
            return array('err_code' => self::ACCESS_DENIED, 'reason' => "Not Logged In!");
        }
        $ticket[0]->firstName = $first;
        $ticket[0]->lastName  = $last;
        $ticket[0]->email     = $user->mail[0];
        if($ticket[0]->insert_to_db() === FALSE)
        {
            return array('err_code' => self::INTERNAL_ERROR, 'reason' => "Failed to update ticket!");
        }
        return self::SUCCESS;
    }

    function post_verify($hash)
    {
        $ticket = Ticket::get_by_short_code($hash);
        if($ticket === FALSE)
        {
            return array('verified'=>0);
        }
        else
        {
            return array('verified'=>1);
        }
    }

    function post($params)
    {
        if(!$this->is_logged_in())
        {
            return array('err_code' => self::ACCESS_DENIED, 'reason' => "Not Logged In!");
        }
        else if(isset($params['pdf']))
        {
            $res = $this->validate_params($params, array('hash'=>'string', 'year'=>'int'));
            if($res == self::SUCCESS)
            {
                $res = $this->post_pdf($params['hash'], $params['year']);
            }
            return $res; 
        }
        else if(isset($params['transfer']))
        {
            $res = $this->validate_params($params, array('hash'=>'string', 'email'=>'string'));
            if($res == self::SUCCESS)
            {
                $res = $this->post_transfer($params['hash'], $params['email']);
            }
            return $res;
        }
        else if(isset($params['claim']))
        {
            $res = $this->validate_params($params, array('hash'=>'string', 'first'=>'string', 'last'=>'string'));
            if($res == self::SUCCESS)
            {
                $res = $this->post_claim($params['hash'], $params['first'], $params['last']);
            }
            return $res;
        }
        else if(isset($params['verify_id']))
        {
            $res = $this->validate_params($params, array('verify_id'=>'string'));
            if($res == self::SUCCESS)
            {
                $res = $this->post_verify($params['verify_id']);
            }
            return $res;
        }
        else
        {
            $res = $this->validate_params($params, array('hash'=>'string','first'=>'string','last'=>'string'));
            if($res == self::SUCCESS)
            {
                $res = $this->do_post_ticket_edit($params['hash'], $params['first'], $params['last']);
            }
            return $res;    
        }
    }
}

$ajax = new TicketsAjax();
$ajax->run();

/* vim: set tabstop=4 shiftwidth=4 expandtab: */
?>
