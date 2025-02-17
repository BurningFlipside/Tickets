<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketAdminPage.php');
$page = new TicketAdminPage('Burning Flipside - Tickets');
$page->setTemplateName('admin-table.html');
$page->addWellKnownJS(JS_DATATABLE, false);
$page->addWellKnownJS(JS_BOOTSTRAP_FH);
$page->addWellKnownCSS(CSS_DATATABLE);
$page->addWellKnownCSS(CSS_BOOTSTRAP_FH);
$page->addWellKnownJS(JS_TABULATOR, false);
$page->addWellKnownCSS(CSS_TABULATOR);

$page->content['pageHeader'] = 'Secondary Ticket Requests';
$page->content['selectors'] = '
  <label for="year" class="col-sm-2 control-label">Request Year:</label>
  <div class="col-sm-4">
    <select id="year" class="form-control" onchange="changeYear(this)">
    </select>
  </div>
  <div class="d-grid gap-2 d-md-block">
    <button onclick="getCSV();" title="Export CSV" class="btn btn-link btn-sm"><i class="fa fa-file-csv"></i></button>
  </div>
';
$page->content['table'] = array('id' => 'requests', 'headers'=>array('Request ID', 'Email', 'First Name', 'Last Name', 'Total Due'));


$page->body .= '
        <div class="modal fade in" aria-hidden="false" id="modal">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" id="modal_title"></h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="request_edit_form">
                            <div class="form-group">
                                <label for="request_id" class="col-sm-2 control-label">Request ID:</label>
                                <div class="col-sm-10">
                                    <input class="form-control" type="text" name="request_id" id="request_id" readonly value="114558">
                                </div>
                            </div>
                            <div class="clearfix visible-sm visible-md visible-lg"></div>
                            <div class="form-group">
                                <label for="givenName" class="col-sm-2 control-label">First Name:</label>
                                <div class="col-sm-10">
                                    <input class="form-control" id="givenName" name="givenName" type="text" autocomplete="off">
                                </div>
                            </div>
                            <div class="clearfix visible-sm visible-md visible-lg"></div>
                            <div class="form-group">
                                <label for="sn" class="col-sm-2 control-label">Last Name:</label>
                                <div class="col-sm-10">
                                    <input class="form-control" id="sn" name="sn" type="text" autocomplete="off">
                                </div>
                            </div>
                            <div class="clearfix visible-sm visible-md visible-lg"></div>
                            <div class="form-group">
                                <label for="mail" class="col-sm-2 control-label">Email:</label>
                                <div class="col-sm-10">
                                    <input class="form-control" type="text" name="mail" id="mail" autocomplete="off">
                                </div>
                            </div>
                            <div class="clearfix visible-sm visible-md visible-lg"></div>
                            <div class="form-group">
                                <label for="c" class="col-sm-2 control-label">Country:</label>
                                <div class="col-sm-10">
                                    <select class="form-control bfh-countries" id="c" name="c" data-country="US" autocomplete="off"></select>
                                </div>
                            </div>
                            <div class="clearfix visible-sm visible-md visible-lg"></div>
                            <div class="form-group">
                                <label for="street" class="col-sm-2 control-label">Street Address:</label>
                                <div class="col-sm-10">
                                    <textarea class="form-control" id="street" name="street" rows="2" autocomplete="off"></textarea>
                                </div>
                            </div>
                            <div class="clearfix visible-sm visible-md visible-lg"></div>
                            <div class="form-group">
                                <label for="zip" class="col-sm-2 control-label">Postal/Zip Code:</label>
                                <div class="col-sm-10">
                                    <input class="form-control" id="zip" name="zip" type="text" autocomplete="off">
                                </div>
                            </div>
                            <div class="clearfix visible-sm visible-md visible-lg"></div>
                            <div class="form-group">
                                <label for="l" class="col-sm-2 control-label">City:</label>
                                <div class="col-sm-10">
                                    <input class="form-control" id="l" name="l" type="text" value="AUSTIN" autocomplete="off">
                                </div>
                            </div>
                            <div class="clearfix visible-sm visible-md visible-lg"></div>
                            <div class="form-group">
                                <label for="st" class="col-sm-2 control-label">State:</label>
                                <div class="col-sm-10">
                                    <select class="form-control bfh-states" data-country="c" id="st" name="st" type="text" autocomplete="off"></select>
                                </div>
                            </div>
                            <div class="clearfix visible-sm visible-md visible-lg"></div>
                            <table id="ticket_table" class="table">
                                <thead>
                                    <tr>
                                        <th>First Name</th>
                                        <th>Last Name</th>
                                        <th>Age</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                            <div class="clearfix visible-sm visible-md visible-lg"></div>
                            <div class="form-group">
                            <label for="total_due" class="col-sm-2 control-label">Total Due:</label>
                            <div class="col-sm-10">
                                <input class="form-control" type="text" name="total_due" id="total_due" readonly/>
                            </div>
                        </div>
                        <div class="clearfix visible-sm visible-md visible-lg"></div>
                        <div class="form-group">
                            <label for="total_received" class="col-sm-2 control-label">Total Received:</label>
                            <div class="col-sm-10">
                                <input class="form-control" type="text" name="total_received" id="total_received" required/>
                            </div>
                        </div>
                        <div class="clearfix visible-sm visible-md visible-lg"></div>
                       </form>
                  </div>
                  <div class="modal-footer">
                      <button type="button" class="btn btn-primary" onclick="saveRequest(this)" id="ticketButton">Ticket</button>
                      <button type="button" class="btn btn-secondary" onclick="getPDF(this)">Get PDF</button>
                      <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                  </div>
              </div>
          </div>
        </div>
    </div>
</div>
';

$page->printPage();
// vim: set tabstop=4 shiftwidth=4 expandtab:

