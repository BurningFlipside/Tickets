<?php
require_once("class.FlipsideDB.php");
require_once('class.FlipsideTicketConstraints.php');
require_once('class.FlipsideDonationType.php');
require_once('class.FlipsideTicketRequest.php');
class FlipsideTicketDB
{
    protected $data_set = null;
    protected static $test_mode = null;
    protected static $year = null;
    protected static $max_buckets = null;

    function __construct()
    {
        $this->data_set = DataSetFactory::get_data_set('tickets');
    }

    function getRequestIdForUser($user)
    {
        $table  = $this->data_set['RequestIDs'];
        $mail   = $user->getEmail();
        $filter = new \Data\Filter("mail eq '$mail'");
        $data   = $table->search($filter, array('request_id'));
        if($data === false || !isset($data[0]) || !isset($data[0]['request_id']))
        {
            return false;
        }
        return $data[0]['request_id'];
    }

    function getNewRequestId($user)
    {
        $table  = $this->data_set['RequestIDs'];
        $data   = $table->search(false, array('MAX(request_id)'));
        if($data === false || !isset($data[0]) || !isset($data[0]['MAX(request_id)']))
        {
            return false;
        }
        $id = $data[0]['MAX(request_id)'];
        if(strpos($id, 'A') === false)
        {
            return 'A00000001';
        }
        $id++;
        $table->create(array('request_id'=>$id,'mail'=>$user->getEmail()));
        return $id;
    }

    function getRequestForUser($user)
    {
        return FlipsideTicketRequest::get_request_by_id_and_year($this->getRequestIdForUser($user), $this->getVariable('year'), $this);
    }

    function getRequestCount($conds = false)
    {
        if($conds !== FALSE)
        {
            throw new \Exception('Unknown conditions!');
        }
        $table  = $this->data_set['TicketRequest'];
        $filter = new \Data\Filter("year eq '".self::getTicketYear()."'");
        $values = $table->search($filter, array('COUNT(*)'));
        return $values[0]['COUNT(*)'];
    }

    function getProblemRequestCount($conds = FALSE)
    {
        if($conds !== FALSE)
        {
            throw new \Exception('Unknown conditions!');
        }
        $table  = $this->data_set['Problems'];
        $filter = new \Data\Filter("year eq '".self::getTicketYear()."'");
        $values = $table->search($filter, array('COUNT(*)'));
        return $values[0]['COUNT(*)'];
    }

    function getRequestedTickets()
    {
        $ret = array();
        $table  = $this->data_set['TicketTypes'];
        $types  = $table->search();
        for($i = 0; $i < count($types); $i++)
        {
            $ret[$i]['typeCode']    = $types[$i]['typeCode'];
            $ret[$i]['description'] = $types[$i]['description'];
            $reqs   = $this->data_set['RequestedTickets'];
            $filter = new \Data\Filter('year eq \''.self::getTicketYear().'\' and type = \''.$types[$i]['typeCode'].'\'');
            $data   = $reqs->search($filter, array('COUNT(*)'));
            if($data === false || !isset($data[0]) || !isset($data[0]['COUNT(*)']))
            {
                 $ret[$i]['count'] = 0;
            }
            else
            {
                $ret[$i]['count'] = $data[0]['COUNT(*)'];;
            }
        }
        return $ret;
    }

    function getTickets()
    {
        $ret = array();
        $types = FlipsideTicketType::get_all_of_type($this);
        for($i = 0; $i < count($types); $i++)
        {
            $ret[$i]['typeCode']    = $types[$i]->typeCode;
            $ret[$i]['description'] = $types[$i]->description;
            $stmt = $this->db->query('SELECT COUNT(*) FROM tblTickets WHERE YEAR = \''.self::getTicketYear().'\' AND type = \''.$types[$i]->typeCode.'\';');
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

    function getTicketCount($filter_str = false)
    {
        $year   = self::getTicketYear();
        $filter = false;
        if($filter_str === false)
        {
            $filter = new \Data\Filter("year eq '$year'");
        }
        else
        {
            $filter = new \Data\Filter("$filter_str and year eq '$year'");
        }
        $table = $this->data_set['Tickets'];
        $data  = $table->search($filter, array('COUNT(*)'));
        if($data === false || !isset($data[0]) || !isset($data[0]['COUNT(*)']))
        {
            return false;
        }
        return $data[0]['COUNT(*)'];
    }

    function getTicketSoldCount()
    {
        return $this->getTicketCount('sold eq 1');
    }

    function getTicketUnsoldCount()
    {
        return $this->getTicketCount('sold eq 0');
    }

    function getTicketUsedCount()
    {
        return $this->getTicketCount('used eq 1');
    }

    function getTicketUnusedCount()
    {
        return $this->getTicketCount('used eq 0');
    }

    function getReceivedTicketCount()
    {
        $data = $this->sql_query("SELECT COUNT(*) FROM tblTicketRequest INNER JOIN tblRequestedTickets ON tblTicketRequest.request_id=tblRequestedTickets.request_id WHERE tblTicketRequest.private_status=1;");
        if($data == FALSE || !isset($data[0]) || !isset($data[0]['COUNT(*)']))
        {
            return FALSE;
        }
        return $data[0]['COUNT(*)'];
    }

    function getTicketCountByType($type)
    {
        $conds = array('type'=>'=\''.$type.'\'');
        return $this->getTicketCount($conds);
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
        $table  = $this->data_set['Variables'];
        $filter = new \Data\Filter("name eq '$name'");
        $values = $table->search($filter, array('value'));
        if($values === false || !isset($values[0]))
        {
            return false;
        }
        switch($name)
        {
            case 'year':
                self::$year = $values[0]['value'];
                break;
            case 'test_mode':
                self::$test_mode = $values[0]['value'];
                break;
        }
        return $values[0]['value'];
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
        $table  = $this->data_set['LongText'];
        $filter = new \Data\Filter("name eq '$name'");
        $values = $table->search($filter, array('value'));
        if($values === false || !isset($values[0]))
        {
            return false;
        }
        return $values[0]['value'];
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
