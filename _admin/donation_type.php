<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketAdminPage.php');
$page = new TicketAdminPage('Burning Flipside - Tickets');

$page->add_js_from_src('js/donation_type.js');

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
    <ul id="donation_type_nav" class="nav nav-tabs" role="tablist">
    </ul>

    <div id="donation_type_content" class="tab-content">
    </div>
</div>
';

}
$page->print_page();
// vim: set tabstop=4 shiftwidth=4 expandtab:
?>

