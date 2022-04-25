<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketAdminPage.php');
$page = new TicketAdminPage('Burning Flipside - Tickets');

$page->addWellKnownJS(JS_DATATABLE, false);
$page->addWellKnownJS(JS_BOOTSTRAP_FH);
$page->addWellKnownCSS(CSS_DATATABLE);
$page->addWellKnownCSS(CSS_BOOTSTRAP_FH);

$page->body .= '
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Discretionary Management <a onclick="getCSV();" title="Export CSV"><i class="fa fa-file-excel-o"></i></a></h1>
            </div>
        </div>
        <div class="row">
            <table class="table" id="discretionary">
                <thead>
                    <th>Holder</th>
                    <th>Ticket Type</th>
                    <th>Unsold</th>
                    <th>Sold</th>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
        <div class="row">
        Assign <input type="number" name="count" id="count"/> discretionary tickets to everyone in <select name="group" id="group"></select>. <button class="btn btn-primary" onclick="assignTickets();">Assign</button>
        </div>
    </div>
</div>
';

$page->printPage();
// vim: set tabstop=4 shiftwidth=4 expandtab:

