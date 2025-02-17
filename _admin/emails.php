<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketAdminPage.php');
$page = new TicketAdminPage('Burning Flipside - Tickets');

$page->addJS('../node_modules/ckeditor5/dist/browser/ckeditor5.umd.js', false);
$page->addCSS('../node_modules/ckeditor5/dist/browser/ckeditor5.css', false);

    $page->body .= '
<div class="row">
    <div class="col-lg-12">
        <select id="ticketTextName" name="ticket_text_name" class="form-control">
            <option value="ticket_email_source" selected>Ticket Email</option>
            <option value="ticket_transfer_email_source">Transfer Email</option>
            <option value="square_purchase_email_source">Square Purchase Email</option>
            <option value="square_request_purchase_email_source">Square Request Purchase Email</option>
        </select>
    </div>
</div>
<div class="row">
    <textarea id="pdf-source" style="width: 100%"></textarea>
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

