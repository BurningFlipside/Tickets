<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketAdminPage.php');
$page = new TicketAdminPage('Burning Flipside - Tickets');

$page->addWellKnownJS(JS_DATATABLE, false);
$page->addWellKnownCSS(CSS_DATATABLE);

$page->body .= '
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Data Entry Users</h1>
            </div>
        </div>
        <div class="row">
            <div class="panel-group" id="accordion">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="panel-title"><a data-toggle="collapse" data-parent="#accordion" href="#current">Current Users</a></h4>
                    </div>
                    <div id="current" class="panel-collapse collapse in">
                        <div class="panel-body">
                            <table class="table" id="users">
                                <thead>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>User ID</th>
                                    <th>Admin</th>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="panel-title"><a data-toggle="collapse" data-parent="#accordion" href="#bulk">Bulk Data Entry</a></h4>
                    </div>
                    <div id="bulk" class="panel-collapse collapse">
                        <div class="panel-body">
                            <p>You can upload a file with a different email address seperated by new lines or commas. Each user will then be set to data entry status. A summary of the actions taken both users that were changed and users that were not will be printed to the screen when it is done.</p>
                            <div id="filehandler" style="border: 2px dotted #0B85A1; width: 400px; color: #92AAB0; text-align:left; vertical-align:middle; font-size: 2em;">
                                Drag and Drop files here
                            </div>
                            <textarea id="bulk_text" placeholder="Or you can enter emails here" rows=10 cols=40></textarea><br/>
                            <button class="btn btn-default" onclick="process_bulk()">Process</button>
                        </div>
                    </div>
                </div>
                <div class="modal fade" id="result_dialog">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                                <h4 class="modal-title">Data Entry Result</h4>
                            </div>
                            <div class="modal-body">
                                <p class="bg-success">
                                    <font style="font-size: 1.5em;">Successful Results: <span id="success_count"></span></font><br/>
                                    <span id="successes"></span>
                                </p>
                                <p class="bg-danger">
                                    <font style="font-size: 1.5em;">Failure Results: <span id="fail_count"></span></font><br/>
                                    <span id="failures"></span>
                                </p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
';

$page->printPage();
// vim: set tabstop=4 shiftwidth=4 expandtab:

