<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketAdminPage.php');
$page = new TicketAdminPage('Burning Flipside - Tickets');

$page->addWellKnownJS(JS_BOOTBOX);
$page->addWellKnownJS(JS_TYPEAHEAD, false);

$page->body .= '
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Gate Control</h1>
            </div>
        </div>
        <div class="row">
            <div class="form-group">
                <label for="show_short_code" class="col-sm-2 control-label">Current Entry Type:</label>
                <div class="col-sm-10">
                    <select class="form-control" name="currentEarlyEntry" id="currentEarlyEntry"></select>
                </div>
            </div>
            <div class="clearfix visible-sm visible-md visible-lg"></div>
        </div>
';

$page->printPage();
// vim: set tabstop=4 shiftwidth=4 expandtab:

