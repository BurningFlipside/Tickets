<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('/var/www/common/Autoload.php');
require_once('app/TicketAutoload.php');
$old_request = \Tickets\Flipside\FlipsideTicketRequest::getByIDAndYear($_GET['id'], $_GET['year']);
unset($old_request['revisions']);
unset($old_request['modifiedBy']);
unset($old_request['modifiedByIP']);
unset($old_request['modifiedOn']);
unset($old_request['total_due']);
unset($old_request['total_received']);
unset($old_request['crit_vol']);
unset($old_request['protected']);
unset($old_request['status']);
unset($old_request['private_status']);
unset($old_request['bucket']);
unset($old_request['comments']);
unset($old_request['test']);
$old_request = json_decode(json_encode($old_request));
print_r($old_request); die();
\Tickets\Flipside\FlipsideTicketRequest::createTicketRequest($old_request);
header('Location: request.php');
?>
