<?php

function request_ticket_api_group()
{
    global $app;
    $app->get('', 'list_request_w_tickets');
    $app->get('/types', 'get_requested_types');
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
    echo @json_encode($requests);
}

function get_requested_types()
{
    global $app;
    if(!$app->user || !$app->user->isInGroupNamed('TicketAdmins'))
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $ticket_data_set = DataSetFactory::get_data_set('tickets');
    $types = $ticket_data_set->raw_query('SELECT tblTicketTypes.description,COUNT(*) as count FROM tickets.vRequestWTickets INNER JOIN tblTicketTypes ON tblTicketTypes.typeCode=vRequestWTickets.type GROUP BY type;');
    echo json_encode($types);
}

?>
