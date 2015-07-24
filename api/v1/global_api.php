<?php
require_once('class.TicketSystemSettings.php');
require_once('class.Ticket.php');

function global_api_group()
{
    global $app;
    $app->get('/window', 'show_window');
    $app->get('/statuses', 'list_statuses');
    $app->get('/vars', 'get_vars');
    $app->get('/vars/:name', 'get_var');
    $app->patch('/vars/:name', 'set_var');
    $app->post('/vars/:name', 'create_var');
    $app->delete('/vars/:name', 'del_var');
}

function show_window()
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $settings = TicketSystemSettings::getInstance();
    $window = array();
    $window['request_start_date'] = $settings->getVariable('request_start_date');
    $window['request_stop_date']  = $settings->getVariable('request_stop_date');
    $window['mail_start_date']    = $settings->getVariable('mail_start_date');
    $window['test_mode']          = $settings->getVariable('test_mode');
    $window['year']               = $settings->getVariable('year');
    $window['current']            = date("Y-m-d");
    echo json_encode($window);
}

function list_statuses()
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $params = $app->request->params();
    $ticket_data_set = DataSetFactory::get_data_set('tickets');
    $status_data_table = $ticket_data_set['RequestStatus'];
    $statuses = $status_data_table->read($app->odata->filter, $app->odata->select);
    if($statuses === false)
    {
        $statuses = array();
    }
    else if(!is_array($statuses))
    {
        $statuses = array($statuses);
    }
    echo json_encode($statuses);
}

function get_vars()
{
    global $app;
    if(!$app->user || !$app->user->isInGroupNamed('TicketAdmins'))
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $settings = TicketSystemSettings::getInstance();
    $vars = $settings->toArray();
    echo json_encode($vars);
}

function get_var($name)
{
    global $app;
    if(!$app->user || !$app->user->isInGroupNamed('TicketAdmins'))
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $settings = TicketSystemSettings::getInstance();
    echo json_encode($settings->getVariable($name));
}

function set_var($name)
{
    global $app;
    if(!$app->user || !$app->user->isInGroupNamed('TicketAdmins'))
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $settings = TicketSystemSettings::getInstance();
    $val = $app->get_json_body();
    $ret = $settings->setVariable($name, $val);
    echo json_encode($ret);
}

function create_var($name)
{
    global $app;
    if(!$app->user || !$app->user->isInGroupNamed('TicketAdmins'))
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $settings = TicketSystemSettings::getInstance();
    $val = $app->get_json_body();
    $ret = $settings->createVariable($name, $val);
    echo json_encode($ret);
}

function del_var($name)
{
     global $app;
    if(!$app->user || !$app->user->isInGroupNamed('TicketAdmins'))
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $settings = TicketSystemSettings::getInstance();
    $ret = $settings->deleteVariable($name);
    echo json_encode($ret);
}

?>
