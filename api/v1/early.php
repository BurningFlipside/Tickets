<?php
require_once('Autoload.php');
require_once('app/TicketAutoload.php');

function eeApiGroup()
{
    global $app;
    $app->get('', 'listEarlyEntryWindows');
}

function listEarlyEntryWindows()
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    if(!$app->user->isInGroupNamed('TicketAdmins'))
    {
        throw new Exception('Must be member of TicketAdmins group', ACCESS_DENIED);
    }
    $dataSet = DataSetFactory::getDataSetByName('tickets');
    $dataTable = $dataSet['EarlyEntryMap'];
    $pools = $dataTable->read($app->odata->filter, $app->odata->select, $app->odata->top, $app->odata->skip, $app->odata->orderby);
    if($pools === false)
    {
        $pools = array();
    }
    else if(!is_array($pools))
    {
        $pools = array($pools);
    }
    $count = count($pools);
    for($i = 0; $i < $count; $i++)
    {
        if(isset($pools[$i]['earlyEntrySetting']))
        {
            $pools[$i]['earlyEntrySetting'] = intval($pools[$i]['earlyEntrySetting']);
        }
    }
    echo json_encode($pools);
}
