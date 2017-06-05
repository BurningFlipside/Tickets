<?php
require_once('Autoload.php');
require_once('app/TicketAutoload.php');

function ticket_api_group()
{
    global $app;
    $app->get('', 'list_tickets');
    $app->get('/types', 'list_ticket_types');
    $app->get('/discretionary', 'list_discretionary_tickets');
    $app->get('/pos(/)', 'getSellableTickets');
    $app->get('/:hash', 'show_ticket');
    $app->get('/:hash/pdf', 'get_pdf');
    $app->patch('/:hash', 'update_ticket');
    $app->post('/:hash/Actions/Ticket.SendEmail', 'send_email');
    $app->post('/:hash/Actions/Ticket.Claim', 'claimTicket');
    $app->post('/:hash/Actions/Ticket.Transfer', 'transferTicket');
    $app->post('/:hash/Actions/Ticket.SpinHash', 'spinHash');
    $app->post('/:hash/Actions/Ticket.Sell', 'sellTicket');
    $app->post('/pos/sell', 'sell_multiple_tickets');
    $app->post('/Actions/VerifyShortCode/:code', 'verifyShortCode');
    $app->post('/Actions/GenerateTickets', 'generateTickets');
}

function list_tickets()
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $filter = false;
    if($app->user->isInGroupNamed('TicketAdmins') && $app->odata->filter !== false)
    {
        $filter = $app->odata->filter;
        if($filter->contains('year eq current'))
        {
            $settings = \Tickets\DB\TicketSystemSettings::getInstance();
            $clause = $filter->getClause('year');
            $clause->var2 = $settings['year'];
        }
    }
    else
    {
        $filter = new \Tickets\DB\TicketDefaultFilter($app->user->mail);
    }
    $ticket_data_table = \Tickets\DB\TicketsDataTable::getInstance();
    $tickets = $ticket_data_table->read($filter, $app->odata->select, $app->odata->top, $app->odata->skip, $app->odata->orderby);
    if($tickets === false)
    {
        $tickets = array();
    }
    else if(!is_array($tickets))
    {
        $tickets = array($tickets);
    }
    if($app->odata->count)
    {
        $tickets = array('@odata.count'=>count($tickets), 'value'=>$tickets);
    }
    echo json_encode($tickets);
}

function show_ticket($hash)
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $params = $app->request->params();
    if(isset($params['with_history']) && $params['with_history'] === '1')
    {
        $ticket = \Tickets\Ticket::get_ticket_history_by_hash($hash);
    }
    else
    {
        $ticket = \Tickets\Ticket::get_ticket_by_hash($hash, $app->odata->select);
    }
    if($ticket === false)
    {
        $app->notFound();
    }
    echo $ticket->serializeObject($app->fmt, $app->odata->select);
}

function get_pdf($hash)
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $ticket = \Tickets\Ticket::get_ticket_by_hash($hash);
    $pdf = new \Tickets\TicketPDF($ticket);
    $app->fmt = 'passthru';
    $app->response->headers->set('Content-Type', 'application/pdf');
    echo $pdf->toPDFBuffer();
}

function getSellableTickets()
{
    global $app;
    if(!$app->user || !$app->user->isInGroupNamed('TicketAdmins'))
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $tickets = \Tickets\Ticket::get_tickets_for_user_and_pool($app->user, $app->odata->filter);
    echo json_encode($tickets);
}

function update_ticket($id)
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $ticket_data_table = \Tickets\DB\TicketsDataTable::getInstance();
    $filter = new \Tickets\DB\TicketHashFilter($id);
    $array = $app->getJsonBody(true);
    $copy = $array;
    unset($copy['firstName']);
    unset($copy['lastName']);
    unset($copy['email']);
    if(count($copy) > 0 && !$app->user->isInGroupNamed("TicketAdmins"))
    {
        throw new Exception('Must be member of TicketAdmins group', ACCESS_DENIED);
    }
    $res = false;
    $hash = false;
    if(count($copy) > 0)
    {
        $hash = $id;
        //Make sure all tickets are getting marked used at gate
        if(isset($array['physical_ticket_id']) && strlen($array['physical_ticket_id']) > 0)
        {
            $array['used'] = 1;
        }
        $res = $ticket_data_table->update($filter, $array);
    }
    else
    {
        if(isset($array['email']))
        {
            $hash = $id;
        }
        else
        {
            $hash = $id;
        }
        $res = $ticket_data_table->update($filter, $array);
    }
    if($res === false)
    {
        throw new Exception('Unable to update DB', INTERNAL_ERROR);
    }
    $url = $app->request->getRootUri().$app->request->getResourceUri();
    $url = substr($url, 0, strrpos($url, '/')+1);
    echo json_encode(array('hash'=>$hash, 'href'=>$url.$hash));
}

function list_discretionary_tickets()
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    if(!$app->user->isInGroupNamed('AAR') && !$app->user->isInGroupNamed('AFs'))
    {
        throw new Exception('Must be member of AAR group', ACCESS_DENIED);
    }
    $ticket_data_table = \Tickets\DB\TicketsDataTable::getInstance();
    $filter = new \Tickets\DB\TicketDefaultFilter($app->user->mail, true);
    $tickets = $ticket_data_table->read($filter, $app->odata->select, $app->odata->top, $app->odata->skip, $app->odata->orderby);
    if($tickets === false)
    {
        $tickets = array();
    }
    else if(!is_array($tickets))
    {
        $tickets = array($tickets);
    }
    echo json_encode($tickets);
}

function list_ticket_types()
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $ticket_data_set = DataSetFactory::getDataSetByName('tickets');
    $ticket_type_data_table = $ticket_data_set['TicketTypes'];
    $ticket_types = $ticket_type_data_table->read($app->odata->filter, $app->odata->select, $app->odata->top, $app->odata->skip, $app->odata->orderby);
    if($ticket_types === false)
    {
        $ticket_types = array();
    }
    else if(!is_array($ticket_types))
    {
        $ticket_types = array($ticket_types);
    }
    echo json_encode($ticket_types);
}

function send_email($hash)
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $ticket = \Tickets\Ticket::get_ticket_by_hash($hash);
    $email_msg = new \Tickets\TicketEmail($ticket);
    $email_provider = EmailProvider::getInstance();
    if($email_provider->sendEmail($email_msg) === false)
    {
        throw new \Exception('Unable to send ticket email!');
    }
    echo 'true';
}

function claimTicket($hash)
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $ticket = \Tickets\Ticket::get_ticket_by_hash($hash);
    if($ticket === false)
    {
        $app->notFound();
        return;
    }
    $ticket->email = $app->user->mail;
    $array = $app->get_json_body(true);
    if(isset($array['firstName']))
    {
        $ticket->firstName = $array['firstName'];
    }
    if(isset($array['lastName']))
    {
        $ticket->lastName = $array['lastName'];
    }
    $ticket->transferInProgress = 0;
    return $ticket->insert_to_db(); 
}

function endswith($string, $test)
{
    $strlen = strlen($string);
    $testlen = strlen($test);
    if ($testlen > $strlen) return false;
    return substr_compare($string, $test, $strlen - $testlen, $testlen) === 0;
}

function transferTicket($hash)
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $array = $app->get_json_body(true);
    if(!isset($array['email']))
    {
        throw new \Exception('Missing Required Parameter email!');
    }
    $ticket = \Tickets\Ticket::get_ticket_by_hash($hash);
    if($ticket === false || $ticket->void == 1)
    {
        $app->notFound();
    }
    if(filter_var($array['email'], FILTER_VALIDATE_EMAIL) === false)
    {
        throw new \Exception('Invalid value for required parameter email!');
    }
    if(endswith($array['email'], 'gmail') || endswith($array['email'], 'yahoo') || endswith($array['email'], 'hotmail') || endswith($array['email'], 'outlook'))
    {
        $array['email'] = $array['email'].'.com';
    }
    $email_msg = new \Tickets\TicketTransferEmail($ticket, $array['email']);
    $email_provider = EmailProvider::getInstance();
    if($email_provider->sendEmail($email_msg) === false)
    {
        throw new \Exception('Unable to send ticket email!');
    }
    $ticket_data_table = \Tickets\DB\TicketsDataTable::getInstance();
    $filter = new \Tickets\DB\TicketHashFilter($hash);
    $res = $ticket_data_table->update($filter, array('transferInProgress'=>1));
    echo json_encode($res);
}

function spinHash($hash)
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    if(!$app->user->isInGroupNamed('TicketAdmins'))
    {
        throw new Exception('Must be member of TicketAdmins group', ACCESS_DENIED);
    }
    $ticket = \Tickets\Ticket::get_ticket_by_hash($hash);
    if($ticket === false)
    {
        $app->notFound();
        return;
    }
    echo json_encode($ticket->insert_to_db());
}

function sellTicket($hash)
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    if(!$app->user->isInGroupNamed('TicketAdmins'))
    {
        throw new Exception('Must be member of TicketAdmins group', ACCESS_DENIED);
    }
    $ticket = \Tickets\Ticket::get_ticket_by_hash($hash);
    if($ticket === false)
    {
        $app->notFound();
        return;
    }
    $obj = $app->getJsonBody();
    if($obj === null || $obj === false)
    {
        throw new \Exception('Unable to parse payload!');
    }
    $ticket->sold = 1;
    $ticket->email = $obj->email;
    if(isset($obj->firstName))
    {
        $ticket->firstName = $obj->firstName;
    }
    if(isset($obj->lastName))
    {
        $ticket->lastName = $obj->lastName;
    }
    $res = $ticket->insert_to_db();
    if($res === true)
    {
        $email_msg = new \Tickets\TicketEmail($ticket);
        $email_provider = EmailProvider::getInstance();
        if($email_provider->sendEmail($email_msg) === false)
        {
            throw new \Exception('Unable to send ticket email!');
        }
    }
    echo json_encode($res);
}

