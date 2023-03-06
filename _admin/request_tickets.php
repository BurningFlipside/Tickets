<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketAdminPage.php');
$page = new TicketAdminPage('Burning Flipside - Tickets');
$page->setTemplateName('admin-table.html');
$page->addWellKnownJS(JS_DATATABLE, false);
$page->addWellKnownCSS(CSS_DATATABLE);

$page->content['pageHeader'] = 'Requested Tickets';
$page->content['selectors'] = '
  Request Year: <select id="year" onchange="changeYear(this)"></select>
';
$page->content['table'] = array('id' => 'tickets', 'headers'=>array('Request ID', 'First Name', 'Last Name', 'Type'));

$page->printPage();
// vim: set tabstop=4 shiftwidth=4 expandtab:

