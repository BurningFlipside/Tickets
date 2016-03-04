<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketAdminPage.php');
$page = new TicketAdminPage('Burning Flipside - Tickets');

$page->add_js(JS_DATATABLE);
$page->add_js(JS_CHART);
$page->add_css(CSS_DATATABLE);
$page->add_js_from_src('js/chart_requests.js');

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
                        <h4 class="panel-title"><a data-toggle="collapse" data-parent="#accordion" href="#crits">Crit Vols/Protected</a></h4>
                    </div>
                    <div id="crits" class="panel-collapse collapse">
                        <div class="panel-body">
                            <table id="critVolTable" class="table">
                                <thead>
                                    <tr><th></th><th>Non-Critvol & Non-Protected</th><th>Critvol</th><th>Protected</th><th>Critvol & Protected</th></tr>
                                </thead>
                                <tbody>
                                    <tr><th>Number</th></tr>
                                    <tr><th>Percentage</th></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="panel-title"><a data-toggle="collapse" data-parent="#accordion" href="#ticket_counts">Tickets per Request</a></h4>
                    </div>
                    <div id="ticket_counts" class="panel-collapse collapse">
                        <div class="panel-body">
                            <canvas id="ticket_count_chart"></canvas>
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

