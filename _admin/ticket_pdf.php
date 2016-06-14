<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketAdminPage.php');
$page = new TicketAdminPage('Burning Flipside - Tickets');

$page->addJSByURI('//cdn.ckeditor.com/4.4.5/full/ckeditor.js', false);
$page->addJSByURI('//cdn.ckeditor.com/4.4.5/full/adapters/jquery.js', false);
$page->addJSByURI('js/ticket_pdf.js');

    $page->body .= '
<div class="row">
    <div class="col-lg-12">
        <h1 class="page-header">Ticket PDF</h1>
    </div>
</div>
<div class="row">
    <textarea id="pdf-source" style="width: 100%"></textarea>
</div>
<div class="row">
    <button onclick="gen_preview()">Preview</button><button onclick="save()">Save</button>
</div>
<div class="row">
    {$barcode}         => The barcode<br/>
    {$transfer_qr}     => Ticket Transfer QR Code<br/>
    {$year}            => The ticket year<br/>
    {$ticket_id}       => The ticket id<br/>
    {$short_id}        => The short ticket id<br/>
    {$word_code}       => The "word code" version of the full ticket id<br/>
    {$name}            => The ticketed person\'s full name<br/>
    {$email}           => The ticketed person\'s email<br/>
    {$type}            => The ticket type<br/>
</div>
';

$page->print_page();
// vim: set tabstop=4 shiftwidth=4 expandtab:

