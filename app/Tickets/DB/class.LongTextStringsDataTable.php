<?php
namespace Tickets\DB;

class LongTextStringsDataTable extends KeyValueSingletonDataTable
{
    function getTableName()
    {
        return 'LongText';
    }

    function getKeyColName()
    {
        return 'name';
    }

    function getValueColName()
    {
        return 'value';
    }
}
?>
