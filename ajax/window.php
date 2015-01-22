<?php
if($_SERVER["HTTPS"] != "on")
{
    header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
    exit();
}
require_once("class.FlipsideTicketDB.php");
if(strtoupper($_SERVER['REQUEST_METHOD']) == 'GET')
{
    $db = new FlipsideTicketDB();
    $vars = $db->getAllVars();
    if($vars === FALSE)
    {
        echo json_encode(array('error' => "Internal Error! Failed to obtain variables!"));
    }
    else
    {
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
        echo json_encode(array('success' => 0, 'window' => $window));
    }
}
else
{
    echo json_encode(array('error' => "Unrecognized Operation ".$_SERVER['REQUEST_METHOD']));
    die();
}
/* vim: set tabstop=4 shiftwidth=4 expandtab: */
?>
