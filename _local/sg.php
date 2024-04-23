<?php
set_include_path(get_include_path() . PATH_SEPARATOR . '/var/www/common' . PATH_SEPARATOR . '/var/www/secure/tickets');
require_once('/var/www/common/Autoload.php');
require_once('../app/TicketAutoload.php');

$settings = \Tickets\DB\TicketSystemSettings::getInstance();
$year = $settings['year'];
$ticketDataTable = \Tickets\DB\TicketsDataTable::getInstance();
$emailsToSend = $ticketDataTable->raw_query("SELECT DISTINCT email FROM tblTickets WHERE year=$year and assigned=1 and sold=1;");


$count = count($emailsToSend);
for($i = 0; $i < $count; $i++)
{
	/*
    $ticket = new \Tickets\Ticket($unProcessedTickets[$i]);
    $hash = $unProcessedTickets[$i]['hash'];
    $email_msg = new \Tickets\TicketEmail($ticket);
    $email_provider = \Flipside\EmailProvider::getInstance();
    if($email_provider->sendEmail($email_msg) !== false)
    {
        $filter = new \Flipside\Data\Filter("hash eq '$hash'");
        $res = $ticketDataTable->update($filter, array('assigned'=>1));
        if($res === false)
        {
            echo "Failed to update ticket $hash\n";
        }
    }
    else
    {
        echo "Failed to send email for ticket $hash\n";
    }*/
}


?>
