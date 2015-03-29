<?php
require_once('class.FlipsideTicketRequest.php');

function request_api_group()
{
    global $app;
    $app->get('', 'list_requests');
    $app->get('/:request_id(/:year)', 'get_request');
}

function odata_filter_to_sql_conditions($filter, $db)
{
    $field = strtok($filter, ' ');
    $operator = strtok(' ');
    $rest = strtok("\0");
    switch($operator)
    {
        case 'ne':
            $operator = '!=';
            break;
        case 'eq':
            $operator = '=';
            break;
        case 'lt':
            $operator = '<';
            break;
        case 'le':
            $operator = '<=';
            break;
        case 'gt':
            $operator = '>';
            break;
        case 'ge':
            $operator = '>=';
            break;
    }
    $ret = array($field=>$operator.$rest);
    return $ret;
}

function list_requests()
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $params = $app->request->params();
    $db = new FlipsideTicketDB();
    if(isset($params['fmt']))
    {
       unset($params['fmt']);
    }
    $requests = array();
    if(count($params) > 0 && $app->user->isInGroupNamed('TicketAdmins'))
    {
        if(isset($params['filter']))
        {
            $conds = odata_filter_to_sql_conditions($params['filter'], $db);
            $requests = FlipsideTicketRequest::select_from_db_multi_conditions($db, $conds);
        }
        else
        {
            process_params($params);
            $requests = FlipsideTicketRequest::select_from_db_multi_conditions($db, $params);
        }
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
