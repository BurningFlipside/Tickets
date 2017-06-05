<?php

function request_ticket_api_group()
{
    global $app;
    $app->get('(/)', 'listRequestWTickets');
    $app->get('/types', 'get_requested_types');
    $app->get('/:request_id(/:year)', 'get_request_w_tickets');
}

function listRequestWTickets()
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $requestDataTable = \Tickets\DB\RequestDataTable::getInstance();
    $filter = false;
    if($app->user->isInGroupNamed('TicketAdmins') && $app->odata->filter != false)
    {
        $filter = $app->odata->filter;
    }
    else
    {
        $filter = new \Data\Filter('mail eq \''.$app->user->mail.'\'');
    }
    $requests = $requestDataTable->read($filter);
    if($requests === false)
    {
        $requests = array();
    }
    $count = count($requests);
    $returnArray = array();
    for($i = 0; $i < $count; $i++)
    {
        if($requests[$i]['tickets'] === null)
        {
            continue;
        }
        $count2 = count($requests[$i]['tickets']);
        for($j = 0; $j < $count2; $j++)
        {
            $tmp = (array)$requests[$i];
            unset($tmp['tickets']);
            $tmp['first'] = $requests[$i]['tickets'][$j]->first;
            $tmp['last'] = $requests[$i]['tickets'][$j]->last;
            $tmp['type'] = $requests[$i]['tickets'][$j]->type;
            array_push($returnArray, $tmp);
        }
    }
    $requests = $app->odata->filterArrayPerSelect($returnArray);
    if($app->odata->count)
    {
        $requests = array('@odata.count'=>count($requests), 'value'=>$requests);
    }
    echo safe_json_encode($requests);
}

function get_request_w_tickets($request_id, $year = false)
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $params = $app->request->params();
    $requestDataTable = \Tickets\DB\RequestDataTable::getInstance();
    $filter = false;
    $filter_str = "request_id eq '$request_id'";
    if($year !== false)
    {
        $filter_str += ' and year eq '+$year;
    }
    if(!$app->user->isInGroupNamed('TicketAdmins'))
    {
        $filter_str += ' and mail eq '+$app->user->mail;
    }
    $filter = new \Data\Filter($filter_str);
    $requests = $requestDataTable->read($filter);
    if($requests === false)
    {
        $requests = array();
    }
    $count = count($requests);
    $returnArray = array();
    for($i = 0; $i < $count; $i++)
    {
        if($requests[$i]['tickets'] === null)
        {
            continue;
        }
        $count2 = count($requests[$i]['tickets']);
        for($j = 0; $j < $count2; $j++)
        {
            $tmp = (array)$requests[$i];
            unset($tmp['tickets']);
            $tmp['first'] = $requests[$i]['tickets'][$j]->first;
            $tmp['last'] = $requests[$i]['tickets'][$j]->last;
            $tmp['type'] = $requests[$i]['tickets'][$j]->type;
            array_push($returnArray, $tmp);
        }
    }
    $requests = $app->odata->filterArrayPerSelect($returnArray);
    if($app->odata->count)
    {
        $requests = array('@odata.count'=>count($requests), 'value'=>$requests);
    }
    echo @json_encode($requests);
}

function get_requested_types()
{
    global $app;
    if(!$app->user || !$app->user->isInGroupNamed('TicketAdmins'))
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $settings = \Tickets\DB\TicketSystemSettings::getInstance();
    $year = $settings['year'];
    
    $requestDataTable = \Tickets\DB\RequestDataTable::getInstance();
    $requests = $requestDataTable->read(new \Data\Filter('year eq '.$year), array('tickets,private_status'));
    $tmp = array();

    $requestCount = count($requests);
    for($i = 0; $i < $requestCount; $i++)
    {
        $request = $requests[$i];
        $ticketCount = count($request['tickets']);
        for($j = 0; $j < $ticketCount; $j++)
        {
            $ticket = $request['tickets'][$j];
            if(!isset($tmp[$ticket->type]))
            {
                $tmp[$ticket->type] = array('count'=>0, 'receivedCount'=>0);
            }
            if($request['private_status'] === 6 || $request['private_status'] === 1)
            {
                $tmp[$ticket->type]['receivedCount']++;
            }
            $tmp[$ticket->type]['count']++;
        }
    }
    $typeDataTable = DataSetFactory::getDataTableByNames('tickets', 'TicketTypes');
    $types = $typeDataTable->read(false, array('typeCode', 'description'));
    $count = count($types);
    for($i = 0; $i < $count; $i++)
    {
        $typeCode = $types[$i]['typeCode'];
        $types[$i]['count'] = $tmp[$typeCode]['count'];
        $types[$i]['receivedCount'] = $tmp[$typeCode]['receivedCount'];
    }
    echo json_encode($types);
}

