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

$page->content['pageHeader'] = 'Gate Graphs';
$page->content['panels'] = array();

array_push($page->content['panels'], array('title'=>'Used/Unused', 'body'=>'
  <canvas id="used_chart" height="150" width="300" style="width: 300px; height: 150px;"></canvas>
'));
array_push($page->content['panels'], array('title'=>'Used/Unused over time', 'body'=>'
  <table id="ticketsUsed" class="table">
    <thead>
      <tr></tr>
    </thead>
    <tbody>
      <tr><th>Used</th></tr>
      <tr><th>Unused</th></tr>
      <tr><th>Percentage Unused</th></tr>
    </tbody>
  </table>
'));

$page->printPage();
// vim: set tabstop=4 shiftwidth=4 expandtab:
?>

