<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketAdminPage.php');
require_once('class.FlipsideTicketDB.php');
$page = new TicketAdminPage('Burning Flipside - Tickets');

$page->add_js_from_src('js/index.js');

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
                <h1 class="page-header">Dashboard</h1>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-3 col-md-6">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <div class="row">
                            <div class="col-xs-3">
                                <span class="glyphicon glyphicon-file" style="font-size: 5em;"></span>                                
                            </div>
                            <div class="col-xs-9 text-right">
                                <div style="font-size: 40px;">'.$request_count.'</div>
                                <div>Ticket Requests</div>
                            </div>
                        </div>
                    </div>
                    <a href="requests.php">
                        <div class="panel-footer">
                            <span class="pull-left">View Details</span>
                            <span class="pull-right glyphicon glyphicon-circle-arrow-right"></span>
                            <div class="clearfix"></div>
                        </div>
                    </a>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="panel panel-green">
                    <div class="panel-heading">
                        <div class="row">
                            <div class="col-xs-3">
                                <span class="glyphicon glyphicon-tag" style="font-size: 5em;"></span>
                            </div>
                            <div class="col-xs-9 text-right">
                                <div style="font-size: 40px;">'.$requested_ticket_count.'</div>
                                <div>Requested Tickets</div>
                            </div>
                        </div>
                    </div>
                    <a href="request_tickets.php">
                        <div class="panel-footer">
                            <span class="pull-left">View Details</span>
                            <span class="pull-right glyphicon glyphicon-circle-arrow-right"></span>
                            <div class="clearfix"></div>
                        </div>
                    </a>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="panel panel-red">
                    <div class="panel-heading">
                        <div class="row">
                            <div class="col-xs-3">
                                <span class="glyphicon glyphicon-fire" style="font-size: 5em;"></span>
                            </div>
                            <div class="col-xs-9 text-right">
                                <div style="font-size: 40px;">'.$db->getProblemRequestCount().'</div>
                                <div>Problem Requests</div>
                            </div>
                        </div>
                    </div>
                    <a href="problems.php">
                        <div class="panel-footer">
                            <span class="pull-left">View Details</span>
                            <span class="pull-right glyphicon glyphicon-circle-arrow-right"></span>
                            <div class="clearfix"></div>
                        </div>
                    </a>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="panel panel-yellow">
                    <div class="panel-heading">
                        <div class="row">
                            <div class="col-xs-3">
                                <span class="glyphicon glyphicon-usd" style="font-size: 5em;"></span>
                            </div>
                            <div class="col-xs-9 text-right">
                                <div style="font-size: 40px;">'.$db->getTicketSoldCount().'</div>
                                <div>Sold Tickets</div>
                            </div>
                        </div>
                    </div>
                    <a href="sold_tickets.php">
                        <div class="panel-footer">
                            <span class="pull-left">View Details</span>
                            <span class="pull-right glyphicon glyphicon-circle-arrow-right"></span>
                            <div class="clearfix"></div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
';

$page->print_page();
// vim: set tabstop=4 shiftwidth=4 expandtab:
?>

