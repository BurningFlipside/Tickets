<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketAdminPage.php');
require_once('class.FlipsideTicketDB.php');
$page = new TicketAdminPage('Burning Flipside - Tickets');

$page->add_js_from_src('/js/jquery.dataTables.js');
$page->add_js_from_src('js/request_tickets.js');

$css_tag = $page->create_open_tag('link', array('rel'=>'stylesheet', 'href'=>'/css/jquery.dataTables.css', 'type'=>'text/css'), true);
$page->add_head_tag($css_tag);

$db = new FlipsideTicketDB();
$years = $db->getAllYears();

$options = '';
for($i = 0; $i < count($years); $i++)
{
    if($years[$i] == FlipsideTicketDB::getTicketYear())
    {
        $options .= '<option value="'.$years[$i].'" selected>'.$years[$i].'</option>';
    }
    else
    {
        $options .= '<option value="'.$years[$i].'">'.$years[$i].'</option>';
    }
}

    $page->body .= '
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Requested Tickets</h1>
            </div>
        </div>
        <div class="row">
            Request Year: <select id="year" onchange="change_year(this)">
            '.$options.'
            </select>
            <table class="table" id="tickets">
                <thead>
                    <th>Request ID</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Type</th>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>
';

$page->print_page();
// vim: set tabstop=4 shiftwidth=4 expandtab:
?>

