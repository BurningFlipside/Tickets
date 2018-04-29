<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketAdminPage.php');
$page = new TicketAdminPage('Burning Flipside - Tickets');

$page->addWellKnownJS(JS_DATATABLE, false);
$page->addWellKnownCSS(CSS_DATATABLE);
$page->addJSByURI('js/tickets.js');

$page->body .= '
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Tickets</h1>
            </div>
        </div>
        <div class="row">
            Year: <select id="ticket_year"></select>
            Sold: 
            <select id="ticketSold">
                <option selected value="*">Both</option>
                <option value="1">Yes</option>
                <option value="0">No</option>
            </select>
            Used: 
            <select id="ticketUsed">
                <option selected value="*">Both</option>
                <option value="1">Yes</option>
                <option value="0">No</option>
            </select>
            Discretionary:
            <input type="email" id="discretionaryUser" autocomplete="on"/>
        </div>
        <div class="row">
            <table class="table" id="tickets">
                <thead>
                    <tr>
                        <th>Short Code</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Email</th>
                        <th>Type</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <div class="modal fade" id="ticket_modal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                        <h4 class="modal-title">Ticket</h4>
                    </div>
                    <div class="modal-body"><div class-"containter-fluid">
                        <form id="ticket_data">
                            <div class="form-group">
                                <label for="hash" class="col-sm-2 control-label">ID:</label>
                                <div class="col-sm-10">
                                    <input class="form-control" type="text" name="hash" id="hash" readonly/>
                                </div>
                            </div>
                            <div class="clearfix visible-sm visible-md visible-lg"></div>
                            <div class="form-group">
                                <label for="year" class="col-sm-2 control-label">Year:</label>
                                <div class="col-sm-10">
                                    <input class="form-control" type="text" name="year" id="year" readonly/>
                                </div>
                            </div>
                            <div class="clearfix visible-sm visible-md visible-lg"></div>
                            <div class="form-group">
                                <label for="firstName" class="col-sm-2 control-label">First Name:</label>
                                <div class="col-sm-10">
                                    <input class="form-control" type="text" name="firstName" id="firstName" autocomplete="off"/>
                                </div>
                            </div>
                            <div class="clearfix visible-sm visible-md visible-lg"></div>
                            <div class="form-group">
                                <label for="lastName" class="col-sm-2 control-label">Last Name:</label>
                                <div class="col-sm-10">
                                    <input class="form-control" type="text" name="lastName" id="lastName" autocomplete="off"/>
                                </div>
                            </div>
                            <div class="clearfix visible-sm visible-md visible-lg"></div>
                            <div class="form-group">
                                <label for="email" class="col-sm-2 control-label">Email:</label>
                                <div class="col-sm-10">
                                    <input class="form-control" type="text" name="email" id="email" autocomplete="off"/>
                                </div>
                            </div>
                            <div class="clearfix visible-sm visible-md visible-lg"></div>
                            <div class="form-group">
                                <label for="request_id" class="col-sm-2 control-label">Request ID:</label>
                                <div class="col-sm-10">
                                    <input class="form-control" type="text" name="request_id" id="request_id" autocomplete="off"/>
                                </div>
                            </div>
                            <div class="clearfix visible-sm visible-md visible-lg"></div>
                            <div class="form-group">
                                <label for="type" class="col-sm-2 control-label">Type:</label>
                                <div class="col-sm-10">
                                    <input class="form-control" type="text" name="type" id="type"/>
                                </div>
                            </div>
                            <div class="clearfix visible-sm visible-md visible-lg"></div>
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
                            <div class="form-group">
                                <input type="checkbox" id="sold" name="sold">
                                <label for="sold" class="col-sm-2 control-label">Sold?</label>
                            </div>
                            <div class="clearfix visible-sm visible-md visible-lg"></div>
                            <div class="form-group">
                                <input type="checkbox" id="used" name="used">
                                <label for="used" class="col-sm-2 control-label">Used?</label>
                            </div>
                            <div class="clearfix visible-sm visible-md visible-lg"></div>
                            <div class="form-group">
                                <input type="checkbox" id="void" name="void">
                                <label for="void" class="col-sm-2 control-label">Void?</label>
                            </div>
                            <div class="clearfix visible-sm visible-md visible-lg"></div>
                            <div class="form-group">
                                <label for="earlyEntryWindow" class="col-sm-2 control-label">Entry Window:</label>
                                <div class="col-sm-10">
                                    <input class="form-control" type="text" name="earlyEntryWindow" id="earlyEntryWindow"/>
                                </div>
                            </div>
                            <div class="clearfix visible-sm visible-md visible-lg"></div>
                            <div class="form-group">
                                 <label for="comments" class="col-sm-2 control-label">Comments:</label>
                                 <div class="col-sm-10">
                                    <textarea class="form-control" rows="5" name="comments" id="comments"></textarea>
                                </div>
                            </div>
                        </form>
                        <div class="col-md-6" style="text-align: center;"><a onclick="prev_ticket()" style="cursor: pointer;" id="left_arrow"><span class="fa fa-chevron-left"></span></a></div>
                        <div class="col-md-6" style="text-align: center;"><a onclick="next_ticket()" style="cursor: pointer;" id="right_arrow"><span class="fa fa-chevron-right"></span></a></div>
                        <div class="clearfix visible-sm visible-md visible-lg"></div>
                    </div></div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-default" onclick="resendTicketEmail();">Resend Ticket Email</button>
                        <button type="button" class="btn btn-default" onclick="spinHash();">Assign New ID</button>
                        <button type="button" class="btn btn-primary" id="saveticket" onclick="save_ticket()">Save changes</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
';

$page->print_page();
// vim: set tabstop=4 shiftwidth=4 expandtab:

