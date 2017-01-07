<?php
set_include_path(get_include_path() . PATH_SEPARATOR . '/var/www/common' . PATH_SEPARATOR . '/var/www/secure/tickets');
require_once('/var/www/common/Autoload.php');
require_once('../app/TicketAutoload.php');

$settings = \Tickets\DB\TicketSystemSettings::getInstance();
$year = $settings['year'];
$filter = new \Data\Filter("year eq $year and assigned eq 0");

$ticketDataTable = \Tickets\DB\TicketsDataTable::getInstance();
$unProcessedTickets = $ticketDataTable->read($filter, false, 10);
$count = count($unProcessedTickets);
for($i = 0; $i < $count; $i++)
{
    $ticket = new \Tickets\Ticket($unProcessedTickets[$i]);
    $hash = $unProcessedTickets[$i]['hash'];
    $email_msg = new \Tickets\TicketEmail($ticket);
    $email_provider = EmailProvider::getInstance();
    if($email_provider->sendEmail($email_msg) !== false)
    {
        $filter = new \Data\Filter("hash eq '$hash'");
        $res = $ticketDataTable->update($filter, array('assigned'=>1));
        if($res === false)
        {
            echo "Failed to update ticket $hash\n";
        }
    }
    else
    {
        echo "Failed to send email for ticket $hash\n";
    }
}

?>
