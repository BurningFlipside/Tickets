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
                <h1 class="page-header">Tickets</h1>
            </div>
        </div>
        <div class="row">
          <div class="col-sm-1 col-form-label">Year:</div>
          <div class="col-sm-1"><select id="ticket_year" class="form-control"></select></div>
          <div class="col-sm-1 col-form-label">Sold:</div>
          <div class="col-sm-1">
            <select id="ticketSold" class="form-control">
                <option selected value="*">Both</option>
                <option value="1">Yes</option>
                <option value="0">No</option>
            </select>
          </div>
          <div class="col-sm-1 col-form-label">Void:</div>
          <div class="col-sm-1">
            <select id="ticketVoid" class="form-control">
                <option selected value="*">Both</option>
                <option value="1">Yes</option>
                <option value="0">No</option>
            </select>
          </div>
          <div class="col-sm-1 col-form-label">Assigned:</div>
          <div class="col-sm-1">
            <select id="ticketAssigned" class="form-control">
                <option selected value="*">Both</option>
                <option value="1">Yes</option>
                <option value="0">No</option>
            </select>
          </div>
          <div class="col-sm-1 col-form-label">Used:</div>
          <div class="col-sm-1">
            <select id="ticketUsed" class="form-control">
                <option selected value="*">Both</option>
                <option value="1">Yes</option>
                <option value="0">No</option>
            </select>
          </div>
          <div class="w-100"></div>
          <div class="col-sm-1 col-form-label">Discretionary:</div>
          <div class="col-sm-3">
            <input type="email" id="discretionaryUser" class="form-control" autocomplete="on"/>
          </div>
          <div class="col-sm-1 col-form-label">Early Entry:</div>
          <div class="col-sm-3">
            <select id="earlyEntry" class="form-control">
              <option selected value="*">All</option>
            </select>
          </div>
          <div class="col-sm-1 col-form-label">Pool:</div>
          <div class="col-sm-2">
            <select id="ticketPool" class="form-control">
              <option selected value="*">All</option>
            </select>
          </div>
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
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                      <h4 class="modal-title">Ticket</h4>
                      <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                    </div>
                    <div class="modal-body">
                      <div class-"containter-fluid">
                        <form id="ticket_data">
                          <div class="row">
                            <label for="hash" class="col-sm-2 col-form-label">ID:</label>
                            <div class="col-sm-10">
                              <input class="form-control" type="text" name="hash" id="hash" readonly/>
                            </div>
                            <div class="w-100"></div>
                            <label for="year" class="col-sm-2 col-form-label">Year:</label>
                            <div class="col-sm-10">
                              <input class="form-control" type="text" name="year" id="year" readonly/>
                            </div>
                            <div class="w-100"></div>
                            <label for="firstName" class="col-sm-2 col-form-label">First Name:</label>
                            <div class="col-sm-10">
                              <input class="form-control" type="text" name="firstName" id="firstName" autocomplete="off"/>
                            </div>
                            <div class="w-100"></div>
                            <label for="lastName" class="col-sm-2 col-form-label">Last Name:</label>
                            <div class="col-sm-10">
                              <input class="form-control" type="text" name="lastName" id="lastName" autocomplete="off"/>
                            </div>
                            <div class="w-100"></div>
                            <label for="email" class="col-sm-2 col-form-label">Email:</label>
                            <div class="col-sm-10">
                              <input class="form-control" type="text" name="email" id="email" autocomplete="off"/>
                            </div>
                            <div class="w-100"></div>
                            <label for="request_id" class="col-sm-2 col-form-label">Request ID:</label>
                            <div class="col-sm-10">
                              <input class="form-control" type="text" name="request_id" id="request_id" autocomplete="off"/>
                            </div>
                            <div class="w-100"></div>
                            <label for="type" class="col-sm-2 col-form-label">Type:</label>
                            <div class="col-sm-10">
                              <input class="form-control" type="text" name="type" id="type"/>
                            </div>
                             <div class="w-100"></div>
                            <label for="guardian_first" class="col-sm-2 col-form-label">Guardian First Name:</label>
                            <div class="col-sm-10">
                              <input class="form-control" type="text" name="guardian_first" id="guardian_first"/>
                            </div>
                            <div class="w-100"></div>
                            <label for="guardian_last" class="col-sm-2 col-form-label">Guardian Last Name:</label>
                            <div class="col-sm-10">
                              <input class="form-control" type="text" name="guardian_last" id="guardian_last"/>
                            </div>
                            <div class="w-100"></div>
                            <div class="col-sm-2"></div>
                            <input type="checkbox" id="sold" name="sold">
                            <label for="sold" class="col-sm-2 col-form-label">Sold?</label>
                            <input type="checkbox" id="used" name="used">
                            <label for="used" class="col-sm-2 col-form-label">Used?</label>
                            <input type="checkbox" id="void" name="void">
                            <label for="void" class="col-sm-2 col-form-label">Void?</label>
                            <div class="w-100"></div>
                            <label for="earlyEntryWindow" class="col-sm-2 col-form-label">Entry Window:</label>
                            <div class="col-sm-10">
                              <input class="form-control" type="text" name="earlyEntryWindow" id="earlyEntryWindow"/>
                            </div>
                            <div class="w-100"></div>
                            <label for="comments" class="col-sm-2 col-form-label">Comments:</label>
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
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-info" onclick="resendTicketEmail();">Resend Ticket Email</button>
                        <button type="button" class="btn btn-outline-info" onclick="spinHash();">Assign New ID</button>
                        <button type="button" class="btn btn-primary" id="saveticket" onclick="save_ticket()">Save changes</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
';

$page->printPage();
// vim: set tabstop=4 shiftwidth=4 expandtab:

