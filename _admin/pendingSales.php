<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketAdminPage.php');
$page = new TicketAdminPage('Burning Flipside - Tickets');
$page->setTemplateName('admin-table.html');

$page->addWellKnownJS(JS_DATATABLE, false);
$page->addWellKnownCSS(CSS_DATATABLE);
$page->addWellKnownJS(JS_BOOTBOX);

$page->content['pageHeader'] = 'Pending Sales';
$page->content['table'] = array('id' => 'sales', 'headers'=>array('Cancel', 'Seller/Pool', 'Purchaser', 'Ticket Count', 'Square Link'));

$page->printPage();
// vim: set tabstop=4 shiftwidth=4 expandtab:

