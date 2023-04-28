<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketPage.php');
$page = new TicketPage('Burning Flipside - Tickets');

$page->addWellKnownJS(JS_DATATABLE, false);
$page->addWellKnownCSS(CSS_DATATABLE);
$page->addJS('js/index.js', false);

$discretionary = '';
if($page->user !== false && $page->user !== null && ($page->user->isInGroupNamed('AAR') || $page->user->isInGroupNamed('AFs')))
{
    $page->addJS('js/discretionary.js');
    $discretionary = '
        <div class="row" id="discretionary_set" style="display: none;">
            <div class="col-sm-1"></div>
            <div class="col-sm-10">
                <div class="card">
                    <div class="card-header" id="discretionaryHeader">
                      <button class="btn btn-light" type="button" data-toggle="collapse" data-target="#discretionaryDiv" aria-expanded="true" aria-controls="discretionaryDiv" style="width: 100%">
                        <span class="float-left">Discretionary Tickets</span><span class="float-right"><i class="fa fa-chevron-up"></i></span>
                      </button>
                    </div>
                    <div id="discretionaryDiv" class="collapse show" aria-labelledby="discretionaryHeader">
                      <div class="card-body">
                        <table id="discretionary" class="table">
                            <thead>
                                <tr>
                                    <th>First Name</th>
                                    <th>Last Name</th>
                                    <th>Type</th>
                                    <th>Short Ticket Code</th>
                                    <th></th>
                                </tr>
                            </thead>
                        </table>
                      </div>
                    </div>
                </div>
            </div>
        </div>';
}
    $page->body .= '
<div id="content">
    <div class="row" id="request_set" style="display: none;">
        <div class="col-sm-1"></div>
        <div class="col-sm-10">
            <div class="card">
                <div class="card-header" id="requestHeader">
                  <button class="btn btn-light" type="button" data-toggle="collapse" data-target="#request" aria-expanded="true" aria-controls="request" style="width: 100%">
                    <span class="float-left">Ticket Request</span><span class="float-right"><i class="fa fa-chevron-up"></i></span>
                  </button>
                </div>
                <div id="request" class="collapse show" aria-labelledby="requestHeader">
                  <div class="card-body">
                    <table id="requestList" class="table">
                        <thead>
                            <tr>
                                <th>Request ID</th>
                                <th>Request Year</th>
                                <th>Number of Tickets</th>
                                <th>Amount Due</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                    <a href="request.php" id="fallback">Create a new ticket request</a>
                  </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row" id="ticket_set" style="display: none;">
        <div class="col-sm-1"></div>
        <div class="col-sm-10">
            <div class="card">
                <div class="card-header" id="ticketHeader">
                  <button class="btn btn-light" type="button" data-toggle="collapse" data-target="#ticket" aria-expanded="true" aria-controls="ticket" style="width: 100%">
                    <span class="float-left">Tickets</span><span class="float-right"><i class="fa fa-chevron-up"></i></span>
                  </button>
                </div>
                <div id="ticket" class="collapse show" aria-labelledby="ticketHeader">
                  <div class="card-body">
                    <table id="ticketList" class="table">
                        <thead>
                            <tr>
                                <th>First Name</th>
                                <th>Last Name</th>
                                <th>Type</th>
                                <th>Short Ticket Code</th>
                                <th></th>
                            </tr>
                        </thead>
                    </table>
                    <a href="transfer.php">Transfer Tickets</a> | <a href="verify.php">Verify Tickets</a>
                  </div>
                </div>
            </div>
        </div>
    </div>
    '.$discretionary.'
    <div class="row">
        <div class="col-sm-1"></div>
        <div class="col-sm-10">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">FAQ</h3>
                </div>
                <div class="panel-body">
                    <a href="'.$page->wwwUrl.'/event/tickets/faq">Ticket FAQ</a>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade in" aria-hidden="false" id="ticket_id_modal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                      <h4 class="modal-title" id="modal_title">Full Ticket ID</h4>
                      <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info" role="alert"><strong>Notice: </strong>Providing this ID to another person is as good as handing them your ticket. The Flipside Ticket Team will never ask for this information. Only provide this code to someone whom you are selling or giving the ticket to!</div>
                        Long Ticket ID: <div class="well" id="long_id"></div><br/>
                        Long Ticket ID (Word Method): <div class="well" id="long_id_words"></div><br/>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Close</button></div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade in" aria-hidden="false" id="ticket_view_modal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                      <h4 class="modal-title" id="modal_title">Ticket Information</h4>
                      <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                    </div>
                    <div class="modal-body">
                        First Name: <span id="view_first_name"></span><br/>
                        Last Name: <span id="view_last_name"></span><br/>
                        Type: <span id="view_type"></span><br/>
                        Short Code: <a href="#" id="view_short_code"></a><br/>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Close</button></div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade in" aria-hidden="false" id="ticket_edit_modal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                      <h4 class="modal-title" id="modal_title">Edit Ticket</h4>
                      <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <label for="show_short_code" class="col-sm-2 control-label">Short Code:</label>
                            <div class="col-sm-10">
                                <input class="form-control" type="text" name="show_short_code" id="show_short_code" readonly/>
                            </div>
                        </div>
                        <div class="row">
                            <label for="edit_first_name" class="col-sm-2 control-label">First Name:</label>
                            <div class="col-sm-10">
                                <input class="form-control" type="text" name="edit_first_name" id="edit_first_name"/>
                            </div>
                        </div>
                        <div class="row">
                            <label for="edit_last_name" class="col-sm-2 control-label">Last Name:</label>
                            <div class="col-sm-10">
                                <input class="form-control" type="text" name="edit_last_name" id="edit_last_name"/>
                            </div>
                        </div>
                        <div class="clearfix visible-sm visible-md visible-lg"></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-default" onclick="saveTicket()">Save</button><button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button></div>
                </div>
            </div>
        </div>
    </div>
</div>
';

//$page->addNotification('Miss out on the initial ticket request window? Just need more tickets? You can request more tickets <a href="secondary.php" class="alert-link">here</a>!');

$page->printPage();
// vim: set tabstop=4 shiftwidth=4 expandtab:

