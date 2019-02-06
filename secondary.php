<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketPage.php');
$page = new TicketPage('Burning Flipside - Tickets');

$page->addWellKnownJS(JS_BOOTSTRAP_FH);
$page->addWellKnownCSS(CSS_BOOTSTRAP_FH);
$page->addJS('js/secondary.js');
$page->addJS('https://www.google.com/recaptcha/api.js');

$page->body = 'There are no secondary ticket sales this year.';
$page->printPage();

// vim: set tabstop=4 shiftwidth=4 expandtab:

