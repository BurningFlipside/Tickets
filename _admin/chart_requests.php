<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketAdminPage.php');
require_once('class.FlipsideTicketDB.php');
$page = new TicketAdminPage('Burning Flipside - Tickets');

$page->add_js(JS_DATATABLE);
$page->add_js(JS_CHART);
$page->add_css(CSS_DATATABLE);
$page->add_js_from_src('js/chart_requests.js');

    $db = new FlipsideTicketDB();
    $request_count = $db->getRequestCount();
    $tickets = $db->getRequestedTickets();
    $requested_ticket_count = 0;
    for($i = 0; $i < count($tickets); $i++)
    {
        $requested_ticket_count += $tickets[$i]['count'];
    }

    $page->body .= '
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Request Graphs</h1>
            </div>
        </div>
        <div class="row">
            <div class="panel-group" id="accordion">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="panel-title"><a data-toggle="collapse" data-parent="#accordion" href="#requestTypes">Request Types</a></h4>
                    </div>
                    <div id="requestTypes" class="panel-collapse collapse in">
                        <div class="panel-body">
                            <canvas id="request_type_chart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="panel-title"><a data-toggle="collapse" data-parent="#accordion" href="#crits">Crit Vols/Protected</a></h4>
                    </div>
                    <div id="crits" class="panel-collapse collapse">
                        <div class="panel-body">
                            <canvas id="crits_chart"></canvas>
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

