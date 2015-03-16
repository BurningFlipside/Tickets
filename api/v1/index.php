<?php
require_once('class.FlipREST.php');
require_once('ticket_api.php');
require_once('request_api.php');

if($_SERVER['REQUEST_URI'][0] == '/' && $_SERVER['REQUEST_URI'][1] == '/')
{
    $_SERVER['REQUEST_URI'] = substr($_SERVER['REQUEST_URI'], 1);
}

$app = new FlipREST();
$app->group('/ticket', 'ticket_api_group');
$app->group('/request', 'request_api_group');

$app->run();
?>
