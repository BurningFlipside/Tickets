<?php
namespace Tickets\DB;

abstract class KeyValueSingletonDataTable extends SingletonDataTable implements \ArrayAccess,\JsonSerializable
{
    protected $cache;

    protected function __construct()
    {
        parent::__construct();
        $data_set = self::getDataSet();
        $this->data_table = $data_set[$this->getTableName()];
        $this->cache = array();
    }

    abstract function getTableName();
    abstract function getKeyColName();
    abstract function getValueColName();

    function create($data)
    {
        if(count($data) != 2)
        {
            throw new \Exception('Data is not a key value pair!');
        }
        else if(!isset($data[$this->getKeyColName()]))
        {
            throw new \Exception('Must contain key column ('.$this->getKeyColName().')!');
        }
        else if(!isset($data[$this->getValueColName()]))
        {
            throw new \Exception('Must contain value column ('.$this->getValueColName().')!');
        }
        $this->cache[$data[$this->getKeyColName()]] = $data[$this->getValueColName()];
        return $this->data_table->create($data);
    }

    function read($filter=false, $select=false, $count=false, $skip=false, $sort=false, $params=false)
    {
        return $this->data_table->read($filter, $db_select, $count, $skip, $sort, $params);
    }

    function update($filter, $data)
    {
        $this->cache = array();
        return $this->data_table($filter, $data);
    }

    function delete($filter)
    {
        $this->cache = array();
        return $this->data_table($filter);
    }

    public function toArray()
    {
        $vars = $this->data_table->read();
        $count = count($vars);
        for($i = 0; $i < $count; $i++)
        {
            $this->cache[$vars[$i][$this->getKeyColName()]] = $vars[$i][$this->getValueColName()];
        }
        return $this->cache;
    } 

    public function offsetExists($offset)
    {
        if(!isset($this->cache[$offset]))
        {
            $this->toArray();
        }
        return isset($this->cache[$offset]);
    }

    public function offsetGet($offset)
    {
        if(isset($this->cache[$offset]))
        {
            return $this->cache[$offset];
        }
        $vars = $this->data_table->read(new \Data\Filter($this->getKeyColName()." eq '$offset'"));
        if($vars === false || !isset($vars))
        {
            return false;
        }
        $this->cache[$offset] = $vars[0][$this->getValueColName()];
        return $this->cache[$offset];
    }

    public function offsetSet($offset, $value)
    {
        //First cache all the current variables
        $this->toArray();
        if(isset($this->cache[$offset]))
        {
            //Already in database, update it
            $this->data_table->update(new \Data\Filter($this->getKeyColName()." eq '$offset'"), array($this->getValueColName()=>$value));
        }
        else
        {
            $this->data_table->create(array($this->getKeyColName()=>$offset, $this->getValueColName()=>$value));
        }
        $this->cache[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->cache[$offset]);
        return $this->datatable->delete(new \Data\Filter($this->getKeyColName()." eq '$offset'"));
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
?>
