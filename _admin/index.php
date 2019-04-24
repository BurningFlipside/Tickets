<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketAdminPage.php');
$page = new TicketAdminPage('Burning Flipside - Tickets');
$page->setTemplateName('admin-dashboard.html');

$data_set = DataSetFactory::getDataSetByName('tickets');
$data_table = $data_set['Problems'];
$settings = \Tickets\DB\TicketSystemSettings::getInstance();
$year = $settings['year'];

$yearFilter = new \Data\Filter('year eq '.$year);

$ticketDataTable = \Tickets\DB\TicketsDataTable::getInstance();
$issuedTicketCount = $ticketDataTable->count($yearFilter);

if($issuedTicketCount == 0)
{
    $page->addCard('fa-file', '<div id="requestCount">?</div> Ticket Requests', 'requests.php');
    $page->addCard('fa-tag',  '<div id="requestedTicketCount">?</div> Requested Tickets', 'request_tickets.php', $page::CARD_GREEN);
}
$page->addCard('fa-fire', $data_table->count($yearFilter).' Problem Requests', 'problems.php', $page::CARD_RED);
$page->addCard('fa-dollar-sign',  '<div id="soldTicketCount">?</div> Sold Tickets', 'tickets.php?sold=1', $page::CARD_YELLOW);
if($issuedTicketCount != 0)
{
    $page->addCard('fa-ticket-alt', '<div id="unsoldCount">?</div> Unsold Tickets', 'tickets.php?sold=0');
    $page->addCard('fa-check', '<div id="usedCount">?</div> Used Tickets', 'tickets.php?used=1', $page::CARD_GREEN);
}
/*
$secondaryTable = \DataSetFactory::getDataTableByNames('tickets', 'SecondaryRequests');
$secondaryTotalCount = 0;
$validTicketArray = $secondaryTable->read(false, array('valid_tickets'));
$proccessedTicketArray = $secondaryTable->read(new \Data\Filter('ticketed eq 1'), array('valid_tickets'));
$validTicketArrayCount = count($validTicketArray);
for($i = 0; $i < $validTicketArrayCount; $i++)
{
    $tmp = json_decode($validTicketArray[$i]['valid_tickets']);
    $secondaryTotalCount += count($tmp);
}
$page->add_card('fa-ticket', '<div>'.$secondaryTotalCount.'</div>', 'Requested Secondary Tickets', 'secondary.php');


$secondaryTotalCount = 0;
$proccessedTicketArrayCount = count($proccessedTicketArray);
for($i = 0; $i < $proccessedTicketArrayCount; $i++)
{
    $tmp = json_decode($proccessedTicketArray[$i]['valid_tickets']);
    $secondaryTotalCount += count($tmp);
}
$page->add_card('fa-ticket', '<div>'.$secondaryTotalCount.'</div>', 'Processed Secondary Tickets', 'secondary.php', $page::CARD_GREEN);
$page->body.='</div>';
*/

$page->printPage();
// vim: set tabstop=4 shiftwidth=4 expandtab:

