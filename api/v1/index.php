<?php
require_once('class.FlipREST.php');
require_once('ticket_api.php');
require_once('ticket_history_api.php');
require_once('request_api.php');
require_once('request_ticket_api.php');
require_once('global_api.php');
require_once('pool_api.php');

if($_SERVER['REQUEST_URI'][0] == '/' && $_SERVER['REQUEST_URI'][1] == '/')
{
    $_SERVER['REQUEST_URI'] = substr($_SERVER['REQUEST_URI'], 1);
}

$app = new FlipREST();
$app->get('(/)', 'getRoot');
$app->get('$metadata', 'getMetadata');
$app->get('/$metadata', 'getMetadata');
$app->group('/ticket', 'ticket_api_group');
$app->group('/tickets', 'ticket_api_group');
$app->group('/tickets_history', 'ticket_history_api_group');
$app->group('/request', 'request_api_group');
$app->group('/requests', 'request_api_group');
$app->group('/requests_w_tickets', 'request_ticket_api_group');
$app->group('/globals', 'global_api_group');
$app->group('/pools', 'poolApiGroup');

function getRoot()
{
    global $app;
    $ret = array();
    $root = $app->request->getRootUri();
    $ret['@odata.context'] = $root.'/$metadata';
    $ret['value'] = array();
    $ret['value']['Tickets'] = array('@odata.id'=>$root.'/tickets');
    $ret['value']['TicketsHistory'] = array('@odata.id'=>$root.'/tickets_history');
    $ret['value']['Requests'] = array('@odata.id'=>$root.'/requests');
    $ret['value']['Globals'] = array('@odata.id'=>$root.'/globals');
    echo json_encode($ret);
}

function getMetadata()
{
    global $app;
    echo file_get_contents('csdl.xml');
}

$app->run();
?>
