<?php
require_once('class.FlipsideDonationType.php');
class FlipsideDonation extends FlipsideDBObject
{
    protected $_tbl_name = 'tblRequestDonation';
    protected $_sql_special = array('type' => 'FlipsideDonation::type_to_entity_name');
    protected $_sql_ai_key = array('donation_id');

    public $donation_id = null;
    public $request_id;
    public $year;
    public $type;
    public $amount;
    public $disclose;

    static function populate_children($db, &$type)
    {
        $type->type = FlipsideDonationType::select_from_db($db, 'entityName', $type->type);
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

    static function test_donation($request_id, $year, $num)
    {
        $type = new static();
        $type->request_id = $request_id;
        $type->year       = $year;
        $type->amount     = 10;
        return $type;
    }

    protected function set_in_db($db, $op)
    {
        if($this->donation_id == null)
        {
            //Make sure I don't already have a ticket with the same request_id, year, and name
            $res = $db->select($this->_tbl_name, 'donation_id', array('request_id'=>'=\''.$this->request_id.'\'', 'year'=>'=\''.$this->year.'\'', 'type'=>'=\''.$this->type->entityName.'\''));
            if(count($res) > 0)
            {
                $this->donation_id = $res[0]['donation_id'];
                $op = 'replace';
            }
        }
        if($op == 'insert' && ($this->amount == 0 || $this->amount == null))
        {
            return 0;
        }
        else if($op == 'replace' && ($this->amount == 0 || $this->amount == null))
        {
            if($this->donation_id != null)
            {
                return $db->delete($this->_tbl_name, array('donation_id' => '=\''.$this->donation_id.'\''));
            }
            else
            {
                return 0;
            }
        }
        return parent::set_in_db($db, $op);
    }

    function __construct($type = null, $data = null)
    {
        if($type != null)
        {
            $this->type       = new FlipsideDonationType($type);
        }
        if($data != null)
        {
            $this->request_id = $data['request_id'];
            $this->year       = $data['year'];
            $this->amount     = $data['amount'];
            if(isset($data['disclose']))
            {
                if($data['disclose'] == 'on')
                {
                    $this->disclose = TRUE;
                }
                else
                {
                    $this->disclose = $data['disclose'];
                }
            }
            else
            {
                $this->disclose = FALSE;
            }
        }
    }

    static function type_to_entity_name($type)
    {
        return $type->entityName;
    }
}
?>
