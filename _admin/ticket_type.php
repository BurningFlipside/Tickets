<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketAdminPage.php');
$page = new TicketAdminPage('Burning Flipside - Tickets');

    $page->body .= '
<div id="content">
    <ul id="ticket_type_nav" class="nav nav-tabs" role="tablist"></ul>
    <div id="ticket_type_content" class="tab-content"></div>
</div>
';

$page->printPage();
// vim: set tabstop=4 shiftwidth=4 expandtab:

