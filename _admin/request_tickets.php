<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketAdminPage.php');
$page = new TicketAdminPage('Burning Flipside - Tickets');

$page->addWellKnownJS(JS_DATATABLE, false);
$page->addWellKnownCSS(CSS_DATATABLE);
$page->addJSByURI('js/request_tickets.js');

$page->body .= '
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Requested Tickets</h1>
            </div>
        </div>
        <div class="row">
            Request Year: <select id="year" onchange="change_year(this)">
            </select>
            <table class="table" id="tickets">
                <thead>
                    <th>Request ID</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Type</th>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>
';

$page->print_page();
// vim: set tabstop=4 shiftwidth=4 expandtab:

