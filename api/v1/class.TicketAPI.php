<?php
class TicketAPI extends Http\Rest\RestAPI
{
    public function setup($app)
    {
        $app->get('[/]', array($this, 'listTickets'));
        $app->get('/types[/]', array($this, 'listTicketTypes'));
        $app->get('/discretionary[/]', array($this, 'listDiscretionaryTickets'));
        $app->post('/discretionary[/]', array($this, 'assignDiscretionaryTickets'));
        $app->get('/pos[/]', array($this, 'getSellableTickets'));
        $app->get('/{hash}[/]', array($this, 'showTicket'));
        $app->get('/{hash}/pdf[/]', array($this, 'getPdf'));
        $app->patch('/{hash}[/]', array($this, 'updateTicket'));
        $app->post('/{hash}/Actions/Ticket.SendEmail', array($this, 'sendEmail'));
        $app->post('/{hash}/Actions/Ticket.Claim', array($this, 'claimTicket'));
        $app->post('/{hash}/Actions/Ticket.Transfer', array($this, 'transferTicket'));
        $app->post('/{hash}/Actions/Ticket.SpinHash', array($this, 'spinHash'));
        $app->post('/{hash}/Actions/Ticket.Sell', array($this, 'sellTicket'));
        $app->post('/pos/sell', array($this, 'sellMultipleTickets'));
        $app->post('/Actions/VerifyShortCode/{code}', array($this, 'verifyShortCode'));
        $app->post('/Actions/GenerateTickets', array($this, 'generateTickets'));
    }

    public function listTickets($request, $response, $app)
    {
        $this->validateLoggedIn($request);
        $odata = $request->getAttribute('odata', new \ODataParams(array()));
        $filter = false;
        if($this->user->isInGroupNamed('TicketAdmins') && $odata->filter !== false)
        {
            $filter = $odata->filter;
            if($filter->contains('year eq current'))
            {
                $settings = \Tickets\DB\TicketSystemSettings::getInstance();
                $clause = $filter->getClause('year');
                $clause->var2 = $settings['year'];
            }
        }
        else
        {
            $filter = new \Tickets\DB\TicketDefaultFilter($this->user->mail);
        }
        $params = $request->getQueryParams();
        $search = null;
        if(isset($params['$search']))
        {
            $search = $params['$search'];
        }
        if($search !== null && ($this->user->isInGroupNamed('TicketAdmins') || $this->user->isInGroupNamed('TicketTeam')))
        {
            $filter->addToSQLString(" AND (email LIKE '%$search%' OR lastName LIKE '%$search%' OR firstName LIKE '%$search%')");
        }
        $ticket_data_table = \Tickets\DB\TicketsDataTable::getInstance();
        $tickets = $ticket_data_table->read($filter, $odata->select, $odata->top, $odata->skip, $odata->orderby);
        if($tickets === false)
        {
            $tickets = array();
        }
        else if(!is_array($tickets))
        {
            $tickets = array($tickets);
        }
        if($odata->count)
        {
            $tickets = array('@odata.count'=>count($tickets), 'value'=>$tickets);
        }
        return $response->withJson($tickets);
    }

    public function showTicket($request, $response, $app)
    {
        $this->validateLoggedIn($request);
        $hash = $app['hash'];
        $withHistory = $request->getQueryParam('with_history', false);
        $odata = $request->getAttribute('odata', new \ODataParams(array()));
        if($withHistory === true || $withHistory === '1')
        {
            $ticket = \Tickets\Ticket::get_ticket_history_by_hash($hash);
        }
        else
        {
            $ticket = \Tickets\Ticket::get_ticket_by_hash($hash, $odata->select);
        }
        if($ticket === false)
        {
            return $response->withStatus(404);
        }
        return $response->withJson($ticket);
    }

    public function getPdf($request, $response, $app)
    {
        $this->validateLoggedIn($request);
        $hash = $app['hash'];
        $ticket = \Tickets\Ticket::get_ticket_by_hash($hash);
        $pdf = new \Tickets\TicketPDF($ticket);
        $response = $response->withHeader('Content-Type', 'application/pdf');
        $response->getBody()->write($pdf->toPDFBuffer());
        return $response;
    }

    public function getSellableTickets($request, $response, $app)
    {
        $this->validateLoggedIn($request);
        if($this->user->isInGroupNamed('TicketAdmins') === false)
        {
            return $response->withStatus(401);
        }
        $odata = $request->getAttribute('odata', new \ODataParams(array()));
        $tickets = \Tickets\Ticket::get_tickets_for_user_and_pool($this->user, $odata->filter);
        return $response->withJson($tickets);
    }

    public function updateTicket($request, $response, $args)
    {
        $this->validateLoggedIn($request);
        $id = $args['hash'];
        $ticket_data_table = \Tickets\DB\TicketsDataTable::getInstance();
        $filter = new \Tickets\DB\TicketHashFilter($id);
        $array = (array)$request->getParsedBody();
        $copy = $array;
        unset($copy['firstName']);
        unset($copy['lastName']);
        unset($copy['email']);
        if(count($copy) > 0 && !$this->user->isInGroupNamed("TicketAdmins"))
        {
            return $response->withStatus(401);
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
            throw new Exception('Unable to update DB', \Http\Rest\INTERNAL_ERROR);
        }
        $url = $request->getUri()->getPath();
        $url = substr($url, 0, strrpos($url, '/')+1);
        return $response->withJson(array('hash'=>$hash, 'href'=>$url.$hash));
    }

    public function listDiscretionaryTickets($request, $response, $app)
    {
        $this->validateLoggedIn($request);
        if(!$this->user->isInGroupNamed('AAR') && !$this->user->isInGroupNamed('AFs'))
        {
            throw new Exception('Must be member of AAR group', \Http\Rest\ACCESS_DENIED);
        }
        $odata = $request->getAttribute('odata', new \ODataParams(array()));
        $ticket_data_table = \Tickets\DB\TicketsDataTable::getInstance();
        $filter = new \Tickets\DB\TicketDefaultFilter($this->user->mail, true);
        $tickets = $ticket_data_table->read($filter, $odata->select, $odata->top, $odata->skip, $odata->orderby);
        if($tickets === false)
        {
            $tickets = array();
        }
        else if(!is_array($tickets))
        {
            $tickets = array($tickets);
        }
        return $response->withJson($tickets);
    }

    protected function assignDiscrtionaryTicketsToUser($tickets, $user, $dataTable)
    {
        $res = true;
        $count = count($tickets);
        for($i = 0; $i < $count; $i++)
        {
            $ticket = $tickets[$i];
            $ticket['email'] = $ticket['discretionaryOrig'] = $user->mail;
            $ticket['assigned'] = 1;
            $ticket['discretionary'] = 1;
            unset($ticket['hash_words']);
            if($dataTable->update(new \Tickets\DB\TicketHashFilter($ticket['hash']), $ticket) === false)
            {
                $res = false;
            }
        }
        return $res;
    }

    public function assignDiscretionaryTickets($request, $response, $app)
    {
        $this->validateLoggedIn($request);
        if(!$this->user->isInGroupNamed('AAR'))
        {
            throw new Exception('Must be member of AAR group', \Http\Rest\ACCESS_DENIED);
        }
        $ticket_data_table = \Tickets\DB\TicketsDataTable::getInstance();
        $obj = $request->getParsedBody();
        if(!isset($obj['ticketGroups']))
        {
            throw new Exception('Missing required parameter "ticketGroups"', \Http\Rest\INVALID_PARAM);
        }
        $settings = \Tickets\DB\TicketSystemSettings::getInstance();
        $year = $settings['year'];
        $array = $obj['ticketGroups'];
        $count = count($array);
        $res = true;
        $messages = '';
        for($i = 0; $i < $count; $i++)
        {
            $group = \AuthProvider::getInstance()->getGroupByName($array[$i]['Group']);
            $ticketCount = $array[$i]['Count'];
            $members = $group->members(true, false, false);
            $count1 = count($members);
            for($j = 0; $j < $count1; $j++)
            {
                $user = $members[$j];
                $filter = new \Data\Filter('year eq '.$year.' and type eq \'A\' and pool_id eq -1 and assigned eq 0 and sold eq 0 and discretionary eq 0');
                $tickets = $ticket_data_table->read($filter, false, $ticketCount);
                if($this->assignDiscrtionaryTicketsToUser($tickets, $user, $ticket_data_table) === false)
                {
                    $res = false;
                    $messages+='Unable to assign tickets to '.$user->uid.'\n';
                }
            }
        }
        if($res)
        {
            return $response->withJson($res);
        }
        else
        {
            return $response->withJson(array('res'=>$res, 'messages'=>$messages));
        }
    }

    public function listTicketTypes($request, $response, $app)
    {
        $this->validateLoggedIn($request);
        $ticket_data_set = DataSetFactory::getDataSetByName('tickets');
        $ticket_type_data_table = $ticket_data_set['TicketTypes'];
        $odata = $request->getAttribute('odata', new \ODataParams(array()));
        $ticket_types = $ticket_type_data_table->read($odata->filter, $odata->select, $odata->top, $odata->skip, $odata->orderby);
        if($ticket_types === false)
        {
            $ticket_types = array();
        }
        else if(!is_array($ticket_types))
        {
            $ticket_types = array($ticket_types);
        }
        return $response->withJson($ticket_types);
    }

    public function sendEmail($request, $response, $args)
    {
        $this->validateLoggedIn($request);
        $hash = $args['hash'];
        $ticket = \Tickets\Ticket::get_ticket_by_hash($hash);
        $email_msg = new \Tickets\TicketEmail($ticket);
        $email_provider = EmailProvider::getInstance();
        if($email_provider->sendEmail($email_msg) === false)
        {
            throw new \Exception('Unable to send ticket email!');
        }
        return $response->withJson(true);
    }

    public function claimTicket($request, $response, $app)
    {
        $this->validateLoggedIn($request);
        $hash = $app['hash'];
        $ticket = \Tickets\Ticket::get_ticket_by_hash($hash);
        if($ticket === false)
        {
            return $response->withStatus(404);
        }
        $ticket->email = $this->user->mail;
        $array = (array)$request->getParsedBody();
        if(isset($array['firstName']))
        {
            $ticket->firstName = $array['firstName'];
        }
        if(isset($array['lastName']))
        {
            $ticket->lastName = $array['lastName'];
        }
        $ticket->transferInProgress = 0;
        $res = $ticket->insert_to_db();
        if($res)
        {
            $email_msg = new \Tickets\TicketEmail($ticket);
            $email_provider = EmailProvider::getInstance();
            if($email_provider->sendEmail($email_msg) === false)
            {
                throw new \Exception('Unable to send ticket email!');
            }
        }
        return $response->withJson($res);
    }

    protected function endswith($string, $test)
    {
        $strlen = strlen($string);
        $testlen = strlen($test);
        if ($testlen > $strlen) return false;
        return substr_compare($string, $test, $strlen - $testlen, $testlen) === 0;
    }

    public function transferTicket($request, $response, $app)
    {
        $this->validateLoggedIn($request);
        $hash = $app['hash'];
        $array = (array)$request->getParsedBody();
        if(!isset($array['email']))
        {
            throw new \Exception('Missing Required Parameter email!');
        }
        $settings = \Tickets\DB\TicketSystemSettings::getInstance();
        $year = $settings['year'];
        $ticket = \Tickets\Ticket::get_ticket_by_hash($hash);
        if($ticket === false || $ticket->void == 1)
        {
            return $response->withStatus(404);
        }
        if($ticket->year !== $year)
        {
            return $response->withStatus(404);
        }
        if(filter_var($array['email'], FILTER_VALIDATE_EMAIL) === false)
        {
            throw new \Exception('Invalid value for required parameter email!');
        }
        if($this->endswith($array['email'], 'gmail') || $this->endswith($array['email'], 'yahoo') || $this->endswith($array['email'], 'hotmail') || $this->endswith($array['email'], 'outlook'))
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
        return $response->withJson($res);
    }

    public function spinHash($request, $response, $app)
    {
        $this->validateLoggedIn($request);
        $hash = $app['hash'];
        if(!$this->user->isInGroupNamed('TicketAdmins'))
        {
            return $response->withStatus(401);
        }
        $ticket = \Tickets\Ticket::get_ticket_by_hash($hash);
        if($ticket === false)
        {
            return $response->withStatus(404);
        }
        return $response->withJson($ticket->insert_to_db());
    }

