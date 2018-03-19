<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketAdminPage.php');
$page = new TicketAdminPage('Burning Flipside - Tickets');

$page->addWellKnownJS(JS_DATATABLE);
$page->addWellKnownJS(JS_CHART);
$page->addWellKnownCSS(CSS_DATATABLE);
$page->addJSByURI('js/chart_requests.js');

$page->body .= '
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Request Statistics</h1>
            </div>
        </div>
        <div class="row">
            <div class="panel-group" id="accordion">
                <div class="panel panel-default">
                     <div class="panel-heading">
                        <h4 class="panel-title"><a data-toggle="collapse" data-parent="#accordion" href="#requestStats">Request Stats</a></h4>
                    </div>
                    <div id="requestStats" class="panel-collapse collapse in">
                        <div class="panel-body">
                            Total Request Count: <span id="requestCount">?</span><br/>
                            Received Request Count: <span id="receivedRequestCount">?</span><br/>
                            Problem Request Count: <span id="problemRequestCount">?</span><br/>
                            Rejected Request Count: <span id="rejectedRequestCount">?</span><br/>
                            Donations Received: <span id="receivedDonations">?</span><br/>
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="panel-title"><a data-toggle="collapse" data-parent="#accordion" href="#requestTypes">Request Types</a></h4>
                    </div>
                    <div id="requestTypes" class="panel-collapse collapse">
                        <div class="panel-body">
                            <table id="requestTypesTable" class="table">
                                <thead>
                                    <tr><th>Type</th><th>Total Count</th><th>Received Count</th></tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="panel-title"><a data-toggle="collapse" data-parent="#accordion" href="#requestsOverTime">Requests over Time</a></h4>
                    </div>
                    <div id="requestsOverTime" class="panel-collapse collapse">
                        <div class="panel-body">
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
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
';

$page->print_page();
// vim: set tabstop=4 shiftwidth=4 expandtab:
?>

