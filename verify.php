<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketPage.php');
require_once('class.Ticket.php');
$page = new TicketPage('Burning Flipside - Tickets');

$page->add_js_from_src('js/verify.js');

if(!FlipSession::is_logged_in())
{
    $page->body .= '
<div id="content">
    <h1>Login</h1>
    <form id="login_form" role="form" action="https://profiles.burningflipside.com/ajax/login.php" method="POST">
        <input class="form-control" type="text" name="username" placeholder="Username or Email" required autofocus/>
        <input class="form-control" type="password" name="password" placeholder="Password" required/>
        <input type="hidden" name="return" value="'.$page->current_url().'"/>
        <input type="hidden" name="redirect" value="1"/>
        <button class="btn btn-lg btn-primary btn-block" type="submit">Login</button>
    </form>
</div>';
}
else
{
    $page->body .= '
<div id="content">
    <div class="input-group">
        <input type="text" class="form-control" placeholder="Short Ticket Code" id="short_code" onchange="verify_code()">
        <span class="input-group-addon" id="verified">?</span>
    </div>
</div>';
}
$page->print_page();
// vim: set tabstop=4 shiftwidth=4 expandtab:
?>
