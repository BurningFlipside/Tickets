<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketAdminPage.php');
$page = new TicketAdminPage('Burning Flipside - Tickets');
$page->setTemplateName('admin-accordian.html');
$page->addWellKnownJS(JS_CHART);
$page->addWellKnownCSS(CSS_DATATABLE);

$page->content['pageHeader'] = 'Ticket Graphs';
$page->content['panels'] = array();

array_push($page->content['panels'], array('title'=>'Ticket Types', 'body'=>'
  <canvas id="ticket_type_chart" height="150" width="300" style="width: 300px; height: 150px;"></canvas>
'));

$page->printPage();
// vim: set tabstop=4 shiftwidth=4 expandtab:
?>

