<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketPage.php');
$page = new TicketPage('Burning Flipside - Tickets');

$script_start_tag = $page->create_open_tag('script', array('src'=>'/js/jquery.dataTables.js'));
$script_close_tag = $page->create_close_tag('script');
$page->add_head_tag($script_start_tag.$script_close_tag);

$script_start_tag = $page->create_open_tag('script', array('src'=>'js/index.js'));
$page->add_head_tag($script_start_tag.$script_close_tag);

$css_tag = $page->create_open_tag('link', array('rel'=>'stylesheet', 'href'=>'/css/jquery.dataTables.css', 'type'=>'text/css'), true);
$page->add_head_tag($css_tag);

$faq = array(
    'Can I get on a mailing list for ticket information?' => 'Why yes, you can. And what a great idea! 
Just sign up for the low-traffic "Announce" email distribution list at <a href="http://www.BurningFlipside.com/email-lists">http://www.BurningFlipside.com/email-lists</a>.
When you create a ticket registration, you will be automatically subscribed to both the Announce list and the Ticket-Business list. You will remain on the Announce list unless you unsubscribe. Your Ticket-Business list subscription is specific to each year\'s ticket registrations, we clear it out annually. Announce is the channel for communicating official Flipside information to anyone interested. We use Ticket-Business for information targeted to people with a ticket registration this year. You may choose to unsubscribe from either list.',
    'When are tickets available?' => 'Flipside tickets are never sold at the event gate and every participant must have a ticket for entry.<br/>The best way to reliably get information about Burning Flipside ticket sales is to subscribe to the low-traffic email list for official Flipside communications, "Announce." Subscribe to the Announce List (and other email lists) <a href="http://www.BurningFlipside.com/email-lists">here</a>.<br/>Usually, ticket information for the next Burning Flipside is announced in December each year and the ticket request registration process begins in December or January.',
    'What do tickets cost?' => 'Ticket prices are determined each year by Austin Artistic Reconstruction, LLC (aka "the LLC") based upon the Flipside budget. Prices usually are announced the prior November-December along with ticket sales/distribution information.',
    'How do I get tickets?' => 'General information:<br/>To request tickets for Burning Flipside you need to do 3 things:<br/><ol><li>Register online during the ticket registration window in January</li><li>Put together your decorated envelope with all the right stuff (money order/cashier\'s check/teller\'s check, printed ticket request form, correct address, etc.)</li><li>Mail your ticket request so that it is postmarked during the specified date range in January.</li></ol>
For Burning Flipside 2015 - The Wizards Of Odd:
<ol>
    <li>Fill out the ticket registration form between TBD and TBD.</li>
        <ul>
            <li>Limit your ticket registration to no more than two adult tickets and a total of two teen, kid and/or child tickets.</li>
            <li>If you are requesting two adult tickets and do not yet know the name of the person who will use the second ticket, just enter your own name also for that ticket. You can enter "[Your Name] 1" and "[Your Name] 2" for the two names to keep the ticket assignments clear.</li>
            <li>You will need to fill in the names of kids or children for whom you are requesting tickets. Kid or child tickets cannot be used by adults.</li>
        </ul>
    <li>Click the "Save" button at the bottom of your ticket registration page.</li>
    <li>Check your email for the message from Flipside Tickets with subject line: Flipside 20145 Ticket Request Confirmation & Mail-In Form. That message includes the link to your mail-in Ticket Request form (a PDF to print).</li>
        <ul><li>Fliptickets will email you every time you save your ticket registration. If you make multiple changes to your registration, make sure that the Ticket Request form you put in the mailing envelope is the correct, current one.</li></ul>
    <li>Get a money order (or cashier\'s check or teller\'s check) for the Amount Due shown on your Ticket Request form and make it payable to: Austin Artistic Reconstruction.</li>
        <ul>
            <li>Write your ticket request ID number, which is shown on your ticket request form, in a comments space or near the top of the payment.</li>
            <li>Sign the money order if it is a type that requires your signature.</li>
            <li>For help with how to fill out a money order, see http://www.wikihow.com/Fill-Out-a-Money-Order.</li>
            <li>Caution: Not every place that sells money orders will take them back and refund your money. Since we return the money orders with any ticket requests that cannot be filled, ensure that you are satisfied with your refund options.</li>
            <li>It\'s a good idea for everyone to hang on to your money order receipt in case you need that to get a refund from the money order issuer. If the issuer of your money order will not refund your money, you probably can deposit the money order into your regular bank account -- but you will need to check with your financial institution.</li>
        </ul>
    <li>Decorate a mailing envelope.</li>
        <ul>
            <li>Be sure your return address, the mailing address and stamp are legible -- easy for the post office folks to read, and not hidden by your fabulous artwork.</li>
            <li>Also, leave clear space on the front for a visible postmark.</li>
        </ul>
    <li>Put both your Ticket Request form and your money order/cashier\'s check/teller\'s check in the stamped envelope and mail it to:
        <address>Austin Artistic Reconstruction, Ticket Request<br/>
        P.O. Box 9987<br/>
        Austin TX 78766</address></li>
    <li>Mail your filled envelope so that it is postmarked between TBD and TBD!</li>
        <ul>
            <li>The postmark must contain a date no earlier than TBD and no later than TBD or your request will be returned unticketed.</li>
            <li>The best way to ensure that there are no problems due to the postmark on your ticket request envelope is to take it into a post office during their business hours and ask the postal clerk to stamp it with a clearly dated postmark.</li>
        </ul>
</ol>',
    'Why can\'t I view the ticket registration form on secure.burningflipside.com? I\'m on the ticket page but do not see the link for registration.' => 'Make sure you are logged in and have created an account. For more information see profiles.burningflipside.com.',
    'Why haven\'t I received the email with my ticket registration confirmation and mail-in ticket request form? I checked my email address and it is entered correctly. I\'ve also checked my spam filtering.' => 'This issue is usually seen when you are using your own domain. It could be the way your MX records are set up to handle mail, or it could be some spam filtering in place that you don\'t see. If you have an email address with Gmail or Yahoo, try changing your email on record to that address instead. If you still have problems, email tickets@burningflipside.com; please include your ticket request ID in your email subject line.',
    'What can I do to increase the likelihood that my ticket request is received and processed?' => 'Things you can do:<ul>
     <li>Follow all of the registration and mailing instructions carefully.</li>
     <li>Make the addresses on the mailing envelope easy to read and leave open space for the postmark.</li>
     <li>Double-check that both your ticket request form and your properly completed money order or cashier\'s/teller\'s check payment are in your mailing envelope before you seal it.</li>
     <li>Ensure that your mailing envelope will be postmarked within the required date range. Waiting until the last day and/or dropping it in a box rather than taking it to the post office may increase your risk of problems. The best way to ensure that there are no problems due to the postmark on your ticket request envelope is to take it into a post office during their business hours and ask the postal clerk to stamp it with a clearly dated postmark.</li>
</ul>
We will return your ticket request and payment, once we are processing ticket requests in February, for any of the following reasons:<ul>
    <li>Illegible Ticket Request forms or money orders/cashier\'s checks/teller\'s checks</li>
    <li>Postmarks before TBD or after TBD</li>
    <li>Payment is not a money order, cashier\'s check or teller\'s check. We do not accept cash or personal checks.</li>
    <li>Money order or cashier\'s/teller\'s check is not made payable to Austin Artistic Reconstruction.</li>
    <li>Money order or cashier\'s/teller\'s check is not for exactly the full Amount Due.<br/>If you have chosen to donate to TinderBox this year, your amount will include the amount you have chosen to donate.</li>
    <li>Money order is a type that requires the purchaser\'s signature but is unsigned.</li>
</ul>',
    'How many tickets can I buy?' => 'The maximum number of tickets per request is 2 Adult tickets plus a total of 2 teen, child and/or kid tickets. Each ticket purchased, whether for adult, teen, kid, or child, counts as part of the total number of tickets available for Flipside.',
    'Do children or teenagers need tickets?' => 'Everyone entering Flipside must have a ticket, purchased in advance. People aged 18 and over need an Adult ticket. Teens aged 14-17 need a Teen ticket, kids ages 7-13 need a Kid ticket, and for little ones up to age 6 we have a Child ticket option. Kid tickets cost less than adult tickets and there is no charge for Child tickets.',
    'What do I do if I don\'t know yet who will be using my second adult ticket?' => 'No problem, and no reason for trying to fake it with a made-up name. Just enter your own name in registration for that ticket, adding the number "2" after your name. There will be opportunity to update the ticket holder name later, before the event.',
    'Can I request tickets for other people?' => 'Each ticket registration is limited to a maximum of two adult tickets and up to two (combined total) kid and/or child tickets. Each person requesting one or more tickets creates their own unique account at burningflipside.com and their online ticket registration.',
    'Do I need to decorate my ticket request envelope?' => 'We love decorated envelopes! Decorating your envelope brings joy to the world as it passes through many hands, including the volunteers who serve on the ticket team. Decorating your envelope is an early opportunity for participation in the art-focused event that is Flipside. And decorated envelopes may be published in the Flame, posted on the website, or used for another Flipside art project. Decorating your envelope does not, however, by itself affect your odds of getting a ticket. This WILL BE CHANGING FOR NEXT YEAR (2015).<br/>
Caution: When decorating your envelope please make it easy for the Post Office to deliver it by keeping the mailing and return addresses easy to read, and leave plenty of white/light space for your postmark to make it clear that you mailed your request on a valid date.',
    'Do I need to include a self-addressed, stamped envelope (SASE) with my ticket request for mailing of my ticket?' => 'SASE is not required and will not be used. The size and weight of the Burning Flipside Survival Guide requires us to use specific envelopes and postage plus we use a standardized mailing label, so we provide the ticket mailing envelopes.',
    'What happens if I mail my ticket request early or late?' => 'Ticket requests postmarked outside of the announced ticket request mail-in dates, whether early or late, are not considered in initial ticket-request processing. If the number of valid ticket requests is lower than the number of available tickets it is possible that out-of-date-range requests could be processed, but that\'s a real long shot. Please don\'t create extra work and return mail expense by knowingly mailing in a late request. If you missed the mail-in deadline, wait for the (unofficial) ticket exchange.',
    'Can I get tickets by volunteering for Flipside?' => 'Burning Flipside is a volunteer-run event. Everyone pays for their ticket except the winners of the annual ticket design and sticker design contest. Each of those winners gets two free adult tickets.<br/>Volunteering is a great way to get to know others in the Flipside community and may make it easier to locate an available ticket after the official ticket sale is over.',
    'What forms of payment do you accept? Can I pay with a credit card, debit card, personal check, Paypal or cash?' => 'Payment must be made with a money order, cashier\'s check or teller\'s check. Payment by credit or debit card, personal check, Paypal, cash, Monopoly(TM) money, silver bars or any other form is NOT accepted.<br/>Ticket purchase payment must be made with one of these forms of guaranteed funds (money order, cashier\'s check or teller\'s check) because it\'s not feasible for us to deal with bounced checks, and accepting cash or electronic payments involves more challenges than we are currently prepared to accept.',
    'What do I need to know about filling out the money order?' => '<ul>
        <li>Money orders must be made out to Austin Artistic Reconstruction and must be for the exact Amount Due shown on your Ticket Request form. We cannot accept overpayment as a donation to TinderBox unless you have specified it on your form.</li>
        <li>Caution: Not every place that sells money orders will take them back and refund your money. Since we return the money orders with any ticket requests that cannot be filled, ensure that you are satisfied with your refund options.</li>
        <li>Most but not all money orders require that the purchaser (you) sign the money order.</li>
        <li>Please write your ticket request ID number, which is shown on your Ticket Request form, at the top of your payment (or wherever there is good space for it).</li>
        <li>It\'s a good idea for everyone to hang on to your payment receipt in case you need that to get a refund from the issuer. If the issuer of your money order will not refund your money, you may be able to deposit the money order into your regular bank account--but you will need to check with your financial institution.</li>
        <li>To learn more about filling out a money order, see http://www.wikihow.com/Fill-Out-a-Money-Order.</li></ul>',
    'Why do you want my email, phone and mailing address for Will Call tickets?' => 'We need valid contact info on every ticket request registration to allow for communication of important ticket- or event-related information, to ensure that we have the correct mailing address for request returns so we can get payments back as quickly as possible, and to help solve mysteries related to ticket ownership or transfers.',
    'How do I cancel a ticket request after the ticket registration window closes?' => 'Contact tickets@burningflipside.com; please include your ticket request ID in your email subject line. The ticket team will cancel your request. Please note that requests cannot be canceled after we receive payment. Should you receive a ticket and find that you are unable to attend the event, you may sell or gift the ticket.',
    'What happens if the number of tickets requested exceeds the number available?' => 'If we are unable to provide tickets for all ticket requests because ticket demand exceeded supply, then we will implement a "random de-selection" process to identify which ticket requests must be returned unfilled.',
    'When and how will I learn whether my ticket request is filled?' => 'Once ticket request processing is complete, which usually happens in mid- to late-February, we will notify you by email whether or not we were able to provide tickets for your request. *

We also will update your ticket status in the online ticket system:

• "Pending" status means that no mailed ticket request was received for the ticket registration. (Before ticket request processing is complete, all submitted ticket registrations show Pending status.)

• "Returned" status means that we received your request but it will not be ticketed and we are returning the request and payment to you.

• "Received" status means that we received your request, and that it was not returned as an invalid request for postmark, insufficient or incorrect payment, personal check, or other reason listed above.

• "Ticketed" status means that a ticket number has been assigned; this will replace "Received" status as we move through the ticket distribution process.

• "Lottery" status means that your request was pulled in this year\'s random deselection soft lottery; this will replace "Received" status as we move through the ticket distribution process. If our event receives the Mass Gathering Act permit and we are able to increase our event attendee numbers for 2014, this status will change to "Ticketed". Please check the email you used during registration for an email sent out on March 18, 2014 for your options regarding your registration, or visit http://www.BurningFlipside.com/Announce for more details.

• "Canceled" status means that you notified the ticket team that you were canceling your registration and not mailing in a request for tickets.

* Keep in mind that due to the quirks of email delivery, some people may receive their ticket request status email before others. No need to panic if it takes a day or three for all messages to be received. Be sure to check your spam filtering.'
);

$faq_string = '';
$faq_count = 0;
foreach($faq as $q => $a)
{
    $faq_string .= '<div class="panel panel-default"><div class="panel-heading"><h4 class="panel-title"><a data-toggle="collapse" data-parent="#accordion" href="#faq_'.$faq_count.'">Q: '.$q.'</a></h4></div>';
    $faq_string .= '<div id="faq_'.$faq_count.'" class="panel-collapse collapse"><div class="panel-body">'.$a.'</div></div></div>';
    $faq_count++;
}

if(!FlipSession::is_logged_in())
{
    $page->body .= '
<div id="content">
    <h1>You must log in to access the Burning Flipside Ticket system!</h1>
</div>';
}
else
{
    $page->body .= '
<div id="content">
    <fieldset id="request_set" style="display: none;">
        <legend>Ticket Request</legend>
        <table id="requestList" class="table table-striped">
            <thead>
                <tr>
                    <th>Request ID</th>
                    <th>Request Year</th>
                    <th>Number of Tickets</th>
                    <th>Amount Due</th>
                    <th></th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </fieldset>
    <fieldset id="ticket_set" style="display: none;">
        <legend>Tickets</legend>
        <table id="ticketList">
            <thead>
                <tr>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Type</th>
                    <th>Short Ticket Code</th>
                    <th></th>
                </tr>
             </thead>
        </table>
    </fieldset>
    <fieldset>
        <legend>FAQ</legend>
        <div class="panel-group" id="faq">
            '.$faq_string.'
        </div>
    </fieldset>
    <div class="modal fade in" aria-hidden="false" id="ticket_id_modal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                        <h4 class="modal-title" id="modal_title">Full Ticket ID</h4>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info" role="alert"><strong>Notice: </strong>Providing this ID to another person is as good as handing them your ticket. The Flipside Ticket Team will never ask for this information. Only provide this code to someone whom you are selling or giving the ticket to!</div>
                        Long Ticket ID: <div class="well" id="long_id"></div><br/>
                        Long Ticket ID (Word Method): <div class="well" id="long_id_words"></div><br/>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Close</button></div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade in" aria-hidden="false" id="ticket_view_modal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                        <h4 class="modal-title" id="modal_title">Ticket Information</h4>
                    </div>
                    <div class="modal-body">
                        First Name: <span id="view_first_name"></span><br/>
                        Last Name: <span id="view_last_name"></span><br/>
                        Type: <span id="view_type"></span><br/>
                        Short Code: <a href="#" id="view_short_code"></a><br/>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Close</button></div>
                </div>
            </div>
        </div>
    </div>
</div>
';

}
$page->print_page();
// vim: set tabstop=4 shiftwidth=4 expandtab:
?>

