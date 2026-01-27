<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketAdminPage.php');
$page = new TicketAdminPage('Burning Flipside - Tickets');
$page->setTemplateName('admin-table-new.html');

$page->addWellKnownJS(JS_BOOTBOX);
$page->addJS('./js/libs/search.js');

$page->content['pageHeader'] = 'Pending Sales';
$page->content['table'] = array('id' => 'sales', 'headers'=>array('Cancel', 'Seller/Pool', 'Purchaser', 'Ticket Count', 'Square Link'));
$page->content['selectors'] = '<div id="alert"></div>';

$page->printPage();
// vim: set tabstop=4 shiftwidth=4 expandtab:

