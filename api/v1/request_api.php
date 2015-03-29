<?php
require_once('class.FlipsideTicketRequest.php');

function request_api_group()
{
    global $app;
    $app->get('', 'list_requests');
    $app->get('/crit_vols', 'get_crit_vols');
    $app->get('/:request_id(/:year)', 'get_request');
}

function list_requests()
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $params = $app->request->params();
    $ticket_data_set = DataSetFactory::get_data_set('tickets');
    $request_data_table = $ticket_data_set['TicketRequest'];
    $filter = false;
    $select = false;
    $count  = false;
    $skip   = false;
    $show_children = false;
    if(isset($params['select']))
    {
        $select = explode(',',$params['select']);
    }
    if(isset($params['top']))
    {
        $count = $params['top'];
    }
    else if(isset($params['length']))
    {
        $count = $params['length'];
    }
    if(isset($params['skip']))
    {
        $skip = $params['skip'];
    }
    else if(isset($params['start']))
    {
        $skip = $params['start'];
    }
    if($app->user->isInGroupNamed('TicketAdmins') && isset($params['filter']))
    {
        $filter = new \Data\Filter($params['filter']);
        if(isset($params['with_children']))
        {
            $show_children = $params['with_children'];
        }
    }
    else
    {
        $filter = new \Data\Filter('mail eq \''.$app->user->getEmail().'\'');
        $show_children = true;
    }
    $requests = $request_data_table->read($filter, $select, $count, $skip);
    if($requests === false)
    {
        $requests = array();
    }
    else if(!is_array($requests))
    {
        $requests = array($requests);
    }
    if($show_children)
    {
        $ticket_data_table   = $ticket_data_set['RequestedTickets'];
        $donation_data_table = $ticket_data_set['RequestDonation'];
        $status_data_table   = $ticket_data_set['RequestStatus'];
        $ticket_data_table->prefetch_all('request_id');
        $donation_data_table->prefetch_all('request_id');
        $status_data_table->prefetch_all();
        $request_count = count($requests);
        for($i = 0; $i < $request_count; $i++)
        {
            $filter = new \Data\Filter('request_id eq \''.$requests[$i]['request_id'].'\' and year eq '.$requests[$i]['year']);
            $requests[$i]['tickets']   = $ticket_data_table[$requests[$i]['request_id']];
            $requests[$i]['donations'] = $donation_data_table[$requests[$i]['request_id']];
            $requests[$i]['status'] = $status_data_table[$requests[$i]['status']];
        }
    }
    echo @json_encode($requests);
}

function get_crit_vols()
{
    global $app;
    if(!$app->user || !$app->user->isInGroupNamed('TicketAdmins'))
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $ticket_data_set = DataSetFactory::get_data_set('tickets');
    $types = $ticket_data_set->raw_query('SELECT crit_vol,protected,COUNT(*) as count FROM tickets.tblTicketRequest GROUP BY crit_vol,protected;');
    echo json_encode($types);
}

function get_request($request_id, $year = FALSE)
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $params = $app->request->params();
    if(isset($params['fmt']))
    {
       unset($params['fmt']);
    }
    $db = new FlipsideTicketDB();
    $requests = array();
    if(count($params) > 0 && $app->user->isInGroupNamed('TicketAdmins'))
    {
        process_params($params);
        $requests = FlipsideTicketRequest::select_from_db_multi_conditions($db, $params);
    }
    else
    {
        $request = $db->getRequestForUser($app->user);
        if($request !== FALSE)
        {
            $requests[0] = $request;
        }
    }
    echo json_encode($requests);
}

?>
