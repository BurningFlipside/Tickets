<?php
namespace Tickets\DB;

abstract class SingletonDataTable extends \Flipside\Data\DataTable
{
    protected static $data_set = false;

    protected $data_table;

    public static function getInstance()
    {
        static $instance = [];
        if(!isset($instance[static::class]))
        {
            $instance[static::class] = new static();
        }
        return $instance[static::class];
    }

    protected function __construct()
    {
    }

    protected static function getDataSet()
    {
        if(static::$data_set === false)
        {
            static::$data_set = \Flipside\DataSetFactory::getDataSetByName('tickets');
        }
        return static::$data_set;
    }
}
