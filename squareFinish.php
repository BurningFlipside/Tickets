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
if($purchase['requestID'] != null && $purchase['requestID'] != '')
{
    $requestID = $purchase['requestID'];
    $request = \Tickets\Flipside\Request::getRequestByID($requestID);
    if($request === false)
    {
        $page->addNotification('Request missing! Did you already claim your tickets?');
        $page->printPage();
        return;
    }
    $request->status = 6;
    $request->private_status = 6;
    if($request->update() === false)
    {
        $page->addNotification('Failed to update request!');
        $page->printPage();
        return;
    }
}
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
$dataTable2 = \Flipside\DataSetFactory::getDataTableByNames('tickets', 'CompletedCCSales');
unset($purchase['issuedAt']);
$settings = \Tickets\DB\TicketSystemSettings::getInstance();
$year = $settings['year'];
$purchase['year'] = $year;
$ret = $dataTable2->create($purchase);
if($ret === false)
{
    $page->addNotification('Failed to save purchase!');
    $page->printPage();
    return;
}

$dataTable->delete($filter);
// vim: set tabstop=4 shiftwidth=4 expandtab: