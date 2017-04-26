<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketAdminPage.php');
$page = new TicketAdminPage('Burning Flipside - Tickets');

$page->addWellKnownJS(JS_DATATABLE);
$page->addWellKnownCSS(CSS_DATATABLE);
$page->addWellKnownJS(JS_BOOTBOX);
$page->addJSByURI('../js/instascan.min.js', false);
$page->addJSByURI('js/gate.js');

$page->body .= '
    <div class="row">
        <div class="col-sm-12">
            <h1 class="page-header">
                Gate
                <button id="screen" class="btn btn-default pull-right" title="fullscreen" onclick="fullscreen()"><span class="fa fa-arrows-alt"></span></button>
            </h1>
        </div>
    </div>
    <div class="row">
        <div class="form-group">
            <label for="ticket_search" class="col-sm-2 control-label">Search:</label>
            <div class="col-sm-10 input-group">
		<input class="form-control" type="text" name="ticket_search" id="ticket_search"/>
		<span class="input-group-btn">
                    <button class="btn btn-default" id="ticketCodeScan" type="button" data-toggle="modal" data-target="#qrcodeScan"><i class="fa fa-qrcode" aria-hidden="true"></i></button>
                </span>
            </div>
        </div>
    </div>
    <div class"row"><br/><br/><br/><br/><br/></div>
    <div class="row">
        <div class="panel-group" id="gate" role="tablist" aria-multiselectable="true">
            <div class="panel panel-default">
                <div class="panel-heading" role="tab" id="history_heading">
                    <h4 class="panel-title">
                        <a data-toggle="collapse" data-parent="#gate" href="#history" aria-expanded="true" aria-controls="history">Ticket History Search</a>
                    </h4>
                </div>
                <div id="history" class="panel-collapse collapse" role="tabpanel" aria-labelledby="history_heading">
                    <div class="panel-body">
                        <div class="form-group">
                            <label for="history_search" class="col-sm-2 control-label">History Search:</label>
                            <div class="col-sm-10">
                                <input class="form-control" type="text" name="history_search" id="history_search"/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" aria-hidden="true" id="process_ticket_modal" style="display: none;" data-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span><span class="sr-only">Close</span></button>
                    <h4 class="modal-title" id="modal_title">Process Ticket</h4>
                </div>
                <div class="modal-body"><div class="container-fluid">
                    <div>
                        <label for="hash" class="col-sm-2 control-label">Code:</label>
                        <div class="col-sm-6">
                            <input class="form-control" type="text" name="hash" id="hash" readonly="">
                        </div>
                        <label for"type" class="col-sm-2 control-label">Type:</label>
                        <div class="col-sm-2">
                            <input class="form-control" type="text" name="type" id="type" readonly="">
                        </div>
                    </div>
                    <div class="clearfix visible-sm visible-md visible-lg"></div>
                    <div class="form-group">
                        <label for="firstName" class="col-sm-2 control-label">First Name:</label>
                        <div class="col-sm-10">
                            <input class="form-control" type="text" name="firstName" id="firstName">
                        </div>
                    </div>
                    <div class="clearfix visible-sm visible-md visible-lg"></div>
                    <div class="form-group">
                        <label for="lastName" class="col-sm-2 control-label">Last Name:</label>
                        <div class="col-sm-10">
                            <input class="form-control" type="text" name="lastName" id="lastName"/>
                        </div>
                    </div>
                    <div class="clearfix visible-sm visible-md visible-lg"></div>
                    <div id="minor_block">
                        <div class="form-group">
                            <label for="guardian_first" class="col-sm-2 control-label">Guardian First Name:</label>
                            <div class="col-sm-10">
                                <input class="form-control" type="text" name="guardian_first" id="guardian_first"/>
                            </div>
                        </div>
                        <div class="clearfix visible-sm visible-md visible-lg"></div>
                        <div class="form-group">
                            <label for="guardian_last" class="col-sm-2 control-label">Guardian Last Name:</label>
                            <div class="col-sm-10">
                                <input class="form-control" type="text" name="guardian_last" id="guardian_last"/>
                            </div>
                        </div>
                        <div class="clearfix visible-sm visible-md visible-lg"></div>
                    </div>
                    <div class="clearfix visible-sm visible-md visible-lg"></div>
                    <div>
                        <label for="used" class="col-sm-2 control-label">Used:</label>
                        <div class="col-sm-2">
                            <input class="form-control" type="checkbox" name="used" id="used"/>
                        </div>
                    </div>
                    <div class="col-sm-2"></div>
                    <div>
                        <label for="void" class="col-sm-2 control-label">Void:</label>
                        <div class="col-sm-2">
                            <input class="form-control" type="checkbox" name="void" id="void"/>
                        </div>
                    </div>
                    <div class="clearfix visible-sm visible-md visible-lg"></div>
                    <div class="form-group">
                        <label for="physical_ticket_id" class="col-sm-2 control-label">Physical Ticket ID:</label>
                        <div class="col-sm-10">
                            <input class="form-control" type="text" name="physical_ticket_id" id="physical_ticket_id"/>
                        </div>
                    </div>
                    <div class="clearfix visible-sm visible-md visible-lg"></div>
                    <div class="form-group">
                        <label for="comments" class="col-sm-2 control-label">Comments:</label>
                        <div class="col-sm-10">
                            <textarea rows="5" class="form-control" name="comments" id="comments"></textarea>
                        </div>
                    </div>
                    <div class="clearfix visible-sm visible-md visible-lg"></div>
                </div></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" onclick="process_ticket()">Process</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" aria-hidden="true" id="search_ticket_modal" style="display: none;" data-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span><span class="sr-only">Close</span></button>
                    <h4 class="modal-title" id="modal_title">Search Tickets</h4>
                </div>
                <div class="modal-body">
                    <div class="container-fluid">
                        <table class-"table table-striped stripe" id="search_ticket_table">
                            <thead>
                                <tr>
                                    <th>Hash</th>
                                    <th>First Name</th>
                                    <th>Last Name</th>
                                    <th>Type</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" aria-hidden="true" id="ticket_history_modal" style="display: none;" data-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span><span class="sr-only">Close</span></button>
                    <h4 class="modal-title" id="modal_title">Ticket History</h4>
                </div>
                <div class="modal-body"><div class="container-fluid">
                    <div class="form-group">
                        <label for="history_hash" class="col-sm-2 control-label">Code:</label>
                        <div class="col-sm-10">
                            <input class="form-control" type="text" name="history_hash" id="history_hash" readonly/>
                        </div>
                    </div>
                    <div class="clearfix visible-sm visible-md visible-lg"></div>
                    <div class="form-group">
                        <label for="history_firstName" class="col-sm-2 control-label">First Name:</label>
                        <div class="col-sm-10">
                            <input class="form-control" type="text" name="history_firstName" id="history_firstName"/>
                        </div>
                    </div>
                    <div class="clearfix visible-sm visible-md visible-lg"></div>
                    <div class="form-group">
                        <label for="history_lastName" class="col-sm-2 control-label">Last Name:</label>
                        <div class="col-sm-10">
                            <input class="form-control" type="text" name="history_lastName" id="history_lastName"/>
                        </div>
                    </div>
                    <div class="clearfix visible-sm visible-md visible-lg"></div>
                    <div id="minor_block">
                        <div class="form-group">
                            <label for="history_guardian_first" class="col-sm-2 control-label">Guardian First Name:</label>
                            <div class="col-sm-10">
                                <input class="form-control" type="text" name="history_guardian_first" id="history_guardian_first"/>
                            </div>
                        </div>
                        <div class="clearfix visible-sm visible-md visible-lg"></div>
                        <div class="form-group">
                            <label for="history_guardian_last" class="col-sm-2 control-label">Guardian Last Name:</label>
                            <div class="col-sm-10">
                                <input class="form-control" type="text" name="history_guardian_last" id="history_guardian_last"/>
                            </div>
                        </div>
                        <div class="clearfix visible-sm visible-md visible-lg"></div>
                    </div>
                    <div class="clearfix visible-sm visible-md visible-lg"></div>
                    <div>
                        <label for="history_used" class="col-sm-2 control-label">Used:</label>
                        <div class="col-sm-2">
                            <input class="form-control" type="checkbox" name="history_used" id="history_used"/>
                        </div>
                    </div>
                    <div class="col-sm-2"></div>
                    <div>
                        <label for="history_void" class="col-sm-2 control-label">Void:</label>
                        <div class="col-sm-2">
                            <input class="form-control" type="checkbox" name="history_void" id="history_void"/>
                        </div>
                    </div>
                    <div class="clearfix visible-sm visible-md visible-lg"></div>
                    <div class="form-group">
                        <label for="history_physical_ticket_id" class="col-sm-2 control-label">Physical Ticket ID:</label>
                        <div class="col-sm-10">
                            <input class="form-control" type="text" name="history_physical_ticket_id" id="history_physical_ticket_id">
                        </div>
                    </div>
                    <div class="clearfix visible-sm visible-md visible-lg"></div>
                    <div class="form-group">
                        <label for="history_comments" class="col-sm-2 control-label">Comments:</label>
                        <div class="col-sm-10">
                            <textarea rows="5" class="form-control" name="history_comments" id="history_comments"></textarea>
                        </div>
                    </div>
                    <div class="clearfix visible-sm visible-md visible-lg"></div>
                    <div class="col-md-6" style="text-align: center;"><a onclick="prev_ticket()" style="cursor: pointer;" id="left_arrow"><span class="fa fa-chevron-left"></span></a></div>
                    <div class="col-md-6" style="text-align: center;"><a onclick="next_ticket()" style="cursor: pointer;" id="right_arrow"><span class="fa fa-chevron-right"></span></a></div>
                    <div class="clearfix visible-sm visible-md visible-lg"></div>
                </div></div>
                <div class="modal-footer">
                    <button id="process_history" type="button" class="btn btn-default" onclick="process_history_ticket()">Process</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" aria-hidden="true" id="history_ticket_modal" style="display: none;" data-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span><span class="sr-only">Close</span></button>
                    <h4 class="modal-title" id="modal_title">Search Ticket History</h4>
                </div>
                <div class="modal-body">
                    <div class="container-fluid">
                        <table class-"table table-striped stripe" id="history_ticket_table">
                            <thead>
                                <tr>
                                    <th>Hash</th>
                                    <th>First Name</th>
                                    <th>Last Name</th>
                                    <th>Type</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" aria-hidden="true" id="qrcodeScan" style="display: none;" data-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span><span class="sr-only">Close</span></button>
                    <h4 class="modal-title" id="modal_title">Scan QR Code</h4>
                </div>
                <div class="modal-body">
		    <div class="container-fluid">
			<div class="form-group">
                            <label class="col-sm-2 control-label">Video Source: </label>
                            <div class="col-sm-10">
			        <select class="form-control" id="videoSource"></select>
                            </div>
			</div>
			<div class="col-sm-10 embed-responsive embed-responsive-4by3">
                            <video id="v" class="embed-responsive-item" width="1024" height="768"></video>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
';

$page->print_page();
// vim: set tabstop=4 shiftwidth=4 expandtab:

