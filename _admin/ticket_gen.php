<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketAdminPage.php');
require_once('../app/TicketAutoload.php');
$page = new TicketAdminPage('Burning Flipside - Tickets');

$page->add_js_from_src('js/ticket_gen.js');

$types = \Tickets\TicketType::getAllTicketTypes();
$dataTable = \Tickets\DB\TicketsDataTable::getInstance();

$type_table = '<table><thead><th colspan="2">Current Counts</th></thead>';
foreach($types as $type)
{
    $count = $dataTable->count(new \Data\Filter("typeCode eq '$type->typeCode'"));
    if($count === false)
    {
        $count = 0;
    }
    $type_table .= "<tr><td>$type->description</td><td>$count</td></tr>";
}
$type_table .= '</table>';

$new_table = '<table><thead><th colspan="2">Additional Counts</th></thead>';
foreach($types as $type)
{
    $new_table .= "<tr><td>$type->description</td><td><input type='text' id='$type->typeCode' name='$type->typeCode'/></td></tr>";
}
$new_table .= '</table>';

$page->body .= '
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Ticket Generation</h1>
            </div>
        </div>
        <div class="row">
            '.$type_table.'
        </div>
        <br/>
        <div class="row">
            <form id="gen_form">
                '.$new_table.'
                <div class="form-group">
                    <input type="checkbox" id="auto_populate" name="auto_populate" checked/>
                    <label for="auto_populate" class="col-sm-2 control-label">Auto populate tickets from valid recieved requests?</label>
                </div>
                <input type="hidden" name="action" value="generate"/>
                <button type="button" class="btn btn-default" onclick="gen_tickets(this)">Generate Tickets</button>
            </form>
        </div>
    </div>
</div>
';

$page->print_page();
// vim: set tabstop=4 shiftwidth=4 expandtab:
?>

