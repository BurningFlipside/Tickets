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
die();

$minor = '<div id="minor_dialog" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true" aria-labelledby="minor-title">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 id="minor-title" class="modal-title">Important Information About Minors At Burning Flipside</h4>
            </div>
            <div class="modal-body">
                <p>Your ticket request indicates that you are brining a minor child to Burning Flipside. If this is a mistake please hit
                the correct button below and fix your request. If this is not a mistake and you are bringing minor children to Burning Flipside
                then you must read the content below.</p>
                <ul>
                    <li>For the protection of minor children and in accordance with Texas law, any minor child attending this event must be accompanied
                        by someone with legal authority to make decisions for the child.</li>
                    <li>A minor is anyone under the age of 18.</li>
                    <li>You must bring proper documentation to demonstrate your authority to make decisions for the child. For the child: physical copies of
                        birth certificate, passport, or other official document. For the parent: photo ID. An electronic copy of your documentation will be
                        stored along with the signed event waiver.</li>
                    <li>A special affidavit process is also available. See below:</li>
                </ul>
                <p>If you do not with to bring irreplaceable documents to Burning Flipside you may bring a notorized affidavit. NOTE: Many
                parents consider this process much easier and quicker at Gate.</p>
                <ol>
                    <li>Download a copy of the affidavit below</li>
                    <li>Print out the affidavit.</li>
                    <li>Bring the affidavit, along with your child\'s birth certificate and/or passport, to a Public Notary.</li>
                    <li>Have the affidavit notarized.</li>
                    <li>Put your other documents away and bring the notarized affidavit to Flipside with you.</li>
                    <li>Present the affidavit to the Gate staff with the minor\'s ticket and your legal ID. Gate will keep the affidavit for Flipside\'s records.</li>
                </ol>
                <center>
                <a href="static/MinorAffidavit03222010.doc" target="_blank"><img src="/images/MS_word_DOC_icon.svg" style="width: 40px; height: 40px;" alt="Word Doc"/></a>
                <a href="static/MinorAffidavit03222010.pdf" target="_blank"><img src="/images/Adobe_PDF_Icon.svg" style="width: 40px; height: 40px;" alt="PDF"/></a>
                </center>
                <input type="checkbox" id="minor_affirm" onchange="minor_affirm_clicked()">&nbsp;<label for="minor_affirm">I have read and understand the above policies.</label>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Oops! I don\'t have any children attending the event!</button>
                <button id="minor_dialog_continue" type="button" class="btn btn-primary" data-dismiss="modal" disabled>Continue</button>
            </div>
        </div>
    </div>
</div>';

if($page->user === null)
{
    $page->printPage();
    die();
}

$maxTotalTickets = $page->ticketSettings['max_tickets_per_request'];

$ticketDataTable = \Tickets\DB\TicketsDataTable::getInstance();
$currentTickets = $ticketDataTable->read(new \Tickets\DB\TicketDefaultFilter($page->user->mail));
$numberOfCurrentTickets = count($currentTickets);
if($currentTickets === false)
{
    $numberOfCurrentTickets = 0;
}

if($numberOfCurrentTickets >= $maxTotalTickets)
{
    $page->body .= '<div id="content">You currently have '.$numberOfCurrentTickets.' tickets registered to you. This means you cannot purchase any additional tickets using this system.</div>';
    $page->printPage();
    die();
}

$ticketTypeDataTable = \DataSetFactory::getDataTableByNames('tickets', 'TicketTypes');
$ticketTypes = $ticketTypeDataTable->read();
$ticketTypeCount = count($ticketTypes);
for($i = 0; $i < $ticketTypeCount; $i++)
{
    $type = $ticketTypes[$i];
    $type['count'] = 0;
    $ticketTypes[$type['typeCode']] = $type;
    unset($ticketTypes[$i]);
}

if($numberOfCurrentTickets != 0)
{
    for($i = 0; $i < $numberOfCurrentTickets; $i++)
    {
        $ticket = $currentTickets[$i];
        $ticketTypes[$ticket['type']]['count']++;
        if($ticketTypes[$ticket['type']]['count'] >= $ticketTypes[$ticket['type']]['max_per_request'])
        {
            $page->addNotification('You already have the maximum available '.$ticketTypes[$ticket['type']]['description'].' tickets and will not be able to order any more.', FlipPage::NOTIFICATION_WARNING, false);
        }
    }
}

$secondaryTable = \DataSetFactory::getDataTableByNames('tickets', 'SecondaryRequests');
$requests = $secondaryTable->read(new \Data\Filter('mail eq "'.$page->user->mail.'"'));
if(!empty($requests))
{
    $page->body .= '<div id="content">You have registered the maximum number of requests. You can reprint your request form <a href="#" onclick="getPDF();">here</a>.</div>';
    $page->printPage();
    die();
}

$secondaryTotalCount = 0;
$validTicketArray = $secondaryTable->read(false, array('valid_tickets'));
$validTicketArrayCount = count($validTicketArray);
for($i = 0; $i < $validTicketArrayCount; $i++)
{
    $tmp = json_decode($validTicketArray[$i]['valid_tickets']);
    $secondaryTotalCount += count($tmp);
}
if($secondaryTotalCount > $page->ticketSettings['secondaryTicketCount'])
{
    $page->body .= 'There have been too many requests. Sorry, we may open up more tickets later.';
    $page->printPage();
    die();
}

$page->body .= '<div id="content">
    <form id="questions">
        First you must answer a series of questions to prove you have read the Burning Flipside '.$page->ticketSettings['year'].' survival guide. You can locate the guide <a href="'.$page->wwwUrl.'/sg" target="_blank">here</a>.
        <div id="questionContent"></div>
        <div class="clearfix visible-sm visible-md visible-lg"></div>
        <br/><br/>
        <div class="g-recaptcha" data-sitekey="6LeCQhgUAAAAAAi1_JQ9143G8IiyWjEu9azpRGj-"></div>
        <button type="button" id="submitAnswer" class="btn btn-primary">Submit Answers</button>
    </form>    
<form id="request" tabindex="-1" aria-hidden="true" style="display:none;" data-ticketcount="'.$numberOfCurrentTickets.'">
    <div class="form-group">
        <label for="givenName" class="col-sm-2 control-label">First Name:</label>
        <div class="col-sm-10">
            <input class="form-control" id="givenName" name="givenName" type="text" required data-toggle="tooltip" data-placement="top" title="This is the first name for the mailing address." value="'.$page->user->givenName.'"/>
        </div>
    </div>
    <div class="clearfix visible-sm visible-md visible-lg"></div>
    <div class="form-group">
        <label for="sn" class="col-sm-2 control-label">Last Name:</label>
        <div class="col-sm-10">
            <input class="form-control" id="sn" name="sn" type="text" required data-toggle="tooltip" data-placement="top" title="This is the last name for the mailing address."  value="'.$page->user->sn.'"/>
        </div>
    </div>
    <div class="clearfix visible-sm visible-md visible-lg"></div>
    <div class="form-group">
        <label for="mail" class="col-sm-2 control-label">Email:</label>
        <div class="col-sm-10">
            <input class="form-control" type="text" name="mail" id="mail" readonly data-toggle="tooltip" data-placement="top" title="This is the email address used for futher communications. This email address has been set and confirmed by your profile. If you need to use a different email address please edit your profile."  value="'.$page->user->mail.'"/>
        </div>
    </div>
    <div class="form-group">
        <label for="c" class="col-sm-2 control-label">Country:</label>
        <div class="col-sm-10">
            <select class="form-control bfh-countries" id="c" name="c" data-country="'.$page->user->c.'" required data-toggle="tooltip" data-placement="top" title="The Country for the mailing address."></select>
        </div>
    </div>
    <div class="clearfix visible-sm visible-md visible-lg"></div>
    <div class="form-group">
        <label for="street" class="col-sm-2 control-label">Street Address:</label>
        <div class="col-sm-10">
            <textarea class="form-control" required id="street" name="street" rows="2" data-toggle="tooltip" data-placement="top" title="The street address for the mailing address.">'.$page->user->postalAddress.'</textarea>
        </div>
    </div>
    <div class="clearfix visible-sm visible-md visible-lg"></div>
    <div class="form-group">
        <label for="zip" class="col-sm-2 control-label">Postal/Zip Code:</label>
        <div class="col-sm-10">
            <input class="form-control" required id="zip" name="zip" type="text" data-toggle="tooltip" data-placement="top" title="The zip or postal code for the mailing address."  value="'.$page->user->postalCode.'"/>
        </div>
    </div>
    <div class="clearfix visible-sm visible-md visible-lg"></div>
    <div class="form-group">
        <label for="l" class="col-sm-2 control-label">City:</label>
        <div class="col-sm-10">
            <input class="form-control" required id="l" name="l" type="text" data-toggle="tooltip" data-placement="top" title="The city for the mailing address."  value="'.$page->user->l.'"/>
        </div>
    </div>
    <div class="clearfix visible-sm visible-md visible-lg"></div>
    <div class="form-group">
        <label for="st" class="col-sm-2 control-label">State:</label>
        <div class="col-sm-10">
            <select class="form-control bfh-states" required data-country="c" id="st" name="st" type="text" data-toggle="tooltip" data-placement="top" title="The state/province/or other subdivision of the country for the mailing address." data-state="'.$page->user->st.'"></select>
        </div>
    </div>
    <div class="clearfix visible-md visible-lg"></div>
    <table id="ticket_table" class="table">
        <thead>
            <tr>
                <th></th>
                <th>Age</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Ticket Cost</th>
            </tr>
        </thead>
        <tbody>';
foreach($ticketTypes as $type)
{
    if($type['count'] >= $type['max_per_request'])
    {
        continue;
    }
    $ticketId = $type['typeCode'].'_1';
    $page->body.='
            <tr>
                <td><input type="checkbox" id="enable_'.$ticketId.'" class="form-control"/></td>
                <td>'.$type['description'].'</td>
                <td>
                    <div class="form-group">
                        <input type="text" id="ticket_first_'.$ticketId.'" name="ticket_first_'.$ticketId.'" class="form-control"/>
                    </div>
                </td>
                <td>
                    <div class="form-group">
                        <input type="text" id="ticket_last_'.$ticketId.'" name="ticket_last_'.$ticketId.'" class="form-control"/>
                    </div>
                </td>
                <td id="cost_'.$ticketId.'">$'.$type['cost'].'</td>
            </tr>';
    if($type['count'] == 0)
    {
        $ticketId = $type['typeCode'].'_2';
        $page->body.='
            <tr>
                <td><input type="checkbox" id="enable_'.$ticketId.'" class="form-control"/></td>
                <td>'.$type['description'].'</td>
                <td><input type="text" id="ticket_first_'.$ticketId.'" name="ticket_first_'.$ticketId.'" class="form-control"/></td>
                <td><input type="text" id="ticket_last_'.$ticketId.'" name="ticket_last_'.$ticketId.'" class="form-control"/></td>
                <td id="cost_'.$ticketId.'">$'.$type['cost'].'</td>
            </tr>';
    }
}
$page->body.='
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3"></td>
                <th>Ticket Subtotal:</th>
                <th id="ticket_subtotal"></th>
            </tr>
        </tfoot>
    </table>
    <button type="submit" name="submit" class="btn btn-primary" id="submitRequest">Submit Request</button>
</form></div>'.$minor;

// vim: set tabstop=4 shiftwidth=4 expandtab:

