<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketPage.php');
$page = new TicketPage('Burning Flipside - Tickets');

$script_start_tag = $page->create_open_tag('script', array('src'=>'/js/jquery.dataTables.js'));
$script_close_tag = $page->create_close_tag('script');
$page->add_head_tag($script_start_tag.$script_close_tag);

$script_start_tag = $page->create_open_tag('script', array('src'=>'js/index.js'));
$page->add_head_tag($script_start_tag.$script_close_tag);

$css_tag = $page->create_open_tag('link', array('rel'=>'stylesheet', 'href'=>'/css/jquery.dataTables.css', 'type'=>'text/css'), true);
$page->add_head_tag($css_tag);

if(!FlipSession::is_logged_in())
{
    $page->body .= '
<div id="content">
    <h1>You must log in to access the Burning Flipside Ticket system!</h1>
</div>';
}
else
{
    $page->body .= '
<div id="content">
    <fieldset id="request_set">
        <legend>Ticket Request</legend>
    </fieldset>
    <fieldset id="ticket_set" style="display: none;">
        <legend>Tickets</legend>
        <table id="ticketList">
            <thead>
                <tr>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Short Ticket Code</th>
                </tr>
             </thead>
        </table>
    </fieldset>
</div>
';

}
$page->print_page();
// vim: set tabstop=4 shiftwidth=4 expandtab:
?>

