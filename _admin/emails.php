<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketAdminPage.php');
$page = new TicketAdminPage('Burning Flipside - Tickets');

$page->addJS('//cdn.ckeditor.com/4.7.0/full/ckeditor.js', false);
$page->addJS('//cdn.ckeditor.com/4.7.0/full/adapters/jquery.js', false);

    $page->body .= '
<div class="row">
    <div class="col-lg-12">
        <select id="ticket_text_name" name="ticket_text_name" class="form-control" onchange="ticket_text_changed()">
            <option value="ticket_email_source" selected>Ticket Email</option>
            <option value="ticket_transfer_email_source">Transfer Email</option>
        </select>
    </div>
</div>
<div class="row">
    <textarea id="pdf-source" style="width: 100%"></textarea>
</div>
<div class="row">
    <button onclick="save()">Save</button>
</div>
<div class="row">
    {$year}            => The ticket year<br/>
    {$firstName}       => The ticket holder\'s First Name<br/>
    {$short_id}        => The short ticket id<br/>
    {$word_code}       => The "word code" version of the full ticket id<br/>
    {$name}            => The ticketed person\'s full name<br/>
    {$email}           => The ticketed person\'s email<br/>
    {$type}            => The ticket type<br/>
</div>
';

$page->printPage();
// vim: set tabstop=4 shiftwidth=4 expandtab:

