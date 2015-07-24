<?php
require_once('class.TicketSystemSettings.php');
require_once('class.Ticket.php');

function global_api_group()
{
    global $app;
    $app->get('/window', 'show_window');
    $app->get('/statuses', 'list_statuses');
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

?>
