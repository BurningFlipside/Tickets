<?php
require_once("class.FlipsideDB.php");
require_once('class.FlipsideTicketConstraints.php');
require_once('class.FlipsideDonationType.php');
require_once('class.FlipsideTicketRequest.php');
class FlipsideTicketDB extends FlipsideDB
{
    protected static $test_mode = null;
    protected static $year = null;

    function __construct()
    {
        parent::__construct('tickets');
    }

    function getRequestIdForUser($uesr)
    {
        return FALSE;
    }

    function getNewRequestId()
    {
        return 'A0000001';
    }

    function getRequestForUser($user)
    {
        return FALSE;
    }

    function getRequestCount()
    {
        $stmt = $this->db->query('SELECT COUNT(*) FROM tblTicketRequest WHERE YEAR = \''.self::getTicketYear().'\';');
        if($stmt == FALSE)
        {
            return FALSE;
        }
        $data = $stmt->fetchAll();
        if($data == FALSE || !isset($data[0]) || !isset($data[0][0]))
        {
            return FALSE;
        }
        return $data[0][0];
    }

    function getTicketSoldCount()
    {
        return 2000;
    }

    function getTicketUnsoldCount()
    {
        return 1000;
    }

    function getFlipsideTicketConstraints()
    {
        $ret = new FlipsideTicketConstraints();
        $ret->max_total_tickets = $this->getVariable('max_tickets_per_request');
        $ret->ticket_types = FlipsideTicketType::get_all_of_type($this);
        return $ret; 
    }

    function getFlipsideDonationTypes()
    {
        return FlipsideDonationType::get_all_of_type($this);
    }

    function getVariable($name)
    {
        $array = $this->select('tblVariables', 'value', array('name'=>'=\''.$name.'\''));
        if($array == FALSE || !isset($array[0]))
        {
            return FALSE;
        }
        switch($name)
        {
            case 'year':
                self::$year = $array[0]['value'];
                break;
            case 'test_mode':
                self::$test_mode = $array[0]['value'];
                break;
        }
        return $array[0]['value'];
    }

    function getAllVars()
    {
        return $this->select('tblVariables');
    }

    function setVariable($name, $value)
    {
        $array = array('name'=>$name, 'value'=>$value);
        return $this->replace_array('tblVariables', $array);
    }

    function deleteVariable($name)
    {
        return $this->delete('tblVariables', array('name'=>'=\''.$name.'\''));
    }

    static function getTicketTypeByType($type)
    {
        $db = new static();
        return FlipsideTicketType::select_from_db($db, 'typeCode', $type);
    }

    static function getTicketYear()
    {
        if(self::$year != null)
        {
            return self::$year;
        }
        $db = new static();
        return $db->getVariable('year');
    }

    static function getTestMode()
    {
        if(self::$test_mode != null)
        {
            return self::$test_mode;
        }
        $db = new static();
        return $db->getVariable('test_mode');
    }
}
?>
