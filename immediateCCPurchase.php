<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketPage.php');
$page = new TicketPage('Burning Flipside - Tickets');

$page->addWellKnownJS(JS_BOOTSTRAP_FH);
$page->addWellKnownCSS(CSS_BOOTSTRAP_FH);

$settings = \Tickets\DB\TicketSystemSettings::getInstance();
if(!isset($settings['ccPurchaseOn']) || $settings['ccPurchaseOn'] != '1')
{
    $page->body .= '<div class="alert alert-danger">Credit Card Purchases are currently disabled. Please try again later.</div>';
    $page->printPage();
    exit(0);
}

$page->body .= '<div class="alert alert-info">Please note that all credit card transactions will incur a 3% fee. This fee is not refundable. Additionally you will be charged sales tax. These are already included in the amounts below.</div>';
$page->body .= 'Hello welcome to the official Burning Flipside secondary credit card only sale. Due to the fact that we did not sell out in the initial window and need to sell more tickets in order to make the event happen, we are opening up a secondary sale. This sale is only for credit card purchases.';
$page->body .= '<table id="ticketTable" class="table table-striped table-bordered table-hover">';
$page->printPage();