    public function sellTicket($request, $response, $app)
    {
        $this->validateLoggedIn($request);
        $hash = $app['hash'];
        if(!$this->user->isInGroupNamed('TicketAdmins'))
        {
            return $response->withStatus(401);
        }
        $ticket = \Tickets\Ticket::get_ticket_by_hash($hash);
        if($ticket === false)
        {
            return $response->withStatus(404);
        }
        $obj = $request->getParsedBody();
        if($obj === null || $obj === false)
        {
            throw new \Exception('Unable to parse payload!');
        }
        $ticket->sold = 1;
        $ticket->email = $obj['email'];
        if(isset($obj['firstName']))
        {
            $ticket->firstName = $obj['firstName'];
        }
        if(isset($obj['lastName']))
        {
            $ticket->lastName = $obj['lastName'];
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
        return $response->withJson($res);
    }

    public function sellMultipleTickets($request, $response, $app)
    {
        $this->validateLoggedIn($request);
        $obj = (array)$request->getParsedBody();
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
        $res = \Tickets\Ticket::do_sale($this->user, $obj['email'], $obj['tickets'], $message, $firstName, $lastName, $pool);
        return $response->withJson($res); 
    }

    public function verifyShortCode($request, $response, $args)
    {
        $this->validateLoggedIn($request);
        $code = $args['code'];
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
        return $response->withJson($res);
    }

    public function generateTickets($request, $response, $args)
    {
        $this->validateLoggedIn($request);
        if(!$this->user->isInGroupNamed('TicketAdmins'))
        {
            return $response->withStatus(401);
        }
        $obj = (array)$request->getParsedBody();
        $autoPopulate = false;
        if(isset($obj['auto_populate']) && $obj['auto_populate'] === 'on')
        {
            $autoPopulate = true;
        }
        $types = $obj['types'];
        $settings = \Tickets\DB\TicketSystemSettings::getInstance();
        $year = $settings['year'];
        $ticketDataTable = \Tickets\DB\TicketsDataTable::getInstance();
        $f = new \Data\Filter("year eq $year and private_status eq 1");
        $returnVal = array('passed' => 0, 'failed'=> 0, 'messages' => array());
        foreach($types as $type=>$count)
        {
            for($i = 0; $i < $count; $i++)
            {
                $ticket = new \Tickets\Ticket();
                $ticket->year = $year;
                $ticket->type = $type;
                if($ticket->insert_to_db($ticketDataTable))
                {
                    $returnVal['passed']++;
                }
                else
                {
                    $returnVal['failed']++;
                    array_push($returnVal['messages'], 'Failed to create ticket type '.$type);
                }
            }
        }
        if($autoPopulate)
        {
            $dataSet = DataSetFactory::getDataSetByName('tickets');
            $requestDataTable = $dataSet['TicketRequest'];
            $unTicketedRequests = $requestDataTable->read(new \Data\Filter("year eq $year and private_status eq 1"));
            foreach($unTicketedRequests as $request)
            {
                $request_id = $request['request_id'];
                $requestedTickets = json_decode($request['tickets']);
                $fullRequest = true;
                foreach($requestedTickets as $requestedTicket)
                {
                    $requestedTicket = (array)$requestedTicket;
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
                    $unAssignedTickets[0]['used_dt'] = NULL;
                    if($requestedTicket['type'] !== 'A')
                    {
                        $unAssignedTickets[0]['guardian_first'] = $request['givenName'];
                        $unAssignedTickets[0]['guardian_last'] = $request['sn'];
                    }
                    $filter = new \Data\Filter("hash eq '{$unAssignedTickets[0]['hash']}'");
                    unset($unAssignedTickets[0]['hash_words']);
                    $res = $ticketDataTable->update($filter, $unAssignedTickets[0]);
                    if($res === false)
                    {
                        $fullRequest = false;
                        array_push($returnVal['messages'], 'Failed to update ticket '.$unAssignedTickets[0]['hash']);
                    }
                }
                if($fullRequest)
                {
                    $request['private_status'] = 6;
                    $filter = new \Data\Filter("year eq $year and request_id eq '$request_id'");
                    $res = $requestDataTable->update($filter, $request);
                    if($res === false)
                    {
                        array_push($returnVal['messages'], 'Failed to update request '.$request_id);
                    }
                }
            }
        }
        return $response->withJson($returnVal);
    }
}
/* vim: set tabstop=4 shiftwidth=4 expandtab: */
