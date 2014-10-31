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
                        <th>Type</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
';

$page->print_page();
// vim: set tabstop=4 shiftwidth=4 expandtab:
?>

