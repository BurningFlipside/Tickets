<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketPage.php');
$page = new TicketPage('Burning Flipside - Tickets');

if(!\Flipside\FlipSession::isLoggedIn())
{
    $page->body .= '
<div id="content">
    <h1>You must <a data-toggle="modal" data-target="#login-dialog" style="cursor: pointer;">log in <span class="fa fa-sign-in"></span></a> to access the Burning Flipside Ticket system!</h1>
</div>';
}
else
{
    $page->body .= '
<div id="content">
    <div class="input-group">
        <input type="text" class="form-control" placeholder="Short Ticket Code" id="short_code" onchange="verifyCode()">
        <div class="input-group-append">
          <span class="input-group-text" id="verified">?</span>
        </div>
    </div>
</div>';
}
$page->printPage();
// vim: set tabstop=4 shiftwidth=4 expandtab:
