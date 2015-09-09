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
    $params = $app->request->params();
    $ticket_data_set = DataSetFactory::get_data_set('tickets');
    $request_data_table = $ticket_data_set['RequestWTickets'];
    $filter = false;
    $select = false;
    if(isset($params['select']))
    {
        $select = explode(',',$params['select']);
    }
    if($app->user->isInGroupNamed('TicketAdmins') && isset($params['filter']))
    {
        $filter = new \Data\Filter($params['filter']);
    }
    else
    {
        $filter = new \Data\Filter('mail eq \''.$app->user->getEmail().'\'');
    }
    $requests = $request_data_table->search($filter, $select);
    if($requests === false)
    {
        $requests = array();
    }
    else if(!is_array($requests))
    {
        $requests = array($requests);
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
    $ticket_data_set = DataSetFactory::get_data_set('tickets');
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
    $ticket_data_set = DataSetFactory::get_data_set('tickets');
    $types = $ticket_data_set->raw_query('SELECT tblTicketTypes.description,COUNT(*) as count FROM tickets.vRequestWTickets INNER JOIN tblTicketTypes ON tblTicketTypes.typeCode=vRequestWTickets.type  WHERE vRequestWTickets.year='.$year.' GROUP BY type;');
    echo json_encode($types);
}

?>
