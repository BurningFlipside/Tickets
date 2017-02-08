<?php
namespace Tickets\DB;
require_once('Autoload.php');

class TicketSystemSettings extends KeyValueSingletonDataTable
{
    function getTableName()
    {
        return 'Variables';
    }

    function getKeyColName()
    {
        return 'name';
    }

    function getValueColName()
    {
        return 'value';
    }

    public function isTestMode()
    {
        return $this['test_mode'] === '1';
    }

    public function count($filter = false)
    {
        return count((array)$this);
    }

    public function search($filter = false, $select = false, $count = false, $skip = false, $sort = false, $params = false)
    {
        return array();
    }
}
?>
