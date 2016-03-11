<?php
require_once('Autoload.php');
require_once('app/TicketAutoload.php');

function poolApiGroup()
{
    global $app;
    $app->get('', 'listPools');
    $app->post('', 'createPool');
    $app->get('/:id', 'getPool');
    $app->patch('/:id', 'updatePool');
    $app->delete('/:id', 'deletePool');
}

function getPoolHelper($id, $dataTable = false)
{
    if($dataTable === false)
    {
        $dataSet = DataSetFactory::get_data_set('tickets');
        $dataTable = $dataSet['PoolMap'];
    }
    $filter = new \Data\Filter('pool_id eq '.$id);
    $pools = $dataTable->read($filter);
    if($pools === false || !isset($pools[0]))
    {
        $filter = new \Data\Filter('pool_name eq \''.$id.'\'');
        $pools = $dataTable->read($filter);
    }
    if($pools === false)
    {
        return false;
    }
    $pools[0]['pool_id'] = intval($pools[0]['pool_id']);
    return $pools[0];
}

function listPools()
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
    $dataSet = DataSetFactory::get_data_set('tickets');
    $dataTable = $dataSet['PoolMap'];
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
        if(isset($pools[$i]['pool_id']))
        {
            $pools[$i]['pool_id'] = intval($pools[$i]['pool_id']);
        }
    }
    echo json_encode($pools);
}

function createPool()
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
    $dataSet = DataSetFactory::get_data_set('tickets');
    $dataTable = $dataSet['PoolMap'];
    $obj = $app->getJsonBody(true);
    if(isset($obj['pool_id']))
    {
        unset($obj['pool_id']);
    }
    echo json_encode($dataTable->create($obj));
}

function getPool($id)
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
    $pool = getPoolHelper($id);
    if($pool === false)
    {
        $app->notFound();
    }
    echo json_encode($pool);
}

function updatePool($id)
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
    $dataSet = DataSetFactory::get_data_set('tickets');
    $dataTable = $dataSet['PoolMap'];
    $pool = getPoolHelper($id, $dataTable);
    if($pool === false)
    {
        $app->notFound();
    }
    $obj = $app->getJsonBody(true);
    $filter = new \Data\Filter('pool_id eq '.$pool['pool_id']);
    if(isset($obj['group_name']))
    {
        $pool['group_name'] = $obj['group_name'];
    }
    if(isset($obj['pool_name']))
    {
        $pool['pool_name'] = $obj['pool_name'];
    }
    echo json_encode($dataTable->update($filter, $pool));
}

function deletePool($id)
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
    $pool = getPoolHelper($id);
    if($pool === false)
    {
        $app->notFound();
    }
    $dataSet = DataSetFactory::get_data_set('tickets');
    $dataTable = $dataSet['PoolMap'];
    $filter = new \Data\Filter('pool_id eq '.$pool['pool_id']);
    echo json_encode($dataTable->delete($filter));
}


?>
