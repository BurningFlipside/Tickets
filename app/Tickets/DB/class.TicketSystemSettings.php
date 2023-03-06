<?php
namespace Tickets\DB;
//require_once('Autoload.php');

class TicketSystemSettings extends KeyValueSingletonDataTable
{
    function getTableName(): string
    {
        return 'Variables';
    }

    function getKeyColName(): string
    {
        return 'name';
    }

    function getValueColName(): string
    {
        return 'value';
    }

    public function isTestMode(): bool
    {
        return $this['test_mode'] === '1';
    }

    public function count($filter = false): int
    {
        return count((array)$this);
    }

    public static function getYear(): int
    {
        $settings = static::getInstance();
        return $settings['year'];
    }
}
?>
