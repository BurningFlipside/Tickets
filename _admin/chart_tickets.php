<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketAdminPage.php');
require_once('class.FlipsideTicketDB.php');
$page = new TicketAdminPage('Burning Flipside - Tickets');

$page->add_js(JS_DATATABLE);
$page->add_js(JS_CHART);
$page->add_css(CSS_DATATABLE);
$page->add_js_from_src('js/chart_tickets.js');

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
                <h1 class="page-header">Ticket Graphs</h1>
            </div>
        </div>
        <div class="row">
            <div class="panel-group" id="accordion">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="panel-title"><a data-toggle="collapse" data-parent="#accordion" href="#requestTypes">Ticket Types</a></h4>
                    </div>
                    <div id="ticketTypes" class="panel-collapse collapse in">
                        <div class="panel-body">
                            <canvas id="ticket_type_chart" height="150" width="300" style="width: 300px; height: 150px;"></canvas>
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

