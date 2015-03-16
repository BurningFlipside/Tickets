<?php
require_once('class.FlipsideTicketRequest.php');

function request_api_group()
{
    global $app;
    $app->get('', 'list_requests');
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
