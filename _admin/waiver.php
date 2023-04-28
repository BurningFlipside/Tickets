<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketAdminPage.php');
$page = new TicketAdminPage('Burning Flipside - Tickets');

$page->addJS('js/extern/pdf.js');
$page->addJS('js/extern/web/pdf_viewer.js');
$page->addJS('https://unpkg.com/pdf-lib');
$page->addWellKnownJS(JS_BOOTBOX);

$page->addCSS('css/pdf.css');
$page->addCSS('js/extern/web/pdf_viewer.css');

$page->body .= '<div id="content" style="position: absolute; background-color: gray;"><div id="viewer" class="pdfViewer"></div><button id="savePDF" type="button" class="btn btn-primary">Continue</button><br/><br/><br/><br/></div>';

$page->printPage();
