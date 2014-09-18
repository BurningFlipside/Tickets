<?php
require_once('class.FlipsideTicketType.php');
class FlipsideTicketRequestTicket extends FlipsideDBObject
{
    protected $_tbl_name = 'tblRequestedTickets';
    protected $_sql_special = array('type' => 'FlipsideTicketRequestTicket::type_to_type_code');
    protected $_sql_ai_key = array('requested_ticket_id');

    public $requested_ticket_id = null;
    public $request_id;
    public $year;
    public $first;
    public $last;
    public $type;

    static function populate_children($db, &$type)
    {
        $type->type = FlipsideTicketType::select_from_db($db, 'typeCode', $type->type);
    }

    static function select_from_db($db, $col, $value)
    {
        $type = parent::select_from_db($db, $col, $value);
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

    function __construct($data)
    {
        $this->request_id = $data['request_id'];
        $this->year       = $data['year'];
        $this->first      = $data['first'];
        $this->last       = $data['last'];
        $this->type       = new FlipsideTicketType($data['type']);
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
}
?>
