<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketAdminPage.php');
require_once('class.FlipsideTicketDB.php');
$page = new TicketAdminPage('Burning Flipside - Tickets');

$page->add_js_from_src('js/index.js');

$db = new FlipsideTicketDB();
$tickets = $db->getRequestedTickets();
$requested_ticket_count = 0;
for($i = 0; $i < count($tickets); $i++)
{
    $requested_ticket_count += $tickets[$i]['count'];
}

$page->body .= '
<div class="row">
    <div class="col-lg-12">
        <h1 class="page-header">Dashboard</h1>
    </div>
</div>
<div class="row">';

$page->add_card('fa-file', '<div id="requestCount">?</div>', 'Ticket Requests', 'requests.php');
$page->add_card('fa-tag',  '<div id="requestedTicketCount">?</div>', 'Requested Tickets', 'request_tickets.php', $page::CARD_GREEN);
$page->add_card('fa-fire', $db->getProblemRequestCount(), 'Problem Requests', 'problems.php', $page::CARD_RED);
$page->add_card('fa-usd',  $db->getTicketSoldCount(), 'Sold Tickets', 'sold_tickets.php', $page::CARD_YELLOW);
$page->body.='</div>';

$page->print_page();
// vim: set tabstop=4 shiftwidth=4 expandtab:
?>

