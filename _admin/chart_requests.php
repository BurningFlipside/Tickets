<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketAdminPage.php');
$page = new TicketAdminPage('Burning Flipside - Tickets');
$page->setTemplateName('admin-accordian.html');
$page->addWellKnownJS(JS_DATATABLE);
$page->addWellKnownJS(JS_CHART);
$page->addWellKnownCSS(CSS_DATATABLE);
$page->addJS('https://cdn.jsdelivr.net/gh/linways/table-to-excel@v1.0.4/dist/tableToExcel.js');
$page->addJS('js/excel.js');

$page->content['pageHeader'] = 'Request Statistics';
$page->content['panels'] = array();

array_push($page->content['panels'], array('title'=>'Request Stats', 'body'=>'
  Total Request Count: <span id="requestCount">?</span><br/>
  Received Request Count: <span id="receivedRequestCount">?</span><br/>
  Problem Request Count: <span id="problemRequestCount">?</span><br/>
  Rejected Request Count: <span id="rejectedRequestCount">?</span><br/>
  Donations Received: <span id="receivedDonations">?</span><br/>
  Total Money Received: <span id="receivedMoney">?</span><br/>
'));
array_push($page->content['panels'], array('title'=>'Request Types', 'body'=>'
  <table id="requestTypesTable" class="table">
    <thead>
      <tr><th>Type</th><th>Total Count</th><th>Received Count</th></tr>
    </thead>
    <tbody>
    </tbody>
  </table>
'));
array_push($page->content['panels'], array('title'=>'Requests over Time', 'body'=>'
  <table id="requestOverTimeTable" class="table">
    <thead>
      <tr></tr>
    </thead>
    <tbody>
      <tr><th>Total Requests</th></tr>
      <tr><th>Received</th></tr>
      <tr><th>Not Received</th></tr>
      <tr><th>Problem Requests</th></tr>
      <tr><th>Rejected Requests</th></tr>
    </tbody>
  </table>
'));

$page->printPage();
// vim: set tabstop=4 shiftwidth=4 expandtab:
?>

