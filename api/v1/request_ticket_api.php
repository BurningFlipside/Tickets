<?php

function request_ticket_api_group()
{
    global $app;
    $app->get('', 'list_request_w_tickets');
    $app->get('/types', 'get_requested_types');
    $app->get('/:request_id(/:year)', 'get_request_w_tickets');
}

function list_request_w_tickets()
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $ticket_data_set = DataSetFactory::getDataSetByName('tickets');
    $request_data_table = $ticket_data_set['RequestWTickets'];
    $filter = false;
    if($app->user->isInGroupNamed('TicketAdmins') && $app->odata->filter != false)
    {
        $filter = $app->odata->filter;
    }
    else
    {
        $filter = new \Data\Filter('mail eq \''.$app->user->getEmail().'\'');
    }
    $requests = $request_data_table->search($filter, $app->odata->select);
    if($requests === false)
    {
        $requests = array();
    }
    else if(!is_array($requests))
    {
        $requests = array($requests);
    }
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
    $ticket_data_set = DataSetFactory::getDataSetByName('tickets');
    $request_data_table = $ticket_data_set['RequestWTickets'];
    $filter = false;
    $select = false;
    if(isset($params['select']))
    {
        $select = explode(',',$params['select']);
    }
    $filter_str = 'request_id eq '+$request_id;
    if($year !== false)
    {
        $filter_str += ' and year eq '+$year;
    }
    if(!$app->user->isInGroupNamed('TicketAdmins'))
    {
        $filter_str += ' and mail eq '+$app->user->getEmail();
    }
    $filter = new \Data\Filter($filter_str);
    $requests = $request_data_table->search($filter, $select);
    if($requests === false)
    {
        $requests = array();
    }
    else if(!is_array($requests))
    {
        $requests = array($requests);
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
    $ticket_data_set = DataSetFactory::getDataSetByName('tickets');
    $types = $ticket_data_set->raw_query('SELECT tblTicketTypes.description,COUNT(*) as count FROM tickets.vRequestWTickets INNER JOIN tblTicketTypes ON tblTicketTypes.typeCode=vRequestWTickets.type  WHERE vRequestWTickets.year='.$year.' GROUP BY type;');
    $received = $ticket_data_set->raw_query('SELECT COUNT(*) as count FROM tickets.vRequestWTickets WHERE vRequestWTickets.year='.$year.' AND private_status IN (1,6) GROUP BY type;');
    if($types !== false && $received !== false)
    {
        $count = count($types);
        for($i = 0; $i < $count; $i++)
        {
            if(!isset($received[$i]))
            {
                $types[$i]['receivedCount'] = 0;
                continue;
            }
            $types[$i]['receivedCount'] = $received[$i]['count'];
        }
    }
    echo json_encode($types);
}

