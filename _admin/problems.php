<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketAdminPage.php');
require_once('class.FlipsideTicketDB.php');
$page = new TicketAdminPage('Burning Flipside - Tickets');

$page->add_js_from_src('/js/jquery.dataTables.js');
$page->add_js_from_src('js/problems.js');

$css_tag = $page->create_open_tag('link', array('rel'=>'stylesheet', 'href'=>'/css/jquery.dataTables.css', 'type'=>'text/css'), true);
$page->add_head_tag($css_tag);

$db = new FlipsideTicketDB();

$page->body .= '
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Problem Requests</h1>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <h2>Status is pending, but there are comments</h2>
            </div>
            <table class="table" id="vProblemPendingWComments">
                <thead>
                    <tr><th>Request Id</th><th>Status</th><th>Total Due</th><th>Total Received</th><th>Comments</th><th>Critical</th></tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <h2>Status is Receieved but total_due!=total_received</h2>
            </div>
            <table class="table" id="vProblemReceivedIncorrect">
                <thead>
                    <tr><th>Request Id</th><th>Status</th><th>Total Due</th><th>Total Received</th><th>Comments</th><th>Critical</th></tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <h2>Problem Status</h2>
            </div>
            <table class="table" id="vProblemStatus">
                <thead>
                    <tr><th>Request Id</th><th>Status</th><th>Total Due</th><th>Total Received</th><th>Comments</th><th>Critical</th></tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <h2>Received Money but not correct status</h2>
            </div>
            <table class="table" id="vProblemMoneyWrongStatus">
                <thead>
                    <tr><th>Request Id</th><th>Status</th><th>Total Due</th><th>Total Received</th><th>Comments</th><th>Critical</th></tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
';

$page->print_page();
// vim: set tabstop=4 shiftwidth=4 expandtab:
?>

