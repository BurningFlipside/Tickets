<?php
require_once("class.FlipsideDB.php");
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

    function getTicketCountByType($type)
    {
        $conds = array('type'=>'=\''.$type.'\'');
        return $this->getTicketCount($conds);
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
}
?>
