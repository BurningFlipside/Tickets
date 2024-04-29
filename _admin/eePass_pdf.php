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
        <h1 class="page-header">Early Entry Pass PDF</h1>
    </div>
</div>
<div class="row">
    <textarea id="pdf-source" style="width: 100%"></textarea>
</div>
<div class="row">
    <button onclick="genPreview()">Preview</button><button onclick="save()">Save</button>
</div>
<div class="row">
    {$barcode}         => The barcode<br/>
    {$qr}              => The QR Code<br/>
    {$year}            => The pass year<br/>
    {$pass_id}         => The pass id<br/>
    {$email}           => The current pass owner\'s email<br/>
    {$type}            => The pass type<br/>
</div>
';

$page->printPage();
// vim: set tabstop=4 shiftwidth=4 expandtab:
