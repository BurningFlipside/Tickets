<?php
namespace Tickets\DB;

class TicketsDataTable extends SingletonDataTable
{
    protected $settings;

    protected function __construct()
    {
        parent::__construct();
        $data_set = self::getDataSet();
        $this->data_table = $data_set['Tickets'];
        $this->settings = TicketSystemSettings::getInstance();
    }

    private function modify_filter(&$filter)
    {
        if($filter !== false && $filter->contains('hash_words'))
        {
            $clause = $filter->getClause('hash_words');
            $clause->var1 = 'hash';
            if(strncmp($clause->var2, '"', 1) === 0 || strncmp($clause->var2, '\'', 1) === 0)
            {
                $clause->var2 = substr($clause->var2, 1, strlen($clause->var2)-2);
            }
            $clause->var2 = trim($clause->var2);
            $clause->var2 = "'".\Ticket::words_to_hash($clause->var2)."'";
        }
    }

    function create($data)
    {
        if($this->settings->isTestMode())
        {
            $data['test'] = 1;
        }
        return $this->data_table->create($data);
    }

    function read($filter=false, $select=false, $count=false, $skip=false, $sort=false, $params=false)
    {
        $this->modify_filter($filter);
        $db_select = $select;
        if($db_select !== false && in_array('hash_words', $db_select))
        {
            if(!in_array('hash', $db_select))
            {
                array_push($db_select, 'hash');
            }
            $db_select = array_diff($db_select, array('hash_words'));
        }
        $res = $this->data_table->read($filter, $db_select, $count, $skip, $sort, $params);
        if($res === false)
        {
            return false;
        }
        if($select === false || in_array('hash_words', $select))
        {
            $count = count($res);
            for($i = 0; $i < $count; $i++)
            {
                $res[$i]['hash_words'] = \Tickets\Ticket::hash_to_words($res[$i]['hash']);
                if($select !== false && !in_array('hash', $select))
                {
                    unset($res[$i]['hash']);
                }
            }
        }
        return $res;
    }

    function update($filter, $data)
    {
        $this->modify_filter($filter);
        if($this->settings->isTestMode())
        {
            $data['test'] = 1;
        }
        return $this->data_table($filter, $data);
    }

    function delete($filter)
    {
        $this->modify_filter($filter);
        return $this->data_table($filter);
    }
}
?>
