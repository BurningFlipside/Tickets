<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketAdminPage.php');
$page = new TicketAdminPage('Burning Flipside - Tickets');

$page->addWellKnownJS(JS_BOOTBOX);

$page->body .= '
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Early Entry Pass Management</h1>
            </div>
        </div>
        <div class="row">
            <table class="table" id="passTypes">
                <thead>
                    <th></th>
                    <th>Type</th>
                    <th>Count</th>
                    <th>Used Count</th>
                    <th>Assigned Count</th>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>';
if($page->user !== false && $page->user !== null && $page->user->isInGroupNamed('AAR'))
{
    $page->body .= '
        <div class="row">
            <button class="btn btn-primary" onclick="addEEFromGoogleSheet();"><i class="fab fa-google-drive"></i> Assign Early Entry from Google Spreadsheet</button>
        </div>';
}

$page->printPage();
// vim: set tabstop=4 shiftwidth=4 expandtab:

