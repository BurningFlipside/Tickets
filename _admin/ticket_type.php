<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketAdminPage.php');
$page = new TicketAdminPage('Burning Flipside - Tickets');

$page->add_js_from_src('js/ticket_type.js');

    $page->body .= '
<div id="content">
    <ul id="ticket_type_nav" class="nav nav-tabs" role="tablist">
    </ul>

    <div id="ticket_type_content" class="tab-content">
    </div>
</div>
';

$page->print_page();
// vim: set tabstop=4 shiftwidth=4 expandtab:
?>

