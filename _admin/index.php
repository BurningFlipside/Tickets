<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketPage.php');
$page = new TicketPage('Burning Flipside - Tickets');

$script_start_tag = $page->create_open_tag('script', array('src'=>'/js/jquery.dataTables.js'));
$script_close_tag = $page->create_close_tag('script');
$page->add_head_tag($script_start_tag.$script_close_tag);

$script_start_tag = $page->create_open_tag('script', array('src'=>'/js/Chart.min.js'));
$page->add_head_tag($script_start_tag.$script_close_tag);

$script_start_tag = $page->create_open_tag('script', array('src'=>'js/index.js'));
$page->add_head_tag($script_start_tag.$script_close_tag);

$css_tag = $page->create_open_tag('link', array('rel'=>'stylesheet', 'href'=>'/css/jquery.dataTables.css', 'type'=>'text/css'), true);
$page->add_head_tag($css_tag);

$css_tag = $page->create_open_tag('link', array('rel'=>'stylesheet', 'href'=>'css/admin.css', 'type'=>'text/css'), true);
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
    <div class="col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">
        <h1 class="page-header">Ticket Dashboard</h1>
            <table align="center" class="table-condensed">
                <tr>
                    <td>
                        <div style="width: 200px; height: 200px; display: table;" id="requests">
                            <h1 style="text-align: center; display: table-cell; vertical-align: middle;" id="request_count"></h1>
                        </div>
                        <h4 style="text-align: center;">Number of Requests</h4>
                    </td>
                    <td>
                        <canvas id="tickets" width="200" height="200"></canvas>
                        <h4 style="text-align: center;">Tickets</h4>
                    </td>
                </tr>
            </table>
    </div>
</div>
';

}
$page->print_page();
// vim: set tabstop=4 shiftwidth=4 expandtab:
?>

