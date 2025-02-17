<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketAdminPage.php');
$page = new TicketAdminPage('Burning Flipside - Tickets');
$page->setTemplateName('admin-table.html');
$page->addWellKnownJS(JS_TABULATOR, false);
$page->addWellKnownCSS(CSS_TABULATOR);

$page->content['pageHeader'] = 'Requested Tickets';
$page->content['selectors'] = '
  <label for="year" class="col-sm-2 control-label">Request Year:</label>
  <div class="col-sm-4">
    <select id="year" class="form-control" onchange="changeYear(this)"></select>
  </div>
';
$page->content['table'] = array('id' => 'tickets', 'headers'=>array('Request ID', 'First Name', 'Last Name', 'Type'));

$page->printPage();
// vim: set tabstop=4 shiftwidth=4 expandtab:

