<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketAdminPage.php');
require_once('class.FlipsideTicketDB.php');
$page = new TicketAdminPage('Burning Flipside - Tickets');

$page->add_js_from_src('/js/jquery.dataTables.js');
$page->add_js_from_src('js/tickets.js');

$css_tag = $page->create_open_tag('link', array('rel'=>'stylesheet', 'href'=>'/css/jquery.dataTables.css', 'type'=>'text/css'), true);
$page->add_head_tag($css_tag);

$db = new FlipsideTicketDB();
$ticket_count = $db->getTicketCount();

if($ticket_count == 0 || $ticket_count === FALSE)
{
    $page->add_notification('There are currently no tickets created! Click <a href="ticket_gen.php" class="alert-link">here</a> to generate tickets.');
}

    $page->body .= '
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Tickets</h1>
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
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                        <h4 class="modal-title">Ticket</h4>
                    </div>
                    <div class="modal-body">
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
                                    <input class="form-control" type="text" name="firstName" id="firstName"/>
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
                            <div class="form-group">
                                <label for="email" class="col-sm-2 control-label">Email:</label>
                                <div class="col-sm-10">
                                    <input class="form-control" type="text" name="email" id="email"/>
                                </div>
                            </div>
                            <div class="clearfix visible-sm visible-md visible-lg"></div>
                            <div class="form-group">
                                <label for="request_id" class="col-sm-2 control-label">Request ID:</label>
                                <div class="col-sm-10">
                                    <input class="form-control" type="text" name="request_id" id="request_id"/>
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
                        </form>
                        <div class="col-md-6" style="text-align: center;"><a onclick="prev_ticket()" style="cursor: pointer;" id="left_arrow"><span class="glyphicon glyphicon-chevron-left"></span></a></div>
                        <div class="col-md-6" style="text-align: center;"><a onclick="next_ticket()" style="cursor: pointer;" id="right_arrow"><span class="glyphicon glyphicon-chevron-right"></span></a></div>
                        <div class="clearfix visible-sm visible-md visible-lg"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" onclick="save_ticket()">Save changes</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
';

$page->print_page();
// vim: set tabstop=4 shiftwidth=4 expandtab:
?>

