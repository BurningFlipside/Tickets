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
                <h1 class="page-header">Critical Volunteers</h1>
            </div>
        </div>
        <div class="row">
            <div class="accordion">
                <div class="card">
                    <div class="card-header">
                        <h4 class="panel-title"><a data-toggle="collapse" data-parent="#accordion" href="#single">Single Critical Volunteer</a></h4>
                    </div>
                    <div id="single" class="collapse show">
                        <div class="card-body">
                            <form class="form-inline" role="form">
                                Search Type: <select id="search_type" name="search_type" class="form-control">
                                    <option value="*">All</option>
                                    <option value="request_id">Request ID</option>
                                    <option value="mail">Email</option>
                                    <option value="givenName">First Name</option>
                                    <option value="sn">Last Name</option>
                                </select>
                                <input type="text" id="search" name="search" class="form-control"/>
                                <button class="btn btn-default" id="search_btn">Search</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <h4 class="panel-title"><a data-toggle="collapse" data-parent="#accordion" href="#auto">Automatic Critical Volunteers</a></h4>
                    </div>
                    <div id="auto" class="collapse">
                        <div class="panel-body">
                            <p>This option will automatically set all members of the AAR, AF, and Lead Groups to Critvol status.</p>
                            <button class="btn btn-default" onclick="auto_critvol()">Automatically Set Critvols</button>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <h4 class="panel-title"><a data-toggle="collapse" data-parent="#accordion" href="#bulk">Bulk Critical Volunteers</a></h4>
                    </div>
                    <div id="bulk" class="collapse">
                        <div class="panel-body">
                            <p>You can upload a file with a different request ID or email address seperated by new lines or commas. Each request will then be set to crit vol status. A summary of the actions taken both requests that were changed and requests that were not will be printed to the screen when it is done.</p>
                            <div id="filehandler" style="border: 2px dotted #0B85A1; width: 400px; color: #92AAB0; text-align:left; vertical-align:middle; font-size: 2em;">
                                Drag and Drop files here
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal fade" id="result_dialog">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                                <h4 class="modal-title">Critical Volunteers Result</h4>
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
?>

