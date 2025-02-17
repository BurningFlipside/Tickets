<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketAdminPage.php');
$page = new TicketAdminPage('Burning Flipside - Tickets');

$page->addWellKnownJS(JS_TABULATOR, false);
$page->addWellKnownCSS(CSS_TABULATOR);

$page->body .= '
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Data Entry</h1>
            </div>
        </div>
        <div class="row">
            <label for="request_id" class="col-sm-2 control-label">Request ID:</label>
            <div class="col-sm-10">
              <input class="form-control" type="text" name="request_id" id="request_id" onchange="lookupRequest(this)" autofocus autocomplete="off"/>
            </div>
        </div>
        <div class="row">
            <hr/>
        </div>
        <div class="row">
            <div class="accordion" id="accordionExample">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="advancedHdr">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#advanced" aria-expanded="true" aria-controls="advanced">
                            Advanced Search
                        </button>
                    </h2>
                    <div id="advanced" class="accordion-collapse collapse" aria-labelledby="advancedHdr" data-bs-parent="#accordionExample">
                        <div class="accordion-body">
                        <div class="input-group">
                            <div class="input-group-btn">
                                <button type="button" class="btn btn-default dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" id="type" data-type="*">All <span class="caret"></span></button>
                                <ul class="dropdown-menu" role="menu">
                                    <li><a href="#" onclick="changeMenu(\'*\', \'All\');">All</a></li>
                                    <li><a href="#" onclick="changeMenu(\'mail\', \'Email\');">Email</a></li>
                                    <li><a href="#" onclick="changeMenu(\'givenName\', \'First Name\');">First Name</a></li>
                                    <li><a href="#" onclick="changeMenu(\'sn\', \'Last Name\');">Last Name</a></li>
                                </ul>
                            </div>
                            <input class="form-control" type="text" name="value" id="value" onchange="lookupRequestByValue(this)" autocomplete="off"/>
                        </div>
                       <div class="clearfix visible-sm visible-md visible-lg"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade in modal-lg" aria-hidden="false" id="modal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                      <h4 class="modal-title" id="modal_title"></h4>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <div class="row">
                        <label for="given_name" class="col-sm-2 control-label">Given Name:</label>
                        <div class="col-sm-10">
                          <input class="form-control" type="text" name="given_name" id="given_name" readonly/>
                        </div>
                        <div class="w-100"></div>
                        <label for="last_name" class="col-sm-2 control-label">Last Name:</label>
                        <div class="col-sm-10">
                          <input class="form-control" type="text" name="last_name" id="last_name" readonly/>
                        </div>
                      </div>
                      <form id="req_form">
                        <div class="row">
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
                        <div class="clearfix visible-sm visible-md visible-lg"></div>
                        <input type="hidden" name="dataentry" value="true"/>
                        <input type="hidden" name="id" id="request_id_hidden"/>
                        </form>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-primary" id="save_btn" onclick="saveRequest(this)">Ok</button><button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button></div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="request_select">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Requests</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                    <table id="request_table" class="table">
                        <thead>
                            <th>Request ID</th>
                            <th>Name</th>
                            <th>Email</th>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                    </div>
                    <div class="modal-footer">
                </div>
            </div>
        </div>
    </div>
</div>
';

$page->printPage();
// vim: set tabstop=4 shiftwidth=4 expandtab:

