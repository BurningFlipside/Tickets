<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketAdminPage.php');
$page = new TicketAdminPage('Burning Flipside - Tickets');

$page->body .= '
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Status Management</h1>
            </div>
        </div>
        <div class="row">
            <table class="table" id="statues">
                <thead>
                    <th>Status</th>
                    <th>Count</th>
                    <th>Actions</th>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
';

$page->printPage();
// vim: set tabstop=4 shiftwidth=4 expandtab:

