<?php
require_once('class.FlipsideTicketType.php');
class FlipsideTicketRequestTicket extends FlipsideDBObject
{
    protected $_tbl_name = 'tblRequestedTickets';
    protected $_sql_special = array('type' => 'FlipsideTicketRequestTicket::type_to_type_code');
    protected $_sql_ai_key = array('requested_ticket_id');

    private static $ticket_types = array();

    public $requested_ticket_id = null;
    public $request_id;
    public $year;
    public $first;
    public $last;
    public $type;
    public $assigned_id;
    public $test;

    static function populate_children($db, &$type)
    {
        if(isset(self::$ticket_types[$type->type]))
        {
            $type->type = self::$ticket_types[$type->type];
        }
        else
        {
            $res = FlipsideTicketType::select_from_db($db, 'typeCode', $type->type);
            self::$ticket_types[$type->type] = $res;
            $type->type = $res;
        }
    }

    static function select_from_db($db, $col, $value)
    {
        $type = parent::select_from_db($db, $col, $value);
        if($type == FALSE)
        {
            return FALSE;
        }
        if(is_array($type))
        {
            for($i = 0; $i < count($type); $i++)
            {
                self::populate_children($db, $type[$i]);
            }
        }
        else
        {
            self::populate_children($db, $type);
        }
        return $type;
    }

    static function select_from_db_multi_conditions($db, $conds)
    {
        $type = parent::select_from_db_multi_conditions($db, $conds);
        if(is_array($type))
        {
            for($i = 0; $i < count($type); $i++)
            {
                self::populate_children($db, $type[$i]);
            }
        }
        else
        {
            self::populate_children($db, $type);
        }
        return $type;
    }

    static function test_ticket($request_id, $year, $num)
    {
        $type = new static();
        $type->request_id = $request_id;
        $type->year       = $year;
        $type->first      = 'Test '.$num;
        $type->last       = 'User';
        $type->type       = new FlipsideTicketType('A');
        return $type;
    }

    protected function set_in_db($db, $op)
    {
        if($this->requested_ticket_id == null)
        {
            //Make sure I don't already have a ticket with the same request_id, year, and name
            $res = $db->select($this->_tbl_name, 'requested_ticket_id', array('request_id'=>'=\''.$this->request_id.'\'', 'year'=>'=\''.$this->year.'\'', 'first'=>'=\''.$this->first.'\'', 'last'=>'=\''.$this->last.'\''));
            if(count($res) > 0)
            {
                $this->requested_ticket_id = $res[0]['requested_ticket_id'];
            }
        }
        return parent::set_in_db($db, $op);
    }

    static function getAll($year)
    {
        $db = new FlipsideTicketDB();
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

    function __construct($data = FALSE)
    {
        if($data != FALSE)
        {
            $this->request_id = $data['request_id'];
            $this->year       = $data['year'];
            $this->first      = $data['first'];
            $this->last       = $data['last'];
            $this->type       = new FlipsideTicketType($data['type']);
        }
    }

    function getCost()
    {
        return $this->type->cost;
    }

    function getTypeName()
    {
        return $this->type->description;
    }

    function isMinor()
    {
        return $this->type->is_minor;
    }

    static function type_to_type_code($type)
    {
        return $type->typeCode;
    }

    function __toString()
    {
        return $this->requested_ticket_id;
    }

    function delete($db)
    {
        return $db->delete($this->_tbl_name, array('requested_ticket_id'=>'=\''.$this->requested_ticket_id.'\''));
    }

    function replace_in_db($db)
    {
        if($db->getVariable('test_mode'))
        {
            $this->test = 1;
        }
        parent::replace_in_db($db);
    }
}
?>
