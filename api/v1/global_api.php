<?php
require_once('class.Ticket.php');

function global_api_group()
{
    global $app;
    $app->get('/window', 'show_window');
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

?>
