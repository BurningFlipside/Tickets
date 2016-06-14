<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketAdminPage.php');
$page = new TicketAdminPage('Burning Flipside - Tickets');

$page->addWellKnownJS(JS_DATATABLE);
$page->addWellKnownCSS(CSS_DATATABLE);
$page->addJSByURI('js/unsold_tickets.js');

$page->body .= '
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Unsold Tickets</h1>
            </div>
        </div>
        <div class="row">
            <table class="table" id="tickets">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Type</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
';

$page->print_page();
// vim: set tabstop=4 shiftwidth=4 expandtab:

