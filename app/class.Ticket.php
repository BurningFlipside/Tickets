<?php
require_once('Autoload.php');
require_once('app/TicketAutoload.php');

class Ticket extends SerializableObject
{
    private static $data_set = false;
    private static $data_tables = array();

    protected static function getDataSet()
    {
        if(static::$data_set === false)
        {
            static::$data_set = DataSetFactory::get_data_set('tickets');
        }
        return static::$data_set;
    }

    protected static function getDataTableByName($name)
    {
        if(!isset(static::$data_tables[$name]))
        {
            $ticket_data_set = self::getDataSet();
            static::$data_tables[$name] = $ticket_data_set[$name];
        }
        return static::$data_tables[$name];
    }

    protected static function get_data_table()
    {
        return \Tickets\DB\TicketsDataTable::getInstance();
    }

    protected static function get_history_data_table()
    {
        return static::getDataTableByName('TicketsHistory');
    }

    function generate_hash($datatable=false)
    {
        do
        {
            $this->rand = mt_rand();
            $this->hash = hash('haval128,5', $this->year.$this->firstName.$this->lastName.$this->uid.$this->type.$this->previous_hash.$this->rand);
        } while($datatable !== false && self::hash_exists($this->hash, $datatable));
    }

    function insert_to_db($datatable=false)
    {
        if($datatable === false)
        {
            $datatable = self::get_data_table();
        }
        if($this->hash === false || $this->hash === null)
        {
            $this->generate_hash($datatable);
        }
        else
        {
            $this->previous_hash = $this->hash;
            $this->generate_hash($datatable);
        }
        $res = $datatable->create($this->jsonSerialize());
        if($res !== false && ($this->previous_hash !== false && $this->previous_hash !== null))
        {
            $filter = new \Tickets\DB\TicketHashFilter($this->previous_hash);
            $datatable->raw_query('INSERT INTO tblTicketsHistory SELECT * FROM tblTickets WHERE '.$filter->to_sql_string());
            $datatable->delete($filter);
        }
        return $res;
    }

    function replace_in_db($datatable=false)
    {
        if($datatable === false)
        {
            $datatable = self::get_data_table();
        }
        return $datatable->update($this->jsonSerialize());
    }

    function send_email($email = FALSE, $attachment = TRUE, $message = FALSE)
    {
        if($email == FALSE)
        {
            $email = $this->email;
        }
        $mail = new TicketEmail($this, $email, $attachment);
        if($message)
        {
            $mail->set_private_message($message);
        }
        $ret = $mail->send_HTML();
        return $ret;
    }

    function queue_email($mesage = FALSE)
    {
        $mail = new TicketEmail($this, $this->email, TRUE);
        if($message)
        {
            $mail->set_private_message($message);
        }
        return $mail->queue_email();
    }

    function has_previous()
    {
        return ($this->previous_hash !== false && $this->previous_hash !== null && strlen($this->previous_hash)>0);
    }

    function get_previous()
    {
        $history_table = self::get_history_data_table();
        $filter = new \Tickets\DB\TicketHashFilter($this->previous_hash);
        $ticket_data = $history_table->read($filter);
        if($ticket_data === false)
        {
            return false;
        }
        return new Ticket($ticket_data[0]);
    }

    function sell_to($email, $send_now = TRUE, $message = FALSE, $db = FALSE)
    {
        $this->email = $email;
        $this->sold  = 1;
        $this->discretionary = 0;
        if($this->insert_to_db($db) === FALSE)
        {
            return FALSE;
        }
        $res = FALSE;
        if($send_now)
        {
            $res = $this->send_email($this->email, TRUE, $message);
        }
        else
        {
            $res = $this->queue_email($message);
        }
        return $res;
    }

    static function get_tickets($filter=false, $select=false)
    {
        $ticket_data_table = self::get_data_table();
        $tickets = $ticket_data_table->read($filter, $select);
        if($tickets === false)
        {
            return false;
        }
        else if(!is_array($tickets))
        {
            $tickets = array($tickets);
        }
        $count = count($tickets);
        for($i = 0; $i < $count; $i++)
        {
            $tickets[$i] = new Ticket($tickets[$i]);
        }
        return $tickets;
    }

    static function get_ticket_by_hash($hash, $select=false)
    {
        $ticket_data_table = self::get_data_table();
        $filter = new \Tickets\DB\TicketHashFilter($hash);
        $tickets = $ticket_data_table->read($filter, $select);
        if($tickets == false)
        {
            return false;
        }
        else if(!is_array($tickets))
        {
            return false;
        }
        else if(!isset($tickets[0]))
        {
            return new Ticket($tickets);
        }
        return new Ticket($tickets[0]);
    }

    static function get_tickets_for_user($user, $filter=false, $select=false)
    {
        $user_filter = new \Data\Filter('email eq \''.$user->getEmail().'\'');
        if($filter === false)
        {
            $filter = $user_filter;
        }
        else
        {
            $filter->add($user_filter);
        }
        return self::get_tickets($filter, $select);
    }

    static function find_current_from_old_hash($hash)
    {
        $filter = new \Data\Filter("hash eq '$hash' or previous_hash eq '$hash'");
        $current = self::get_tickets($filter);
        if($current === false)
        {
            $filter = new \Data\Filter("previous_hash eq '$hash'");
            $history_table = self::get_history_data_table();
            $ticket_data = $history_table->search($filter);
            if($ticket_data === false)
            {
                return false;
            }
            $current = self::find_current_from_old_hash($ticket_data[0]['hash']);
            return $current;
        }
        return $current[0];
    }

    static function create_new($count, $type='', $db=FALSE, $flush = TRUE)
    {
        if($db == FALSE)
        {
            $db = new FlipsideTicketDB();
        }
        $res = array();
        for($i = 0; $i < $count; $i++)
        {
            $ticket = new Ticket();
            $ticket->year = TicketSystemSettings::getInstance()['year'];
            $ticket->type = $type;
            if($flush)
            {
                $ticket->insert_to_db($db);
            }
            array_push($res, $ticket);
        }
        return $res;
    }

    static function hash_exists($hash, $data_table)
    {
        $filter = new \Tickets\DB\TicketHashFilter($hash);
        $tickets = $data_table->read($filter, array('hash'));
        if($tickets === false || !isset($tickets[0]) || !isset($tickets[0]['hash']))
        {
            return false;
        }
        return true;
    }

    static function get_tickets_for_user_and_pool($user, $criteria = FALSE)
    {
        $groups = $user->getGroups();
        $count = count($groups);
        for($i = 0; $i < $count; $i++)
        {
            $groups[$i] = '\''.$groups[$i]->cn[0].'\'';
        }
        $groups = implode(',', $groups);
        $db = new FlipsideTicketDB();
        $conds = array('group_name' => ' IN ('.$groups.')');
        $pools = $db->select('tblPoolMap', 'pool_id', $conds);
        $count = count($pools);
        for($i = 0; $i < $count; $i++)
        {
            $pools[$i] = $pools[$i]['pool_id'];
        }
        $pools = implode(',', $pools);
        $conds = array('pool_id' => ' IN ('.$pools.')');
        if($criteria != FALSE)
        {
            $conds = array_merge($conds, $criteria);
        }
        $res = self::select_from_db_multi_conditions($db, $conds);
        if($res === FALSE)
        {
            return FALSE;
        }
        else if(!is_array($res))
        {
            $res = array($res);
        }
        return $res;
    }

    static function get_ticket_history_by_hash($hash)
    {
        $current = self::find_current_from_old_hash($hash);
        if($current === false)
        {
            return false;
        }
        $res = new SerializableObject();
        $res->current = $current;
        $res->history = array();
        $ticket = $current;
        while($ticket->has_previous())
        {
            $ticket = $ticket->get_previous();
            array_push($res->history, $ticket);
        }
        if(strcasecmp($current->hash,$hash) === 0)
        {
            $res->selected = -1;
        }
        else
        {
            $count = count($res->history);
            for($i = 0; $i < $count; $i++)
            {
                if(strcasecmp($res->history[$i]->hash,$hash) === 0)
                {
                    $res->selected = $i;
                    break;
                }
            }
        }
        return $res;
    }

    static function get_by_short_code($hash)
    {
        $db = new FlipsideTicketDB();
        $res = self::select_from_db_multi_conditions($db, array('hash' => ' LIKE \''.$hash.'%\''));
        if($res === FALSE)
        {
            return FALSE;
        }
        else if(!is_array($res))
        {
            $res = array($res);
        }
        return $res;
    }

    static function getAll($year = FALSE)
    {
        $db = new FlipsideTicketDB();
        if($year === FALSE)
        {
            $year = $db->getVariable('year');
        }
        $type = self::select_from_db($db, 'year', $year);
        if($type == FALSE)
        {
            return FALSE;
        }
        if(!is_array($type))
        {
            $type = array($type);
        }
        return $type;
    }

    static function hash_to_words($hash)
    {
        require("static.words.php");
        $res = substr($hash, 0, 8);
        $remainder = gmp_init(substr($hash, 8), 16);
        $my_words = '';
        while(gmp_intval($remainder) > 0)
        {
            $pos = gmp_mod($remainder, 4096);
            $my_words = $words[gmp_intval($pos)].' '.$my_words;
            $remainder = gmp_div($remainder,4096);
        }
        return trim($res.' '.$my_words);
    }

    static function words_to_hash($my_words)
    {
        require_once("static.words.php");
        $res = strtok($my_words, ' ');
        $token = strtok(' ');
        while($token !== FALSE)
        {
            $pos = array_search($token, $words);
            $pos = dechex($pos);
            if(strlen($pos) < 1)
            {
                $pos = '000';
            }
            if(strlen($pos) < 2)
            {
                $pos = '00'.$pos;
            }
            else if(strlen($pos) < 3)
            {
                $pos = '0'.$pos;
            }
            $res .= $pos;
            $token = strtok(' ');
        }
        return $res;
    }

    static function test_ticket()
    {
         $type = new static();
         $type->year           = TicketSystemSettings::getInstance()['year'];
         $type->firstName      = 'Test';
         $type->lastName       = 'User';
         $type->email          = 'test@test.org';
         $type->request_id     = '000000';
         $type->assigned       = TRUE;
         $type->void           = FALSE;
         $type->sold           = TRUE;
         $type->used           = FALSE;
         $type->discretionary  = FALSE;
         $type->type           = 'A';
         $type->guardian_first = null;
         $type->guardian_last  = null;
         $type->pool_id        = -1;
         $type->generate_hash();

         return $type;
    }

    static function user_has_ticket($hash, $user)
    {
        $tickets = Ticket::get_tickets_for_user($user);
        if($tickets == FALSE)
        {
            return FALSE;
        }
        for($i = 0; $i < count($tickets); $i++)
        {
            if($tickets[$i]->hash == $hash)
            {
                return TRUE;
            }
        }
        return FALSE;
    }

    static function do_sale($user, $email, $types, $message = FALSE)
    {
        $db = new FlipsideTicketDB();
        foreach($types as $type=>$qty)
        {
            $tickets = self::get_tickets_for_user_and_pool($user, array('sold'=>'=0', 'type'=>'=\''.$type.'\''));
            $sold = 0;
            $count = count($tickets);
            for($i = 0; $sold < $qty; $i++)
            {
                if($i > $count) return FALSE;
                if($tickets[$i]->sell_to($email, TRUE, $message, $db))
                {
                    $sold++;
                }
            }
        }
        return TRUE;
    }

    static function searchForTickets($type, $value, $include_history=FALSE)
    {
        $cond = array();
        switch($type)
        {
            default:
            case '*':
                $cond['hash'] = ' LIKE \'%'.$value.'%\'';
                $cond['firstName'] = ' LIKE \''.$value.'\'';
                $cond['email'] = ' LIKE \''.$value.'\'';
                $cond['lastName'] = ' LIKE \''.$value.'\'';
                break;
            case 'hash':
                $cond['hash'] = ' LIKE \'%'.$value.'%\'';
                break;
            case 'email':
                $cond['email'] = ' LIKE \''.$value.'\'';
                break;
            case 'first':
                $cond['firstName'] = ' LIKE \''.$value.'\'';
                break;
            case 'last':
                $cond['lastName'] = ' LIKE \''.$value.'\'';
                break;
        }
        $db = new FlipsideTicketDB();
        $type = self::select_from_db_multi_conditions($db, $cond, 'OR');
        if($type === false && $include_history === true)
        {
            $ticket_data = $db->select('tblTicketsHistory', '*', $cond, 'OR');
            if($ticket_data === false) return false;
            $type = array('history'=>1);
            $count = count($ticket_data);
            for($i = 0; $i < $count; $i++)
            {
                $ticket = new static();
                $ticket->set_object_vars($ticket_data[$i]);
                array_push($type, $ticket);
            }
            return $type;
        }
        return $type;
    }

}
?>
