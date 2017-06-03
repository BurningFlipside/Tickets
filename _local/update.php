<?php
set_include_path(get_include_path() . PATH_SEPARATOR . '/var/www/common' . PATH_SEPARATOR . '/var/www/secure/tickets');
require_once('/var/www/common/Autoload.php');
require_once('../app/TicketAutoload.php');

$filter = new \Data\Filter("tickets eq CAST('null' AS JSON)");

$dataSet = \DataSetFactory::getDataSetByName('tickets');
$requestDataTable = $dataSet['TicketRequest'];
$requestedTicketDataTable = $dataSet['RequestedTickets'];
$donationDataTable = $dataSet['RequestDonation'];
$requests = $requestDataTable->read($filter);
$count = count($requests);
for($i = 0; $i < $count; $i++)
{
    $request = $requests[$i];
    $year = $request['year'];
    $requestId = $request['request_id'];

    $childFilter = new \Data\Filter("request_id eq '$requestId' and year eq $year");
    $tickets = $requestedTicketDataTable->read($childFilter);
    $donations = $donationDataTable->read($childFilter);
    $ticketTotal = 0;
    $donationTotal = 0;
    if($tickets === false)
    {
        $tickets = null;
    }
    else
    {
        $ticketCount = count($tickets);
        for($j = 0; $j < $ticketCount; $j++)
        {
            unset($tickets[$j]['requested_ticket_id']);
            unset($tickets[$j]['request_id']);
            unset($tickets[$j]['year']);
            unset($tickets[$j]['assigned_id']);
            unset($tickets[$j]['test']);
            switch($tickets[$j]['type'])
            {
                case 'A':
                case 'T':
                    $ticketTotal += 111;
                    break;
                case 'K':
                    $ticketTotal += 33;
                    break;
            }
        }
    }
    if($donations === false)
    {
        $donations = null;
    }
    else
    {
        $donationCount = count($donations);
        for($j = 0; $j < $donationCount; $j++)
        {
            unset($donations[$j]['donation_id']);
            unset($donations[$j]['request_id']);
            unset($donations[$j]['year']);
            unset($donations[$j]['test']);
            $donationTotal += $donations[$j]['amount'];
        }
    }
    $request['tickets'] = json_encode($tickets);
    $request['donations'] = json_encode($donations);
    $request['ticketAmount'] = $ticketTotal;
    $request['donationAmount'] = $donationTotal;
    unset($request['revisions']);

    echo "Request ID = $requestId\n";
    $res = $requestDataTable->update($childFilter, $request);
    if($res === false)
    {
        print_r($requestDataTable->getLastError());
    }
    echo "Result is $res\n";
}

?>
