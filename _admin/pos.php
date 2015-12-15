<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketAdminPage.php');
$page = new TicketAdminPage('Burning Flipside - Tickets');

$page->add_js(JS_DATATABLE);
$page->add_css(CSS_DATATABLE);
$page->add_js_from_src('js/pos.js');

$page->body .= '
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Ticket Sales</h1>
            </div>
        </div>
        <div class="row">
            <div id="poswizard">
                <div class="navbar navbar-default">
                    <div class="container-fluid">
                        <div class="navbar-header">
                            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#wizard-navbar-collapse-1">
                                <span class="sr-only">Toggle navigation</span>
                                <span class="icon-bar"></span>
                                <span class="icon-bar"></span>
                                <span class="icon-bar"></span>
                            </button>
                        </div>
                    </div>
                    <div class="collapse navbar-collapse" id="wizard-navbar-collapse-1">
                        <ul class="nav navbar-nav">
                            <li class="active"><a href="#tab0" data-toggle="tab">Select Ticket(s)</a></li>
                            <li><a href="#tab1" data-toggle="tab">Email</a></li>
                            <li><a href="#tab2" data-toggle="tab">Checkout</a></li>
                        </ul>
                    </div>
                </div>
                <div class="tab-content">
                    <div role="tabpanel" class="tab-pane active" id="tab0">
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
                                <input class="form-control" type="text" name="email" id="email" required/>
                            </div>
                        </div>
                        <div class="clearfix visible-sm visible-md visible-lg"></div>
                        <div class="form-group">
                            <label for="message" class="col-sm-2 control-label">First Name (Optional):</label>
                            <div class="col-sm-10">
                                <input class="form-control" type="text" name="firstName" id="firstName"/>
                            </div>
                        </div>
                        <div class="clearfix visible-sm visible-md visible-lg"></div>
                        <div class="form-group">
                            <label for="message" class="col-sm-2 control-label">Last Name (Optional):</label>
                            <div class="col-sm-10">
                                <input class="form-control" type="text" name="lastName" id="lastName"/>
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
                    <ul class="pager">
                        <li class="previous disabled"><a href="#" onclick="prev_tab(event)"><span aria-hidden="true">&larr;</span> Previous</a></li>
                        <li class="next"><a href="#" onclick="next_tab(event)">Next <span aria-hidden="true">&rarr;</span></a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>
';

$page->print_page();
// vim: set tabstop=4 shiftwidth=4 expandtab:
?>

