<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketPage.php');
$page = new TicketPage('Burning Flipside - Tickets');

if(!isset($_GET['purchaseId']))
{
    $page->addNotification('Missing Purchase Id!');
    $page->printPage();
    return;
}
$purchaseId = $_GET['purchaseId'];
$dataTable = \Flipside\DataSetFactory::getDataTableByNames('tickets', 'PendingPurchases');
$filter = new \Flipside\Data\Filter("purchaseId eq '$purchaseId'");
$purchase = $dataTable->read($filter);
if(empty($purchase))
{
    $page->addNotification('Purchase missing! Did you already claim your tickets?');
    $page->printPage();
    return;
}
$purchase = $purchase[0];
$ticketIds = json_decode($purchase['ticketIds']);
$tickets = \Tickets\Ticket::get_tickets(new \Flipside\Data\Filter('hash in ('.implode(',', $ticketIds).')'));
if(empty($tickets))
{
    $page->addNotification('Tickets missing! Did you already claim your tickets?');
    $page->printPage();
    return;
}
$count = count($tickets);
for($i = 0; $i < $count; $i++)
{
    $tickets[$i]->sellTo($purchase['purchaserEmail'], false, $purchase['firstName'], $purchase['lastName']);
}

$page->addNotification('Tickets purchase successful! Click <a href="index.php" class="alert-link">here</a> to view your tickets.');
$page->printPage();

$dataTable->delete($filter);
// vim: set tabstop=4 shiftwidth=4 expandtab: