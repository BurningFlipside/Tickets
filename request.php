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
                <label for="first" class="col-sm-2 control-label">First Name:</label>
                <div class="col-sm-10">
                    <input type="text" name="first" id="first" required/>
                </div>
            </div>
            <div class="form-group">
                <label for="last" class="col-sm-2 control-label">Last Name:</label>
                <div class="col-sm-10">
                    <input type="text" name="last" id="last" required/>
                </div>
            </div>
            <div class="form-group">
                <label for="last" class="col-sm-2 control-label">Email:</label>
                <div class="col-sm-10">
                    <input type="text" name="email" id="email" readonly/>
                    <img src="/images/info.svg" style="height: 1em; width: 1em;" title="This email address has been set and confirmed by your profile. If you need to use a different email address please edit your profile."/>
                </div>
            </div>
            <div class="form-group">
                <label for="last" class="col-sm-2 control-label">Street Address:</label>
                <div class="col-sm-10">
                    <input type="text" name="address" id="address" required/>
                </div>
            </div>
            <div class="form-group">
                <label for="last" class="col-sm-2 control-label">City:</label>
                <div class="col-sm-10">
                    <input type="text" name="city" id="city" required/>
                </div>
            </div>
            <div class="form-group">
                <label for="last" class="col-sm-2 control-label">State:</label>
                <div class="col-sm-10">
                    <select id="state" name="state">
                    <option value=""></option>
                    <option value="AL">Alabama</option>
                    <option value="AK">Alaska</option>
                    <option value="AS">American Samoa</option>
                    <option value="AZ">Arizona</option>
                    <option value="AR">Arkansas</option>
                    <option value="AA">Armed Forces Americas</option>
                    <option value="AP">Armed Forces Pacific</option>
                    <option value="AE">Armed Forces Others</option>
                    <option value="CA">California</option>
                    <option value="CO">Colorado</option>
                    <option value="CT">Connecticut</option>
                    <option value="DE">Delaware</option>
                    <option value="DC">District Of Columbia</option>
                    <option value="FL">Florida</option>
                    <option value="GU">Guam</option>
                    <option value="GA">Georgia</option>
                    <option value="HI">Hawaii</option>
                    <option value="ID">Idaho</option>
                    <option value="IL">Illinois</option>
                    <option value="IN">Indiana</option>
                    <option value="IA">Iowa</option>
                    <option value="KS">Kansas</option>
                    <option value="KY">Kentucky</option>
                    <option value="LA">Louisiana</option>
                    <option value="ME">Maine</option>
                    <option value="MD">Maryland</option>
                    <option value="MA">Massachusetts</option>
                    <option value="MI">Michigan</option>
                    <option value="MN">Minnesota</option>
                    <option value="MS">Mississippi</option>
                    <option value="MO">Missouri</option>
                    <option value="MT">Montana</option>
                    <option value="NE">Nebraska</option>
                    <option value="NV">Nevada</option>
                    <option value="NH">New Hampshire</option>
                    <option value="NJ">New Jersey</option>
                    <option value="NM">New Mexico</option>
                    <option value="NY">New York</option>
                    <option value="NC">North Carolina</option>
                    <option value="ND">North Dakota</option>
                    <option value="MP">Northern Mariana Islands</option>
                    <option value="OH">Ohio</option>
                    <option value="OK">Oklahoma</option>
                    <option value="OR">Oregon</option>
                    <option value="PA">Pennsylvania</option>
                    <option value="PR">Puerto Rico</option>
                    <option value="RI">Rhode Island</option>
                    <option value="SC">South Carolina</option>
                    <option value="SD">South Dakota</option>
                    <option value="TN">Tennessee</option>
                    <option value="TX">Texas</option>
                    <option value="UM">United States Minor Outlying Islands</option>
                    <option value="UT">Utah</option>
                    <option value="VT">Vermont</option>
                    <option value="VI">Virgin Islands</option>
                    <option value="VA">Virginia</option>
                    <option value="WA">Washington</option>
                    <option value="WV">West Virginia</option>
                    <option value="WI">Wisconsin</option>
                    <option value="WY">Wyoming</option>
                    </select>
                </div>
            </div>
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

