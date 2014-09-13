<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketPage.php');
$page = new TicketPage('Burning Flipside - Tickets');

$script_start_tag = $page->create_open_tag('script', array('src'=>'/js/jquery.dataTables.js'));
$script_close_tag = $page->create_close_tag('script');
$page->add_head_tag($script_start_tag.$script_close_tag);

$script_start_tag = $page->create_open_tag('script', array('src'=>'js/request.js'));
$page->add_head_tag($script_start_tag.$script_close_tag);

$css_tag = $page->create_open_tag('link', array('rel'=>'stylesheet', 'href'=>'/css/jquery.dataTables.css', 'type'=>'text/css'), true);
$page->add_head_tag($css_tag);

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
    <form id="request" role="form">
        <fieldset id="request_set">
            <legend>Ticket Request</legend>
            <div class="form-group">
                <label for="givenName" class="col-sm-2 control-label">First Name:</label>
                <div class="col-sm-10">
                    <input id="givenName" name="givenName" type="text" required/>
                </div>
            </div>
            <div class="form-group">
                <label for="sn" class="col-sm-2 control-label">Last Name:</label>
                <div class="col-sm-10">
                    <input id="sn" name="sn" type="text" required/>
                </div>
            </div>
            <div class="form-group">
                <label for="mail" class="col-sm-2 control-label">Email:</label>
                <div class="col-sm-10">
                    <input type="text" name="mail" id="mail" readonly/>
                    <img src="/images/info.svg" style="height: 1em; width: 1em;" title="This email address has been set and confirmed by your profile. If you need to use a different email address please edit your profile."/>
                </div>
            </div>
            <div class="form-group">
                <label for="mobile" class="col-sm-2 control-label">Cell Number:</label>
                <div class="col-sm-10">
                    <input id="mobile" name="mobile" type="text"/>
                </div>
            </div>
            <div class="form-group">
                <label for="c" class="col-sm-2 control-label">Country:</label>
                <div class="col-sm-10">
                    <select id="c" name="c"></select>
                </div>
            </div>
            <div class="clearfix visible-md visible-lg"></div>
            <div class="form-group">
                <label for="street" class="col-sm-2 control-label">Street Address:</label>
                <div class="col-sm-10">
                    <input id="street" name="street" type="text"/>
                </div>
            </div>
            <div class="form-group">
                <label for="zip" class="col-sm-2 control-label">Postal/Zip Code:</label>
                <div class="col-sm-10">
                    <input id="zip" name="zip" type="text"/>
                </div>
            </div>
            <div class="form-group">
                <label for="l" class="col-sm-2 control-label">City:</label>
                <div class="col-sm-10">
                    <input id="l" name="l" type="text"/>
                </div>
            </div>
            <div class="form-group">
                <label for="st" class="col-sm-2 control-label">State:</label>
                <div class="col-sm-10">
                    <select id="st" name="st" type="text"></select>
                </div>
            </div>
            <div class="clearfix visible-md visible-lg"></div>
            <table id="ticket_table" class="table">
                <thead>
                    <tr>
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
                        <td colspan="2"><button type="button" class="btn btn-primary" id="add_new_ticket">Add New Tickets</button></td>
                        <th>Ticket Subtotal:</th>
                        <th id="ticket_subtotal"></th>
                    </tr>
                </tfoot>
            </table>
        <fieldset id="donations">
            <legend>Donation</legend>
        </fieldset>
        <fieldset>
            <legend>Mailing Lists</legend>
            It is highly recommended that all Burning Flipside participants sign up for one or more email lists to allow
            AAR, LLC to communicate important details about Burning Flipside before and after the event.<br/>
            <br/>
            Sign me up for the following lists:<br/>
            <table id="email_lists">
            </table>
        </fieldset>
        <button type="submit" name="submit" class="btn btn-primary">Submit Request</button>
    </fieldset>
    </form>
</div>
';

}
$page->print_page();
// vim: set tabstop=4 shiftwidth=4 expandtab:
?>

