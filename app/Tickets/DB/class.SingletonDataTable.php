<?php
namespace Tickets\DB;

class SingletonDataTable extends \Data\DataTable
{
    protected static $data_set = false;

    protected $data_table;

    public static function getInstance()
    {
        static $instance = null;
        if(null === $instance)
        {
            $instance = new static();
        }
        return $instance;
    }

    protected function __construct()
    {
    }

    protected static function getDataSet()
    {
        if(static::$data_set === false)
        {
            static::$data_set = \DataSetFactory::getDataSetByName('tickets');
        }
        return static::$data_set;
    }
}
