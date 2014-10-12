<?php
require_once("class.FlipsideDB.php");
require_once('class.FlipsideTicketConstraints.php');
require_once('class.FlipsideDonationType.php');
require_once('class.FlipsideTicketRequest.php');
class FlipsideTicketDB extends FlipsideDB
{
    protected static $test_mode = null;
    protected static $year = null;
    protected static $max_buckets = null;

    function __construct()
    {
        parent::__construct('tickets');
    }

    function getRequestIdForUser($user)
    {
        $conds['mail'] = '=\''.$user->mail[0].'\'';
        $data = $this->select('tblTicketRequest', 'request_id', $conds);
        if($data == FALSE || !isset($data[0]) || !isset($data[0]['request_id']))
        {
            return FALSE;
        }
        return $data[0]['request_id'];
    }

    function getNewRequestId()
    {
        $data = $this->select('tblTicketRequest', 'MAX(request_id)');
        if($data == FALSE || !isset($data[0]) || !isset($data[0]['MAX(request_id)']))
        {
            return FALSE;
        }
        $id = $data[0]['MAX(request_id)'];
        if(strpos($id, 'A') === FALSE)
        {
            return 'A00000001';
        }
        $id++;
        return $id;
    }

    function getRequestForUser($user)
    {
        return new FlipsideTicketRequest($this->getRequestIdForUser($user), FALSE, $this->getVariable('year'));
    }

    function getRequestCount($conds = FALSE)
    {
        if($conds == FALSE)
        {
            $conds = array();
        }
        $conds['year'] = '=\''.self::getTicketYear().'\'';
        $data = $this->select('tblTicketRequest', 'COUNT(*)', $conds);
        if($data == FALSE || !isset($data[0]) || !isset($data[0]['COUNT(*)']))
        {
            return FALSE;
        }
        return $data[0]['COUNT(*)'];
    }

    function getProblemRequestCount($conds = FALSE)
    {
        if($conds == FALSE)
        {
            $conds = array();
        }
        $conds['year'] = '=\''.self::getTicketYear().'\'';
        $data = $this->select('vProblems', 'COUNT(*)', $conds);
        if($data == FALSE || !isset($data[0]) || !isset($data[0]['COUNT(*)']))
        {
            return FALSE;
        }
        return $data[0]['COUNT(*)'];
    }

    function getRequestedTickets()
    {
        $ret = array();
        $types = FlipsideTicketType::get_all_of_type($this);
        for($i = 0; $i < count($types); $i++)
        {
            $ret[$i]['typeCode']    = $types[$i]->typeCode;
            $ret[$i]['description'] = $types[$i]->description;
            $stmt = $this->db->query('SELECT COUNT(*) FROM tblRequestedTickets WHERE YEAR = \''.self::getTicketYear().'\' AND type = \''.$types[$i]->typeCode.'\';');
            if($stmt == FALSE)
            {
                $ret[$i]['count'] = 0;
                continue;
            }
            $data = $stmt->fetchAll();
            if($data == FALSE || !isset($data[0]) || !isset($data[0][0]))
            {
                $ret[$i]['count'] = 0;
                continue;
            }
            $ret[$i]['count'] = $data[0][0];
        }
        return $ret;
    }

    function getView($viewName, $year = FALSE)
    {
        if($year == FALSE)
        {
            $year = self::getTicketYear();
        }
        $stmt = $this->db->query("SELECT * FROM $viewName WHERE YEAR = '$year';");
        if($stmt == FALSE)
        {
            return FALSE;
        }
        return $stmt->fetchAll();
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

    function getLongText($name)
    {
        $array = $this->select('tblLongText', 'value', array('name'=>'=\''.$name.'\''));
        if($array == FALSE || !isset($array[0]))
        {
            return FALSE;
        }
        return $array[0]['value'];
    }

    function setLongText($name, $value)
    {
        $array = array('name'=>$name, 'value'=>$value);
        return $this->replace_array('tblLongText', $array);
    }

    function getAllYears()
    {
        $stmt = $this->db->query('SELECT DISTINCT(year) FROM tblTicketRequest;');
        if($stmt == FALSE)
        {
            return array(self::getTicketYear());
        }
        $data = $stmt->fetchAll();
        if($data == FALSE)
        {
            return array(self::getTicketYear());
        }
        $ret = array();
        for($i = 0; $i < count($data); $i++)
        {
            $ret[$i] = $data[$i]['year'];
        }
        if(!in_array(self::getTicketYear(), $ret))
        {
            array_push($ret, self::getTicketYear());
        }
        return $ret;
    }

    function clearTestMode()
    {
        $res = TRUE;
        $rc = $this->delete('tblTicketRequest', array('test'=>'=\'1\''));
        if($rc === FALSE) $res = FALSE;
        $rc = $this->delete('tblRequestDonation', array('test'=>'=\'1\''));
        if($rc === FALSE) $res = FALSE;
        $rc = $this->delete('tblRequestedTickets', array('test'=>'=\'1\''));
        if($rc === FALSE) $res = FALSE;
        return $res;
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

    static function getMaxBuckets()
    {
        if(self::$max_buckets != null)
        {
            return self::$max_buckets;
        }
        $db = new static();
        return $db->getVariable('max_buckets');
    }

    static function get_var($name)
    {
        $db = new static();
        return $db->getVariable($name);
    }

    static function get_long_text($long_test_name)
    {
        $db = new static();
        return $db->getLongText($long_test_name);
    }

    static function set_long_text($long_test_name, $value)
    {
        $db = new static();
        return $db->setLongText($long_test_name, $value);
    }
}
?>
