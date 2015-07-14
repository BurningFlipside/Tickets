<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketPage.php');
$page = new TicketPage('Burning Flipside - Tickets');

$page->add_js(JS_DATATABLE, false);
$page->add_css(CSS_DATATABLE);
$page->add_js_from_src('js/index.js');

if(!FlipSession::is_logged_in())
{
    $page->body .= '
<div id="content">
    <h1>You must <a href="https://profiles.burningflipside.com/login.php?return='.$page->current_url().'">log in <span class="glyphicon glyphicon-log-in"></span></a> to access the Burning Flipside Ticket system!</h1>
</div>';
}
else
{
    $discretionary = '';
    $user = FlipSession::get_user(TRUE);
    if($user !== false && $user->isInGroupNamed("AAR"))
    {
        $page->add_js_from_src('js/discretionary.js');
        $discretionary = '
        <fieldset id="discretionary_set">
            <legend>Discretionary Tickets</legend>
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
        </fieldset>';
    }
    $page->body .= '
<div id="content">
    <fieldset id="request_set" style="display: none;">
        <legend>Ticket Request</legend>
        <table id="requestList" class="table table-striped">
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
    </fieldset>
    <fieldset id="ticket_set" style="display: none;">
        <legend>Tickets</legend>
        <table id="ticketList">
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
    </fieldset>
    <a href="transfer.php">Transfer Tickets</a> | <a href="verify.php">Verify Tickets</a>
    '.$discretionary.'
    <fieldset>
        <legend>FAQ</legend>
        <a href="http://www.burningflipside.com/event/tickets/faq">Ticket FAQ</a>
    </fieldset>
    <div class="modal fade in" aria-hidden="false" id="ticket_id_modal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                        <h4 class="modal-title" id="modal_title">Full Ticket ID</h4>
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
                        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                        <h4 class="modal-title" id="modal_title">Ticket Information</h4>
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
                        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                        <h4 class="modal-title" id="modal_title">Edit Ticket</h4>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="show_short_code" class="col-sm-2 control-label">Short Code:</label>
                            <div class="col-sm-10">
                                <input class="form-control" type="text" name="show_short_code" id="show_short_code" readonly/>
                            </div>
                        </div>
                        <div class="clearfix visible-sm visible-md visible-lg"></div>
                        <div class="form-group">
                            <label for="edit_first_name" class="col-sm-2 control-label">First Name:</label>
                            <div class="col-sm-10">
                                <input class="form-control" type="text" name="edit_first_name" id="edit_first_name"/>
                            </div>
                        </div>
                        <div class="clearfix visible-sm visible-md visible-lg"></div>
                        <div class="form-group">
                            <label for="edit_last_name" class="col-sm-2 control-label">Last Name:</label>
                            <div class="col-sm-10">
                                <input class="form-control" type="text" name="edit_last_name" id="edit_last_name"/>
                            </div>
                        </div>
                        <div class="clearfix visible-sm visible-md visible-lg"></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-default" onclick="save_ticket()">Save</button><button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button></div>
                </div>
            </div>
        </div>
    </div>
</div>
';

}
$page->print_page();
// vim: set tabstop=4 shiftwidth=4 expandtab:
?>

