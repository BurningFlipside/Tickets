<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketPage.php');
$page = new TicketPage('Burning Flipside - Tickets');

$script_start_tag = $page->create_open_tag('script', array('src'=>'js/ticket_type.js'));
$script_close_tag = $page->create_close_tag('script');
$page->add_head_tag($script_start_tag.$script_close_tag);

$css_tag = $page->create_open_tag('link', array('rel'=>'stylesheet', 'href'=>'/css/jquery.dataTables.css', 'type'=>'text/css'), true);
$page->add_head_tag($css_tag);

$css_tag = $page->create_open_tag('link', array('rel'=>'stylesheet', 'href'=>'css/admin.css', 'type'=>'text/css'), true);
$page->add_head_tag($css_tag);

$user = FlipSession::get_user(TRUE);
$is_admin = $user->isInGroupNamed("TicketAdmins");
if(!$is_admin)
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
    <ul id="ticket_type_nav" class="nav nav-tabs" role="tablist">
    </ul>

    <div id="ticket_type_content" class="tab-content">
    </div>
</div>
';

}
$page->print_page();
// vim: set tabstop=4 shiftwidth=4 expandtab:
?>

