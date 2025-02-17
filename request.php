<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketPage.php');
$page = new TicketPage('Burning Flipside - Tickets');

$page->addWellKnownJS(JS_DATATABLE);
$page->addWellKnownJS(JS_BOOTSTRAP_FH, false);
$page->addWellKnownCSS(CSS_DATATABLE);
$page->addWellKnownCSS(CSS_BOOTSTRAP_FH);

$email = '';
if($page->user)
{
    $email = $page->user->mail;
}

    $page->body .= '
<div id="content">
    <form id="request" role="form">
        <fieldset id="request_set">
            <legend>Ticket Request</legend>
            <div class="row">
                <label for="mail" class="col-sm-2 control-label">Request ID:</label>
                <div class="col-sm-10">
                    <input class="form-control" type="text" name="request_id" id="request_id" readonly data-toggle="tooltip" data-placement="top" title="This request ID uniquely identifies your request. Don\'t worry, it should be the same as in previous years." disabled/>
                </div>
            </div>
            <div class="row">
                <label for="givenName" class="col-sm-2 control-label">First Name:</label>
                <div class="col-sm-10">
                    <input class="form-control" id="givenName" name="givenName" type="text" required data-toggle="tooltip" data-placement="top" title="This is the first name for the mailing address."/>
                </div>
            </div>
            <div class="row">
                <label for="sn" class="col-sm-2 control-label">Last Name:</label>
                <div class="col-sm-10">
                    <input class="form-control" id="sn" name="sn" type="text" required data-toggle="tooltip" data-placement="top" title="This is the last name for the mailing address."/>
                </div>
            </div>
            <div class="row">
                <label for="mail" class="col-sm-2 control-label">Email:</label>
                <div class="col-sm-10">
                    <input class="form-control" type="text" name="mail" id="mail" readonly data-toggle="tooltip" data-placement="top" title="This is the email address used for further communications. This email address has been set and confirmed by your profile. If you need to use a different email address please edit your profile." value="'.$email.'"/>
                </div>
            </div>
            <div class="row">
                <label for="c" class="col-sm-2 control-label">Country:</label>
                <div class="col-sm-10">
                    <select class="form-control bfh-countries" id="c" name="c" data-country="US" required data-toggle="tooltip" data-placement="top" title="The Country for the mailing address."></select>
                </div>
            </div>
            <div class="row">
                <label for="mobile" class="col-sm-2 control-label">Cell Number:</label>
                <div class="col-sm-10">
                    <input id="mobile" name="mobile" type="text" class="form-control bfh-phone" required data-country="c" data-toggle="tooltip" data-placement="top" title="This phone number may be used by the ticket team in case of a problem with your request."/>
                </div>
            </div>
            <div class="row">
                <label for="street" class="col-sm-2 control-label">Street Address:</label>
                <div class="col-sm-10">
                    <textarea class="form-control" required id="street" name="street" rows="2" data-toggle="tooltip" data-placement="top" title="The street address for the mailing address."></textarea>
                </div>
            </div>
            <div class="row">
                <label for="zip" class="col-sm-2 control-label">Postal/Zip Code:</label>
                <div class="col-sm-10">
                    <input class="form-control" required id="zip" name="zip" type="text" data-toggle="tooltip" data-placement="top" title="The zip or postal code for the mailing address."/>
                </div>
            </div>
            <div class="row">
                <label for="l" class="col-sm-2 control-label">City:</label>
                <div class="col-sm-10">
                    <input class="form-control" required id="l" name="l" type="text" data-toggle="tooltip" data-placement="top" title="The city for the mailing address."/>
                </div>
            </div>
            <div class="row">
                <label for="st" class="col-sm-2 control-label">State:</label>
                <div class="col-sm-10">
                    <select class="form-control bfh-states" required data-country="c" id="st" name="st" type="text" data-toggle="tooltip" data-placement="top" title="The state/province/or other subdivision of the country for the mailing address."></select>
                </div>
            </div>
            <div class="row">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="paymentMethod" id="paymentTraditional">
                    <label class="form-check-label" for="paymentTraditional">
                        I want to pay for my tickets with a Money Order or Cashier\'s Check.
                    </label>
                    </div>
                    <div class="form-check">
                    <input class="form-check-input" type="radio" name="paymentMethod" id="paymentCC">
                    <label class="form-check-label" for="paymentCC">
                        I want to pay for my tickets later with a Credit Card. I understand that the tickets will <a href="#" data-bs-toggle="modal" data-bs-target="#costModal">cost more</a> and that all Money Order requests will be granted before mine.
                    </label>
                </div>
            </div>
            <table id="ticket_table" class="table" hidden>
                <thead>
                    <tr>
                        <th></th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Age</th>
                        <th>Ticket Cost</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3"><div id="new_ticket_tooltip"><button type="button" class="btn btn-primary" id="add_new_ticket">Add New Tickets</button></div></td>
                        <th>Ticket Subtotal:</th>
                        <th id="ticket_subtotal"></th>
                    </tr>
                </tfoot>
            </table>
        <div id="traditionalPaymentDetails" class="row" hidden>
            <fieldset id="donations">
                <legend>Donation</legend>
            </fieldset>
            <fieldset>
                <legend>Envelope Art</legend>
                <input id="envelopeArt" name="envelopeArt" type="checkbox"/>&nbsp;
                <label for="envelopeArt">
                    Allow AAR to use my envelope art in the Survival Guide or Website. It will be credited by the name on the return address.
                </label>
            </fieldset>
        </div>
        <div id="creditPaymentDetails" class="row" hidden>
            <div class="col">
                Donations are not accepted for Credit Card payments. However you should totally still <a href="https://ignitionphilter.com/donate/">donate</a> to Ignition Philter.

                You will be notified at the above email if you have been chosen to get tickets. You will then have two weeks to pay for your tickets. If you do not pay within two weeks your tickets will be released to the next person in line.
            </div>
        </div>
        <div class="row">
            <div class="col col-sm2">
                <button type="submit" name="submit" id="submit" class="btn btn-primary" hidden>Submit Request</button>
            </div>
        </div>
        </fieldset>
    </form>
</div>
<div id="minor_dialog" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true" aria-labelledby="minor-title">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 id="minor-title" class="modal-title">Important Information About Minors At Burning Flipside</h4>
            </div>
            <div class="modal-body">
                <p>Your ticket request indicates that you are bringing a minor child to Burning Flipside. If this is a mistake please hit
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
                <input type="checkbox" id="minor_affirm" onchange="minorAffirmClicked()">&nbsp;<label for="minor_affirm">I have read and understand the above policies.</label>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Oops! I don\'t have any children attending the event!</button>
                <button id="minor_dialog_continue" type="button" class="btn btn-primary" data-dismiss="modal" disabled>Continue</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="costModal" tabindex="-1" aria-labelledby="costModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="costModalLabel">Why does it cost more to pay with a Credit Card?</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        There are several reasons why it costs more to pay with a credit card.
        <ul>
            <li>First, the credit card companies charge a fee for each transaction. This fee is typically a percentage of the total amount charged, plus a fixed fee per transaction. This fee is passed on to the customer.</li>
            <li>Second, the non-profit that runs Burning Flipside is allowed a sales tax free day, the organization uses that for ticket opening. Therefore any tickets bought on any other day must pay sales tax. This tax is passed on to the customer.</li>
            <li>Third, when using Credit Cards versus Money Orders there are things known as "Charge Backs" which is when you or your credit card company dispute the charges. In addition to us not getting the money from the transaction, there are also fees involved with this. So a small fee (rounding to the nearest dollar) has been included to cover charge backs.</li>
        </ul>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
';

$page->printPage();
// vim: set tabstop=4 shiftwidth=4 expandtab:

