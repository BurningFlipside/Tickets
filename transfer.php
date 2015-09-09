<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketPage.php');
require_once('class.Ticket.php');
$page = new TicketPage('Burning Flipside - Tickets');

$page->add_js(JS_DATATABLE);
$page->add_css(CSS_DATATABLE);
$page->add_js_from_src('js/transfer.js');

function ticket_id_entry_form()
{
    return '<div id="content">
                <form method="GET" action="transfer.php">
                    <div class="form-group">
                        <label for="id" class="col-sm-2 control-label">Ticket ID:</label>
                        <div class="col-sm-10">
                            <input class="form-control" type="text" name="id" id="id" data-toggle="tooltip" data-placement="top" title="The ticket ID can take two forms. One form is a 32-character string of letters and numbers. The other form is an 8 character string followed by approximately 8 words. You may enter either value here."/>
                        </div>
                    </div>
                    <button type="submit" name="submit" class="btn btn-primary">Lookup Ticket</button>
                </form>
            </div>';
}

if(!FlipSession::isLoggedIn())
{
    $page->body .= '
<div id="content">
    <h1>Login</h1>
    <form id="login_form" role="form" action="https://profiles.burningflipside.com/ajax/login.php" method="POST">
        <input class="form-control" type="text" name="username" placeholder="Username or Email" required autofocus/>
        <input class="form-control" type="password" name="password" placeholder="Password" required/>
        <input type="hidden" name="return" value="'.$page->current_url().'"/>
        <input type="hidden" name="redirect" value="1"/>
        <button class="btn btn-lg btn-primary btn-block" type="submit">Login</button>
    </form>
</div>';
}
else
{
    if(isset($_GET['id']))
    {
        $hash = $_GET['id'];
        if(strpos($hash, ' ') !== FALSE)
        {
            $hash = Ticket::words_to_hash($hash); 
        }
        $ticket = Ticket::get_ticket_by_hash($hash);
        if($ticket == FALSE)
        {
            $page->add_notification('The specified ticket does not exist!. Please enter the ID again. If this error persists then please contact the <a href="mailto:tickets@burningflipside.com" class="alert-link">Flipside Ticket Team</a>.', 
                                    TicketPage::NOTIFICATION_FAILED);
            $page->body .= ticket_id_entry_form();
        }
        else
        {
            if(Ticket::user_has_ticket($hash, FlipSession::getUser()))
            {
                /*This user already owns the ticket. Let them send it to someone else or just change the name*/
                $page->body .= '<div id="content">
                                    <input class="form-control" type="hidden" name="hash" id="hash" value="'.$hash.'"/>
                                    <formset>
                                        <legend>Change Name</legend>
                                        <div class="form-group">
                                            <label for="firstName" class="col-sm-2 control-label">First Name:</label>
                                            <div class="col-sm-10">
                                                <input class="form-control" type="text" name="firstName" id="firstName" data-toggle="tooltip" data-placement="top" title="The first name that matches the legal photo ID that will be presented at the Burning Flipside gate." value="'.$ticket[0]->firstName.'"/>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="lastName" class="col-sm-2 control-label">Last Name:</label>
                                            <div class="col-sm-10">
                                                <input class="form-control" type="text" name="lastName" id="lastName" data-toggle="tooltip" data-placement="top" title="The last name that matches the legal photo ID that will be presented at the Burning Flipside gate." value="'.$ticket[0]->lastName.'"/>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-primary" onclick="change_name()">Change Name</button>
                                    </formset>
                                    <formset>
                                        <legend>Change Ownership</legend>
                                        <div class="form-group">
                                            <label for="email" class="col-sm-2 control-label">Email:</label>
                                            <div class="col-sm-10">
                                                <input class="form-control" type="text" name="email" id="email" data-toggle="tooltip" data-placement="top" title="The email to send the ticket to." value="'.$ticket[0]->email.'"/>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-primary" onclick="transfer()">Transfer Tickets</button>
                                    </formset>
                                </div>';

            }
            else
            {
                /*This user does not own the ticket yet, but has the full id. Let them attempt to claim it*/
                $page->body .= '<div id="content">
                                    <input class="form-control" type="hidden" name="hash" id="hash" value="'.$hash.'"/>
                                    <formset>
                                        <legend>Change Name</legend>
                                        <div class="form-group">
                                            <label for="firstName" class="col-sm-2 control-label">First Name:</label>
                                            <div class="col-sm-10">
                                                <input class="form-control" type="text" name="firstName" id="firstName" data-toggle="tooltip" data-placement="top" title="The first name that matches the legal photo ID that will be presented at the Burning Flipside gate." value="'.$ticket[0]->firstName.'"/>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="lastName" class="col-sm-2 control-label">Last Name:</label>
                                            <div class="col-sm-10">
                                                <input class="form-control" type="text" name="lastName" id="lastName" data-toggle="tooltip" data-placement="top" title="The last name that matches the legal photo ID that will be presented at the Burning Flipside gate." value="'.$ticket[0]->lastName.'"/>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-primary" onclick="claim_ticket()">Claim Ticket</button>
                                    </formset>
                                </div>';
            }
        }
    }
    else
    {
        /*No id, but logged on. Let them enter an ID and repost*/
        $page->body .= ticket_id_entry_form();
    }
}
$page->print_page();
// vim: set tabstop=4 shiftwidth=4 expandtab:
?>

