<?php
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
    $db = new FlipsideTicketDB();
    $vars = $db->getAllVars();
    if($vars === FALSE)
    {
        throw new Exception('Failed to obtain variables!', INTERNAL_ERROR);
    }
    $window = array();
    for($i = 0; $i < count($vars); $i++)
    {
            switch($vars[$i]['name'])
            {
                case 'request_start_date':
                case 'request_stop_date':
                case 'mail_start_date':
                case 'test_mode':
                    $window[$vars[$i]['name']] = $vars[$i]['value'];
                    break;
            }
    }
    unset($vars);
    date_default_timezone_set('CST');
    $window['current'] = date("Y-m-d");
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
    $filter = false;
    $select = false;
    if(isset($params['select']))
    {
        $select = explode(',',$params['select']);
    }
    if(isset($params['filter']))
    {
        $filter = new \Data\Filter($params['filter']);
    }
    $statuses = $status_data_table->read($filter, $select);
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
