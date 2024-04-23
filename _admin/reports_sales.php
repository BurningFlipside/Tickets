<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketAdminPage.php');
$page = new TicketAdminPage('Burning Flipside - Tickets');
$page->setTemplateName('admin-accordian.html');
$page->addWellKnownJS(JS_DATATABLE, false);
$page->addWellKnownCSS(CSS_DATATABLE);

$page->content['pageHeader'] = 'Sales Reports';
$page->content['panels'] = array();

array_push($page->content['panels'], array('title'=>'Sales over Time', 'body'=>'
  <table id="salesOverTimeTable" class="table">
    <thead>
      <tr></tr>
    </thead>
    <tbody>
      <tr><th>Gross Sales</th></tr>
      <tr><th>Net Sales</th></tr>
    </tbody>
  </table>
'));

$page->printPage();
// vim: set tabstop=4 shiftwidth=4 expandtab: