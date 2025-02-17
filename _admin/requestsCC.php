<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketAdminPage.php');
$page = new TicketAdminPage('Burning Flipside - Tickets');
$page->setTemplateName('admin-table.html');
$page->addWellKnownJS(JS_BOOTSTRAP_FH);
$page->addWellKnownCSS(CSS_BOOTSTRAP_FH);
$page->addWellKnownJS(JS_TABULATOR, false);
$page->addWellKnownCSS(CSS_TABULATOR);
$page->addWellKnownJS(JS_BOOTBOX);
$page->addAsyncJS('//oss.sheetjs.com/sheetjs/xlsx.full.min.js', 'excelLoaded()');

$page->content['pageHeader'] = 'Credit Card Requests';
$page->content['selectors'] = '
  <label for="year" class="col-sm-2 control-label">Request Year:</label>
  <div class="col-sm-4">
    <select id="year" class="form-control">
      <option value="*">All</option>
    </select>
  </div>
  <label for="statusFilter" class="col-sm-2 control-label">Request Status:</label>
  <div class="col-sm-4">
    <select id="statusFilter" class="form-control">
        <option value="*">All</option>
        <option value="sold">Sold</option>
        <option value="pending">Link Unissued or Expired</option>
        <option value="issued">Link Issued</option>
    </select>
  </div>
  <div class="d-grid gap-2 d-md-block">
    <button id="csv" class="btn btn-link btn-sm" onclick="getCSV();" title="Export CSV"><i class="fa fa-file-csv"></i></button>
  </div>
  <div class="d-grid gap-2 d-md-block" id="globalIssuePlaceholder">
  </div>
';
$page->content['table'] = array('id' => 'requests', 'headers'=>array('Request ID', 'First Name', 'Last Name', 'Email', 'Total Due', 'Link Status', 'Link'));

$page->printPage();
// vim: set tabstop=4 shiftwidth=4 expandtab: