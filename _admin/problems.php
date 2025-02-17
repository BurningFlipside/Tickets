<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketAdminPage.php');
$page = new TicketAdminPage('Burning Flipside - Tickets');

$page->addWellKnownJS(JS_TABULATOR, false);
$page->addWellKnownCSS(CSS_TABULATOR);

$page->body .= '
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Problem Requests <a href="#" onclick="exportCSV()"><i class="fa fa-file-excel-o"></i></a></h1>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <h2>Status is pending, but there are comments <a href="#" onclick="exportCSV(\'vProblemPendingWComments\')"><i class="fas fa-file-csv"></i></a></h2>
            </div>
            <table class="table" id="vProblemPendingWComments">
                <thead>
                    <tr><th>Request Id</th><th>Status</th><th>Total Due</th><th>Total Received</th><th>Comments</th><th>Bucket</th><th>Critical</th></tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <h2>Status is Receieved but total_due!=total_received <a href="#" onclick="exportCSV(\'vProblemReceivedIncorrect\')"><i class="fas fa-file-csv"></i></a></h2>
            </div>
            <table class="table" id="vProblemReceivedIncorrect">
                <thead>
                    <tr><th>Request Id</th><th>Status</th><th>Total Due</th><th>Total Received</th><th>Comments</th><th>Bucket</th><th>Critical</th></tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <h2>Problem Status <a href="#" onclick="exportCSV(\'vProblemStatus\')"><i class="fas fa-file-csv"></i></a></h2>
            </div>
            <table class="table" id="vProblemStatus">
                <thead>
                    <tr><th>Request Id</th><th>Status</th><th>Total Due</th><th>Total Received</th><th>Comments</th><th>Bucket</th><th>Critical</th></tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <h2>Received Money but not correct status <a href="#" onclick="exportCSV(\'vProblemMoneyWrongStatus\')"><i class="fas fa-file-csv"></i></a></h2>
            </div>
            <table class="table" id="vProblemMoneyWrongStatus">
                <thead>
                    <tr><th>Request Id</th><th>Status</th><th>Total Due</th><th>Total Received</th><th>Comments</th><th>Bucket</th><th>Critical</th></tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <h2>Status is Received, but there are comments <a href="#" onclick="exportCSV(\'vProblemReceivedWComments\')"><i class="fas fa-file-csv"></i></a></h2>
            </div>
            <table class="table" id="vProblemReceivedWComments">
                <thead>
                    <tr><th>Request Id</th><th>Status</th><th>Total Due</th><th>Total Received</th><th>Comments</th><th>Bucket</th><th>Critical</th></tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <h2>Status is Not Received, but there is a bucket assigned <a href="#" onclick="exportCSV(\'vProblemBucketNotRecieved\')"><i class="fas fa-file-csv"></i></a></h2>
            </div>
            <table class="table" id="vProblemBucketNotRecieved">
                <thead>
                    <tr><th>Request Id</th><th>Status</th><th>Total Due</th><th>Total Received</th><th>Comments</th><th>Bucket</th><th>Critical</th></tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
';

$page->printPage();
// vim: set tabstop=4 shiftwidth=4 expandtab:

