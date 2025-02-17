<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketAdminPage.php');
$page = new TicketAdminPage('Burning Flipside - Tickets');

$page->addWellKnownJS(JS_TABULATOR, false);
$page->addWellKnownCSS(CSS_TABULATOR);

$page->body .= '
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Invalid Requests <a href="#" onclick="exportCSV()"><i class="fa fa-file-csv"></i></a></h1>
            </div>
        </div>
        <div class="row">
            <table class="table" id="invalid">
                <thead>
                    <tr><th>Request ID</th><th>Status</th><th>Total Due</th><th>Total Received</th><th>Comments</th><th>Critical</th></tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
';

$page->printPage();
// vim: set tabstop=4 shiftwidth=4 expandtab:

