<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketAdminPage.php');
$page = new TicketAdminPage('Burning Flipside - Tickets');

$page->addWellKnownJS(JS_DATATABLE);
$page->addWellKnownCSS(CSS_DATATABLE);
$page->addJS('https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js');

$page->body .= '
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Ticket Sales</h1>
            </div>
        </div>
        <div class="row">
            <div class="container" id="poswizard">
              <ul class="nav nav-tabs" id="posTabs" role="tablist">
                <li class="nav-item">
                  <a class="nav-link active" id="select-tab" data-toggle="tab" href="#tab0" role="tab" aria-controls="tab0" aria-selected="true">Select Ticket(s)</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" id="email-tab" data-toggle="tab" href="#tab1" role="tab" aria-controls="tab1" aria-selected="false">Email</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" id="checkout-tab" data-toggle="tab" href="#tab2" role="tab" aria-controls="tab2" aria-selected="false">Checkout</a>
                </li>
              </ul>
              <div class="tab-content">
                    <div role="tabpanel" class="tab-pane active" id="tab0">
                        <div class="form-group">
                            <label for="pool" class="col-sm-2 control-label">Ticket From Pool:</label>
                            <div class="col-sm-10">
                                <select class="form-control" name="pool" id="pool" onchange="poolChanged(this)">
                                    <option value="-1">Personal Discretionary Tickets</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="posType" class="col-sm-2 control-label">Payment Type:</label>
                            <div class="col-sm-10">
                                <select class="form-control" name="pool" id="posType">
                                </select>
                            </div>
                        </div>
                        <table class="table" id="ticket_select">
                            <thead>
                                <tr>
                                    <th>Qty</th>
                                    <th>Type</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                    <div role="tabpanel" class="tab-pane" id="tab1">
                        <div class="form-group">
                            <label for="email" class="col-sm-2 control-label">Email Address:</label>
                            <div class="col-sm-10">
                                <input class="form-control" type="email" name="email" id="email" required/>
                            </div>
                        </div>
                        <div class="clearfix visible-sm visible-md visible-lg"></div>
                        <div class="form-group">
                            <label for="message" class="col-sm-2 control-label">First Name:</label>
                            <div class="col-sm-10">
                                <input class="form-control" type="text" name="firstName" id="firstName" required/>
                            </div>
                        </div>
                        <div class="clearfix visible-sm visible-md visible-lg"></div>
                        <div class="form-group">
                            <label for="message" class="col-sm-2 control-label">Last Name:</label>
                            <div class="col-sm-10">
                                <input class="form-control" type="text" name="lastName" id="lastName" required/>
                            </div>
                        </div>
                        <div class="clearfix visible-sm visible-md visible-lg"></div>
                        <div class="form-group">
                            <label for="message" class="col-sm-2 control-label">Personal Message (Optional):</label>
                            <div class="col-sm-10">
                                <textarea rows="4" class="form-control" type="text" name="message" id="message">
                                </textarea>
                            </div>
                        </div>
                        <div class="clearfix visible-sm visible-md visible-lg"></div>
                    </div>
                    <div role="tabpanel" class="tab-pane" id="tab2">
                        <div class="form-group">
                            <label for="total" class="col-sm-2 control-label">Total Due:</label>
                            <div class="col-sm-10">
                                <input class="form-control" type="text" name="total" id="total" disabled/>
                            </div>
                        </div>
                        <div class="clearfix visible-sm visible-md visible-lg"></div>
                        <div class="form-group">
                            <label for="confirm_email" class="col-sm-2 control-label">Email To:</label>
                            <div class="col-sm-10">
                                <input class="form-control" type="text" name="confirm_email" id="confirm_email" disabled/>
                            </div>
                        </div>
                        <div class="clearfix visible-sm visible-md visible-lg"></div>
                    </div>
              </div>
              <nav>
                <ul class="pagination">
                  <li class="page-item previous disabled"><a class="page-link" href="#" onclick="prevTab(event)"><span aria-hidden="true">&larr;</span> Previous</a></li>
                  <li class="page-item next"><a class="page-link" href="#" onclick="nextTab(event)">Next <span aria-hidden="true">&rarr;</span></a></li>
                </ul>
              </nav>
            </div>
        </div>
    </div>
</div>
';

$page->printPage();
// vim: set tabstop=4 shiftwidth=4 expandtab:

