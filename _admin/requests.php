<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketAdminPage.php');
$page = new TicketAdminPage('Burning Flipside - Tickets');
$page->setTemplateName('admin-table.html');
$page->addWellKnownJS(JS_BOOTSTRAP_FH);
$page->addWellKnownCSS(CSS_BOOTSTRAP_FH);
$page->addWellKnownJS(JS_TABULATOR, false);
$page->addWellKnownCSS(CSS_TABULATOR);
$page->addAsyncJS('//oss.sheetjs.com/sheetjs/xlsx.full.min.js', 'excelLoaded()');

$page->content['pageHeader'] = 'Ticket Requests';
$page->content['selectors'] = '
  <label for="year" class="col-sm-2 control-label">Request Year:</label>
  <div class="col-sm-4">
    <select id="year" class="form-control" onchange="changeYear(this)">
      <option value="*">All</option>
    </select>
  </div>
  <label for="statusFilter" class="col-sm-2 control-label">Request Status:</label>
  <div class="col-sm-4">
    <select id="statusFilter" class="form-control" onchange="changeStatusFilter(this)">
        <option value="*">All</option>
    </select>
  </div>
  <div class="d-grid gap-2 d-md-block">
    <button id="csv" class="btn btn-link btn-sm" onclick="getCSV();" title="Export CSV"><i class="fa fa-file-csv"></i></button>
  </div>
';
$page->content['table'] = array('id' => 'requests', 'headers'=>array('Request ID', 'First Name', 'Last Name', 'Email', 'Total Due'));

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
                            <div class="row">
                              <label for="request_id" class="col-sm-2 control-label">Request ID:</label>
                              <div class="col-sm-10">
                                    <input class="form-control" type="text" name="request_id" id="request_id" readonly value="114558">
                              </div>
                              <div class="w-100"></div>
                              <label for="givenName" class="col-sm-2 control-label">First Name:</label>
                              <div class="col-sm-10">
                                  <input class="form-control" id="givenName" name="givenName" type="text">
                              </div>
                              <div class="w-100"></div>
                              <label for="sn" class="col-sm-2 control-label">Last Name:</label>
                              <div class="col-sm-10">
                                    <input class="form-control" id="sn" name="sn" type="text">
                              </div>
                              <div class="w-100"></div>
                              <label for="mail" class="col-sm-2 control-label">Email:</label>
                              <div class="col-sm-10">
                                  <input class="form-control" type="text" name="mail" id="mail">
                              </div>
                              <div class="w-100"></div>
                              <label for="c" class="col-sm-2 control-label">Country:</label>
                              <div class="col-sm-10">
                                  <select class="form-control bfh-countries" id="c" name="c" data-country="US"></select>
                              </div>
                              <div class="w-100"></div>
                              <label for="mobile" class="col-sm-2 control-label">Cell Number:</label>
                              <div class="col-sm-10">
                                  <input id="mobile" name="mobile" type="text" class="form-control bfh-phone" data-country="c">
                              </div>
                              <div class="w-100"></div>
                              <label for="street" class="col-sm-2 control-label">Street Address:</label>
                              <div class="col-sm-10">
                                    <textarea class="form-control" id="street" name="street" rows="2"></textarea>
                              </div>
                              <div class="w-100"></div>
                              <label for="zip" class="col-sm-2 control-label">Postal/Zip Code:</label>
                              <div class="col-sm-10">
                                  <input class="form-control" id="zip" name="zip" type="text">
                              </div>
                              <div class="w-100"></div>
                              <label for="l" class="col-sm-2 control-label">City:</label>
                              <div class="col-sm-10">
                                  <input class="form-control" id="l" name="l" type="text" value="AUSTIN">
                              </div>
                              <div class="w-100"></div>
                              <label for="st" class="col-sm-2 control-label">State:</label>
                              <div class="col-sm-10">
                                  <select class="form-control bfh-states" data-country="c" id="st" name="st" type="text"></select>
                              </div>
                              <div class="w-100"></div>
                              <label for="paymentMethod" class="col-sm-2 control-label">Payment Method:</label>
                              <div class="col-sm-10">
                                  <input class="form-control" id="paymentMethod" name="paymentMethod" type="text" readonly></select>
                              </div>
                            </div>
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
                            <table id="donation_table" class="table">
                                <thead>
                                    <tr>
                                        <th>Entity</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                            <div class="row">
                                <label for="envelopeArt" class="col-sm-2 control-label">Envelope Art Consent:</label>
                                <div class="col-sm-2">
                                    <input type="checkbox" id="envelopeArt" name="envelopeArt"></input>
                                </div>
                                <label for="critvol" class="col-sm-2 control-label">Critical Volunteer:</label>
                                <div class="col-sm-2">
                                    <input type="checkbox" id="critvol" name="critvol"></input>
                                </div>
                                <label for="protected" class="col-sm-2 control-label">Protected:</label>
                                <div class="col-sm-2">
                                    <input type="checkbox" id="protected" name="protected"></input>
                                </div>
                                <div class="w-100"></div>
                                  <label for="total_due" class="col-sm-2 control-label">Total Due:</label>
                                  <div class="col-sm-10">
                                    <input class="form-control" type="text" name="total_due" id="total_due" readonly/>
                                </div>
                                <div class="w-100"></div>
                                <label for="total_received" class="col-sm-2 control-label">Total Received:</label>
                                <div class="col-sm-10">
                                  <input class="form-control" type="text" name="total_received" id="total_received" required/>
                                </div>
                                <div class="w-100"></div>
                                <label for="status" class="col-sm-2 control-label">Status:</label>
                                <div class="col-sm-10">
                                  <select class="form-control" name="status" id="status"></select>
                                </div>
                                <div class="w-100"></div>
                                <label for="comments" class="col-sm-2 control-label">Comments:</label>
                                <div class="col-sm-10">
                                  <textarea class="form-control" name="comments" id="comments"></textarea>
                                </div>
                                <div class="w-100"></div>
                                <label for="bucket" class="col-sm-2 control-label">Bucket:</label>
                                <div class="col-sm-10">
                                  <input type="text" class="form-control" name="bucket" id="bucket" readonly/>
                               </div>
                        </div>
                       </form>
                  </div>
                  <div class="modal-footer">
                      <button type="button" class="btn btn-primary" onclick="saveRequest(this)">Ok</button>
                      <button type="button" class="btn btn-outline-primary" onclick="editRequest(this)">Edit Tickets/Donations</button>
                      <button type="button" class="btn btn-outline-secondary" onclick="getPDF(this)">Get PDF</button>
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                  </div>
              </div>
          </div>
        </div>
    </div>
</div>
';

$page->printPage();
// vim: set tabstop=4 shiftwidth=4 expandtab:

