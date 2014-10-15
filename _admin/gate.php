<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketAdminPage.php');
require_once('class.FlipsideTicketDB.php');
$page = new TicketAdminPage('Burning Flipside - Tickets');

$page->add_js_from_src('/js/jquery.dataTables.js');
$page->add_js_from_src('js/gate.js');

$css_tag = $page->create_open_tag('link', array('rel'=>'stylesheet', 'href'=>'/css/jquery.dataTables.css', 'type'=>'text/css'), true);
$page->add_head_tag($css_tag);

$db = new FlipsideTicketDB();

    $page->body .= '
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Gate Graphs</h1>
            </div>
        </div>
        <div class="row">
            <div class="panel-group" id="accordion">
            </div>
        </div>
    </div>
</div>
';

$page->print_page();
// vim: set tabstop=4 shiftwidth=4 expandtab:
?>

