<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketAdminPage.php');
require_once('class.FlipsideTicketDB.php');
$page = new TicketAdminPage('Burning Flipside - Tickets');

$script_start_tag = $page->create_open_tag('script', array('src'=>'/js/jquery.dataTables.js'));
$script_close_tag = $page->create_close_tag('script');
$page->add_head_tag($script_start_tag.$script_close_tag);

$script_start_tag = $page->create_open_tag('script', array('src'=>'js/critvols.js'));
$page->add_head_tag($script_start_tag.$script_close_tag);

$css_tag = $page->create_open_tag('link', array('rel'=>'stylesheet', 'href'=>'/css/jquery.dataTables.css', 'type'=>'text/css'), true);
$page->add_head_tag($css_tag);

$page->body .= '
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Critical Volunteers</h1>
            </div>
        </div>
        <div class="row">
            <div class="panel-group" id="accordion">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="panel-title"><a data-toggle="collapse" data-parent="#accordion" href="#single">Single Critical Volunteer</a></h4>
                    </div>
                    <div id="single" class="panel-collapse collapse in">
                        <div class="panel-body">
                            <form class="form-inline" role="form">
                                Search Type: <select id="search_type" name="search_type" class="form-control" onchange="search_type_changed(this)">
                                    <option value="*">All</option>
                                    <option value="request_id">Request ID</option>
                                    <option value="email">Email</option>
                                    <option value="first">First Name</option>
                                    <option value="last">Last Name</option>
                                </select>
                                <input type="text" id="search" name="search" class="form-control"/>
                                <button class="btn btn-default" id="search_btn">Search</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="panel-title"><a data-toggle="collapse" data-parent="#accordion" href="#bulk">Bulk Critical Volunteers</a></h4>
                    </div>
                    <div id="bulk" class="panel-collapse collapse">
                        <div class="panel-body">
                            <p>You can upload a file with a different request ID or email address seperated by new lines or commas. Each request will then be set to crit vol status. A summary of the actions taken both requests that were changed and requests that were not will be printed to the screen when it is done.</p>
                            <form class="form-inline" role="form">
                                Critvol File: <input class="form-control" type="file" name="file" id="file"/>
                            </form>
                        </div>
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