function sell_multiple_tickets()
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $obj = $app->get_json_body(true);
    foreach($obj['tickets'] as $type=>$qty)
    {
        if($qty > 0)
        {
        }
        else
        {
            unset($obj['tickets'][$type]);
        }
    }
    $message = false;
    if(isset($obj['message']))
    {
        $message = $obj['message'];
    }
    $firstName = false;
    if(isset($obj['firstName']))
    {
        $firstName = $obj['firstName'];
    }
    $lastName = false;
    if(isset($obj['lastName']))
    {
        $lastName = $obj['lastName'];
    }
    $pool = false;
    if(isset($obj['pool']))
    {
        $pool = $obj['pool'];
    }
    $res = \Tickets\Ticket::do_sale($app->user, $obj['email'], $obj['tickets'], $message, $firstName, $lastName, $pool);
    echo json_encode($res); 
}

function verifyShortCode($code)
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $count = FlipSession::getVar('TicketVerifyCount', 0);
    if($count > 20)
    {
        throw new \Exception('Exceeded Ticket Verify Count for this session!');
    }
    $count++;
    FlipSession::setVar('TicketVerifyCount', $count);
    $filter = new \Data\Filter('contains(hash,'.$code.') and void eq 0');
    $ticket_data_table = \Tickets\DB\TicketsDataTable::getInstance();
    $res = $ticket_data_table->read($filter);
    if($res === false) echo 'false';
    else echo 'true';
}

function generateTickets()
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    else if(!$app->user->isInGroupNamed('TicketAdmins'))
    {
        throw new Exception('Must be member of TicketAdmins group', ACCESS_DENIED);
    }
    $obj = $app->getJsonBody(true);
    $autoPopulate = false;
    if(isset($obj['auto_populate']) && $obj['auto_populate'] === 'on')
    {
        $autoPopulate = true;
    }
    $types = $obj['types'];
    $settings = \Tickets\DB\TicketSystemSettings::getInstance();
    $year = $settings['year'];
    $ticketDataTable = \Tickets\DB\TicketsDataTable::getInstance();
    foreach($types as $type=>$count)
    {
        for($i = 0; $i < $count; $i++)
        {
            $ticket = new \Tickets\Ticket();
            $ticket->year = $year;
            $ticket->type = $type;
            $ticket->insert_to_db($ticketDataTable);
        }
    }
    if($autoPopulate)
    {
        $dataSet = DataSetFactory::getDataSetByName('tickets');
        $requestDataTable = $dataSet['TicketRequest'];
        $requestedTicketsDataTable = $dataSet['RequestedTickets'];
        $unTicketedRequests = $requestDataTable->read(new \Data\Filter("year eq $year and private_status eq 1"));
        foreach($unTicketedRequests as $request)
        {
            $request_id = $request['request_id'];
            $filter = new \Data\Filter("year eq $year and request_id eq '$request_id'");
            $requestedTickets = $requestedTicketsDataTable->read($filter);
            foreach($requestedTickets as $requestedTicket)
            {
                $unAssignedTickets = $ticketDataTable->read(new \Data\Filter("sold eq 0 and year eq $year and type eq '{$requestedTicket['type']}'"), false, 1);
                if(!isset($unAssignedTickets[0]))
                {
                    throw new \Exception('Insufficient tickets of type '.$requestedTicket['type'].' to process all requests!');
                }
                $unAssignedTickets[0]['firstName'] = $requestedTicket['first'];
                $unAssignedTickets[0]['lastName'] = $requestedTicket['last'];
                $unAssignedTickets[0]['email'] = $request['mail'];
                $unAssignedTickets[0]['request_id'] = $request['request_id'];
                $unAssignedTickets[0]['sold'] = 1;
                if($requestedTicket['type'] !== 'A')
                {
                    $unAssignedTickets[0]['guardian_first'] = $request['givenName'];
                    $unAssignedTickets[0]['guardian_last'] = $request['sn'];
                }
                $filter = new \Data\Filter("hash eq '{$unAssignedTickets[0]['hash']}'");
                unset($unAssignedTickets[0]['hash_words']);
                $ticketDataTable->update($filter, $unAssignedTickets[0]);
                $requestedTicket['assigned_id'] = $unAssignedTickets[0]['hash'];
                $filter = new \Data\Filter("requested_ticket_id eq {$requestedTicket['requested_ticket_id']}");
                $requestedTicketsDataTable->update($filter, $requestedTicket);
            }
            $request['private_status'] = 6;
            $filter = new \Data\Filter("year eq $year and request_id eq '$request_id'");
            $requestDataTable->update($filter, $request);
        }
        echo 'true';
    }
    else
    {
        echo 'true';
    }
}

