<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if(file_exists(__DIR__ . '/vendor/autoload.php'))
{
    require(__DIR__ . '/vendor/autoload.php');
}
else if(file_exists(__DIR__ . '/../../../../common/Autoload.php'))
{
    require(__DIR__ . '/../../../../common/Autoload.php');
}

require('class.BaseAPI.php');
require('class.AdminTicketDataTableAPI.php');
require('class.GlobalAPI.php');
require('class.TicketDataTableAPI.php');
require('class.PoolAPI.php');
require('class.RequestAPI.php');
require('class.RequestWithTicketsAPI.php');
require('class.SecondaryAPI.php');
require('class.TicketAPI.php');
require('class.TicketHistoryAPI.php');
require('class.Square.php');
require('class.PendingPurchaseAPI.php');
require('class.EarlyEntryAPI.php');

$site = new \Flipside\Http\WebSite();
$site->registerAPI('/', new BaseAPI());
$site->registerAPI('/earlyEntry', new EarlyEntryAPI());
$site->registerAPI('/globals', new GlobalAPI());
$site->registerAPI('/globals/donation_types', new TicketDataTableAPI('tickets', 'DonationTypes', 'entityName'));
$site->registerAPI('/globals/ticket_types', new TicketDataTableAPI('tickets', 'TicketTypes', 'TypeCode'));
$site->registerAPI('/globals/statuses', new TicketDataTableAPI('tickets', 'RequestStatus', 'status_id'));
$site->registerAPI('/globals/vars', new AdminTicketDataTableAPI('tickets', 'Variables', 'name'));
$site->registerAPI('/globals/long_text', new AdminTicketDataTableAPI('tickets', 'LongText', 'name'));
$site->registerAPI('/globals/costs', new AdminTicketDataTableAPI('tickets', 'CostHistory', 'year'));
$site->registerAPI('/pools', new PoolAPI());
$site->registerAPI('/requests', new RequestAPI());
$site->registerAPI('/request', new RequestAPI());
$site->registerAPI('/requests_w_tickets', new RequestWithTicketsAPI());
$site->registerAPI('/secondary', new SecondaryAPI());
$site->registerAPI('/square', new SquareAPI());
$site->registerAPI('/ticket', new TicketAPI());
$site->registerAPI('/tickets', new TicketAPI());
$site->registerAPI('/tickets_history', new TicketHistoryAPI());
$site->registerAPI('/pendingSales', new PendingPurchaseAPI());
$site->run();
/* vim: set tabstop=4 shiftwidth=4 expandtab: */
