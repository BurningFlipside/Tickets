<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketAdminPage.php');
$page = new TicketAdminPage('Burning Flipside - Tickets');

$page->addWellKnownJS(JS_DATATABLE);
$page->addWellKnownCSS(CSS_DATATABLE);

$page->body .= '
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Common Reports</h1>
            </div>
        </div>
        <div class="row">
            <h3>Mailings</h3>
            <ul>
              <li>
                Survival Guide Mail List: <a href="../api/v1/requests_w_tickets?$select=request_id,street,l,st,zip,first,last,type&$filter=year%20eq%20current%20and%20private_status%20eq%206&fmt=csv">CSV</a> | <a href="../api/v1/requests_w_tickets?$select=request_id,street,l,st,zip,first,last,type&$filter=year%20eq%20current%20and%20private_status%20eq%206&fmt=xls">XLS</a> | <a href="../api/v1/requests_w_tickets?$select=request_id,street,l,st,zip,first,last,type&$filter=year%20eq%20current%20and%20private_status%20eq%206&fmt=xlsx">XLSX</a>
              </li>
              <li>
                  Minor Mailouts List: <a href="../api/v1/requests_w_tickets/minorMails?$format=csv">CSV</a> | <a href="../api/v1/requests_w_tickets/minorMails?$format=xls">XLS</a> | <a href="../api/v1/requests_w_tickets/minorMails?$format=xlsx">XLSX</a>
              </li>
            </ul>
        </div>
        <div class="row">
            <h3>Pre-Gate Lists</h3>
            <ul>
              <li>
                  Sold Tickets (alpha by last name): <a href="../api/v1/tickets?$filter=year%20eq%20current%20and%20sold%20eq%201&$orderby=lastName&$select=hash,firstName,lastName,email,request_id,type,guardian_first,guardian_last&$format=csv">CSV</a> | <a href="../api/v1/tickets?$filter=year%20eq%20current%20and%20sold%20eq%201&$orderby=lastName&$select=hash,firstName,lastName,email,request_id,type,guardian_first,guardian_last&$format=xls">XLS</a> | <a href="../api/v1/tickets?$filter=year%20eq%20current%20and%20sold%20eq%201&$orderby=lastName&$select=hash,firstName,lastName,email,request_id,type,guardian_first,guardian_last&$format=xlsx">XLSX</a>
              </li>
              <li>
                  Sold Tickets (alpha by hash): <a href="../api/v1/tickets?$filter=year%20eq%20current%20and%20sold%20eq%201&$orderby=hash&$select=hash,firstName,lastName,email,request_id,type,guardian_first,guardian_last&$format=csv">CSV</a> | <a href="../api/v1/tickets?$filter=year%20eq%20current%20and%20sold%20eq%201&$orderby=hash&$select=hash,firstName,lastName,email,request_id,type,guardian_first,guardian_last&$format=xls">XLS</a> | <a href="../api/v1/tickets?$filter=year%20eq%20current%20and%20sold%20eq%201&$orderby=hash&$select=hash,firstName,lastName,email,request_id,type,guardian_first,guardian_last&$format=xlsx">XLSX</a>
              </li>
              <li>
                  Tuesday Early Entry: <a href="../api/v1/tickets?$filter=year%20eq%20current%20and%20sold%20eq%201%20and%20earlyEntryWindow%20eq%202&$orderby=lastName&$select=hash,firstName,lastName,email,request_id,type,guardian_first,guardian_last&$format=csv">CSV</a> | <a href="../api/v1/tickets?$filter=year%20eq%20current%20and%20sold%20eq%201%20and%20earlyEntryWindow%20eq%202&$orderby=lastName&$select=hash,firstName,lastName,email,request_id,type,guardian_first,guardian_last&$format=xls">XLS</a> | <a href="../api/v1/tickets?$filter=year%20eq%20current%20and%20sold%20eq%201%20and%20earlyEntryWindow%20eq%202&$orderby=lastName&$select=hash,firstName,lastName,email,request_id,type,guardian_first,guardian_last&$format=xlsx">XLSX</a>
              </li>
              <li>
                  Wednesday Infrastructure Setup: <a href="../api/v1/tickets?$filter=year%20eq%20current%20and%20sold%20eq%201%20and%20earlyEntryWindow%20eq%201&$orderby=lastName&$select=hash,firstName,lastName,email,request_id,type,guardian_first,guardian_last&$format=csv">CSV</a> | <a href="../api/v1/tickets?$filter=year%20eq%20current%20and%20sold%20eq%201%20and%20earlyEntryWindow%20eq%201&$orderby=lastName&$select=hash,firstName,lastName,email,request_id,type,guardian_first,guardian_last&$format=xls">XLS</a> | <a href="../api/v1/tickets?$filter=year%20eq%20current%20and%20sold%20eq%201%20and%20earlyEntryWindow%20eq%201&$orderby=lastName&$select=hash,firstName,lastName,email,request_id,type,guardian_first,guardian_last&$format=xlsx">XLSX</a>
              </li>
              <li>
                  Wednesday Art/Theme Camp EA: <a href="../api/v1/tickets?$filter=year%20eq%20current%20and%20sold%20eq%201%20and%20earlyEntryWindow%20eq%200&$orderby=lastName&$select=hash,firstName,lastName,email,request_id,type,guardian_first,guardian_last&$format=csv">CSV</a> | <a href="../api/v1/tickets?$filter=year%20eq%20current%20and%20sold%20eq%201%20and%20earlyEntryWindow%20eq%200&$orderby=lastName&$select=hash,firstName,lastName,email,request_id,type,guardian_first,guardian_last&$format=xls">XLS</a> | <a href="../api/v1/tickets?$filter=year%20eq%20current%20and%20sold%20eq%201%20and%20earlyEntryWindow%20eq%200&$orderby=lastName&$select=hash,firstName,lastName,email,request_id,type,guardian_first,guardian_last&$format=xlsx">XLSX</a>
              </li>
            </ul>
        </div>
    </div>
</div>
';

$page->printPage();
// vim: set tabstop=4 shiftwidth=4 expandtab:
?>

