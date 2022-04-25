<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketAdminPage.php');
$page = new TicketAdminPage('Burning Flipside - Tickets');
$page->setTemplateName('admin-accordian.html');
$page->addWellKnownJS(JS_CHART);
$page->addWellKnownCSS(CSS_DATATABLE);
$page->addJS('https://cdn.jsdelivr.net/gh/linways/table-to-excel@v1.0.4/dist/tableToExcel.js');
$page->addJS('js/excel.js');

$page->content['pageHeader'] = 'Ticket Graphs';
$page->content['panels'] = array();

array_push($page->content['panels'], array('title'=>'Tickets Sold', 'body'=>'
  <table id="ticketsSold" class="table">
    <thead>
      <tr></tr>
    </thead>
    <tbody>
      <tr><th>Original Sale</th></tr>
      <tr><th>Critical Volunteer</th></tr>
      <tr><th>Secondary Sale</th></tr>
      <tr><th>Discretionary</th></tr>
      <tr><th>Other Pools</th></tr>
      <tr><th>Total Tickets</th></tr>
    </tbody>
  </table> 
'));
array_push($page->content['panels'], array('title'=>'Tickets Sold Bar Chart', 'body'=>'
  <canvas id="ticket_sold_chart" height="150" width="300" style="width: 300px; height: 150px;"></canvas>
'));
array_push($page->content['panels'], array('title'=>'Ticket Types', 'body'=>'
  <canvas id="ticket_type_chart" height="150" width="300" style="width: 300px; height: 150px;"></canvas>
'));

$page->printPage();
// vim: set tabstop=4 shiftwidth=4 expandtab:
?>

