<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketAdminPage.php');
$page = new TicketAdminPage('Burning Flipside - Tickets');
$page->setTemplateName('admin-accordian.html');
$page->addWellKnownJS(JS_DATATABLE, false);
$page->addWellKnownCSS(CSS_DATATABLE);
$page->addJS('https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js');

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
array_push($page->content['panels'], array('title'=>'Credit Card Sales this Year', 'body'=>'
  <canvas id="ccSales"></canvas>
'));

$page->printPage();
// vim: set tabstop=4 shiftwidth=4 expandtab: