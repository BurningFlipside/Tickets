<?php
namespace Tickets;

class TicketType extends \SerializableObject
{
    private static $types = array();
    private static $dataTable = null;

    function __construct($data = false)
    {
        if($data !== false)
        {
            $this->typeCode      = $data['typeCode'];
            $this->description   = $data['description'];
            $this->cost          = $data['cost'];
            $this->maxPerRequest = $data['max_per_request'];
            $this->isMinor       = $data['is_minor'];
        }
    }

    static function getDataTable()
    {
        $dataSet = \DataSetFactory::getDataSetByName('tickets');
        static::$dataTable = $dataSet['TicketTypes'];
    }

    static function getAllTicketTypes()
    {
        if(static::$dataTable === null)
        {
            static::getDataTable();
        }
        $types = static::$dataTable->read();
        foreach($types as $type)
        {
            static::$types[$type['typeCode']] = new static($type);
        }
        return static::$types;
    }

    static function getTicketType($typeCode)
    {
        if(isset(static::$types[$typeCode]))
        {
            return static::$types[$typeCode];
        }
        if(static::$dataTable === null)
        {
            static::getDataTable();
        }
        $types = static::$dataTable->read(new \Data\Filter("typeCode eq '$typeCode'"));
        if($types === false || !isset($types[0]))
        {
            throw new \Exception('No such type '.$typeCode.'!');
        }
        static::$types[$typeCode] = new static($types[0]);
        return static::$types[$typeCode];
    }

    static function typeIsMinor($typeCode)
    {
        $type = static::getTicketType($typeCode);
        return $type->isMinor;
    }

    static function getCostForType($typeCode)
    {
        $type = static::getTicketType($typeCode);
        return $type->cost;
    }
}
?>
