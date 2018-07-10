<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketAdminPage.php');
$page = new TicketAdminPage('Burning Flipside - Tickets');

$page->addWellKnownJS(JS_BOOTBOX);
$page->addWellKnownJS(JS_TYPEAHEAD, false);

$page->body .= '
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Pool Management</h1>
            </div>
        </div>
        <div class="row">
            <table class="table" id="pools">
                <thead>
                    <th></th>
                    <th>Pool ID</th>
                    <th>Pool Name</th>
                    <th>Pool Owner</th>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
        <div id="editModal" class="modal fade">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                        <h4 class="modal-title" id="modal_title">Edit Pool #<span id="_id"></span></h4>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="show_short_code" class="col-sm-2 control-label">Pool Name:</label>
                            <div class="col-sm-10">
                                <input class="form-control" type="text" name="pool_name" id="pool_name"/>
                            </div>
                        </div>
                        <div class="clearfix visible-sm visible-md visible-lg"></div>
                        <div class="form-group">
                            <label for="show_short_code" class="col-sm-2 control-label">Pool Owner:</label>
                            <div class="col-sm-10">
                                <input class="form-control typeahead" type="text" name="group_name" id="group_name"/>
                            </div>
                        </div>
                        <div class="clearfix visible-sm visible-md visible-lg"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-defualt" data-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="updatePool()">OK</button>
                    </div>
                </div>
            </div>
        </div>
        <div id="newModal" class="modal fade">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                        <h4 class="modal-title" id="modal_title">New Ticket Pool</h4>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="show_short_code" class="col-sm-2 control-label">Pool Name:</label>
                            <div class="col-sm-10">
                                <input class="form-control" type="text" name="pool_name" id="pool_name_new"/>
                            </div>
                        </div>
                        <div class="clearfix visible-sm visible-md visible-lg"></div>
                        <div class="form-group">
                            <label for="show_short_code" class="col-sm-2 control-label">Pool Owner:</label>
                            <div class="col-sm-10">
                                <input class="form-control typeahead" type="text" name="group_name" id="group_name_new"/>
                            </div>
                        </div>
                        <div class="clearfix visible-sm visible-md visible-lg"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-defualt" data-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="createPool()">OK</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
';

$page->printPage();
// vim: set tabstop=4 shiftwidth=4 expandtab:

