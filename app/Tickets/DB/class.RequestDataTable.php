<?php
namespace Tickets\DB;

class RequestDataTable extends \Flipside\Data\ObjectDataTable
{
    protected $className = '\Tickets\Flipside\Request';
    protected $settings;

    protected function __construct()
    {
        $dataTable = \Flipside\DataSetFactory::getDataTableByNames('tickets', 'TicketRequest');
        parent::__construct($dataTable);
        $this->settings = TicketSystemSettings::getInstance();
    }

    function create($data)
    {
        if($this->settings->isTestMode())
        {
            $data['test'] = 1;
        }
        return parent::create($data);
    }

    function update($filter, $data, $bypassQuote = false)
    {
        if($this->settings->isTestMode())
        {
            $data['test'] = 1;
        }
        return parent::update($filter, $data);
    }
}
