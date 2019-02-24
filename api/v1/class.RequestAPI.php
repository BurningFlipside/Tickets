<?php
class RequestAPI extends Http\Rest\RestAPI
{
    public function setup($app)
    {
        $app->get('[/]', array($this, 'listRequests'));
        $app->get('/crit_vols', array($this, 'getCritVols'));
        $app->get('/problems[/{view}]', array($this, 'getProblems'));
        $app->get('/countsByStatus[/{year}]', array($this, 'getCountsByStatus'));
        $app->get('/donations', array($this, 'getDonations'));
        $app->get('/{request_id}[/{year}]', array($this, 'getRequest'));
        $app->get('/{request_id}/{year}/pdf', array($this, 'getRequestPdf'));
        $app->get('/{request_id}/{year}/donations', array($this, 'getRequestDonations'));
        $app->get('/{request_id}/{year}/tickets', array($this, 'getRequestTickets'));
        $app->post('[/]', array($this, 'makeRequest'));
        $app->post('/Actions/Requests.GetRequestID', array($this, 'getRequestId'));
        $app->post('/Actions/SetCritVols', array($this, 'setCritVols'));
        $app->post('/{request_id}/{year}/Actions/Requests.GetPDF', array($this, 'getRequestPdf'));
        $app->post('/{request_id}/{year}/Actions/Requests.SendEmail', array($this, 'sendRequestEmail'));
        $app->post('/{request_id}/{year}/Actions/Requests.GetBucket', array($this, 'getRequestBucket'));
        $app->patch('/{request_id}[/{year}]', array($this, 'editRequest'));
    }

    protected function returnRequestId()
    {
        if($this->user === false)
        {
            throw new Exception('Must be logged in', \Http\Rest\ACCESS_DENIED);
        }
        $dataTable = \DataSetFactory::getDataTableByNames('tickets', 'RequestIDs');
        $filter = new \Data\Filter('mail eq \''.$this->user->mail.'\'');
        $request_ids = $dataTable->read($filter);
        if($request_ids !== false && isset($request_ids[0]) && isset($request_ids[0]['request_id']))
        {
            return $request_ids[0]['request_id'];
        }
        $request_ids = $dataTable->read(false, array('MAX(request_id)'));
        $id = 'A00000001';
        if($request_ids !== false && isset($request_ids[0]) && isset($request_ids[0]['MAX(request_id)']))
        {
            $id = $request_ids[0]['MAX(request_id)'];
            $id++;
        }
        $data = array('mail'=>$this->user->mail, 'request_id'=>$id);
        $dataTable->create($data);
        return $id;
    }

    protected function getRequestHelper($request_id, $year)
    {
        if($request_id === 'me')
        {
            $request_id = $this->returnRequestId();
        }
        if($year === 'current')
        {
            $settings = \Tickets\DB\TicketSystemSettings::getInstance();
            $year = $settings['year'];
        }
        return \Tickets\Flipside\FlipsideTicketRequest::getByIDAndYear($request_id, $year);
    }

    protected function getRequestByID($id, $year)
    {
        try
        {
            return \Tickets\Flipside\FlipsideTicketRequest::getByIDAndYear($id, $year);
        }
        catch(Exception $e)
        {
            return false;
        }
    }

    protected function getRequestByMail($mail, $year, $dataTable)
    {
        $filter = new \Data\Filter("mail eq '$mail' and year eq $year");
        $requests = $dataTable->read($filter);
        if($requests !== false && isset($requests[0]))
        {
            return new \Tickets\Flipside\FlipsideTicketRequest($requests[0]);
        }
        else
        {
            return false;
        }
    }

    protected function getRequestFromListEntry($entry, $year, $dataTable)
    {
        $request = false;
        if(is_string($entry))
        {
            $request = $this->getRequestByID($entry, $year);
            if($request !== false)
            {
                return $request;
            }
            $request = $this->getRequestByMail($entry, $year, $dataTable);
            if($request !== false)
            {
                return $request;
            }
        }
        if(isset($entry['id']))
        {
            $request = $this->getRequestByID($entry['id'], $year);
            if($request !== false)
            {
                return $request;
            }
        }
        if(isset($entry['mail']))
        {
            $request = $this->getRequestByMail($entry['mail'], $year, $dataTable);
            if($request !== false)
            {
                return $request;
            }
        }
        if(isset($entry[0]))
        {
            $request = $this->getRequestByID($entry[0], $year);
            if($request !== false)
            {
                return $request;
            }
            $request = $this->getRequestByMail($entry[0], $year, $dataTable);
            if($request !== false)
            {
                return $request;
            }
        }
        if(isset($entry[1]))
        {
            $request = $this->getRequestByID($entry[1], $year);
            if($request !== false)
            {
                return $request;
            }
            $request = $this->getRequestByMail($entry[1], $year, $dataTable);
            if($request !== false)
            {
                return $request;
            }
        }
        return false;
    }

    public function listRequests($request, $response, $args)
    {
        $this->validateLoggedIn($request);
        $params = $request->getQueryParams();
        $requestDataTable = \Tickets\DB\RequestDataTable::getInstance();
        $filter = false;
        $show_children = false;
        $odata = $request->getAttribute('odata', new \ODataParams(array()));
        if(($this->user->isInGroupNamed('TicketAdmins') || $this->user->isInGroupNamed('TicketTeam')) && $odata->filter !== false)
        {
            $filter = $odata->filter;
            if($filter->contains('year eq current'))
            {
                $settings = \Tickets\DB\TicketSystemSettings::getInstance();
                $clause = $filter->getClause('year');
                $clause->var2 = $settings['year'];
            }
            if(isset($params['with_children']))
            {
                $show_children = $params['with_children'];
            }
        }
        else
        {
            $filter = new \Data\Filter('mail eq \''.$this->user->mail.'\'');
            $show_children = true;
        }
        $search = null;
        if(isset($params['$search']))
        {
            $search = $params['$search'];
        }
        if($search !== null && ($this->user->isInGroupNamed('TicketAdmins') || $this->user->isInGroupNamed('TicketTeam')))
        {
            $filter->addToSQLString(" AND (mail LIKE '%$search%' OR sn LIKE '%$search%' OR givenName LIKE '%$search%')");
        }
        $requests = $requestDataTable->read($filter, $odata->select, $odata->top, $odata->skip, $odata->orderby);
        if($requests === false)
        {
            return $response->withJson(array());
        }
        if($show_children)
        {
            $request_count = count($requests);
            for($i = 0; $i < $request_count; $i++)
            {
                $requests[$i]->enhanceStatus();
            }
        }
        if($odata->count)
        {
            $requests = array('@odata.count'=>count($requests), 'value'=>$requests);
        }
        return $response->withJson($requests);
    }

    public function getCritVols($request, $response, $args)
    {
        $this->validateLoggedIn($request);
        if($this->user->isInGroupNamed('TicketAdmins') === false)
        {
            return $response->withStatus(401);
        }
        $ticketDataSet = DataSetFactory::getDataSetByName('tickets');
        $settings = \Tickets\DB\TicketSystemSettings::getInstance();
        $year = $settings['year'];
        $types = $ticketDataSet->raw_query('SELECT crit_vol,protected,COUNT(*) as count FROM tickets.tblTicketRequest WHERE year='.$year.' GROUP BY crit_vol,protected;');
        $count = count($types);
        for($i = 0; $i < $count; $i++)
        {
            $types[$i]['crit_vol'] = boolval($types[$i]['crit_vol']);
            $types[$i]['protected'] = boolval($types[$i]['protected']);
            $types[$i]['count'] = intval($types[$i]['count']);
        }
        return $response->withJson($types);
    }

    public function getRequest($request, $response, $args)
    {
        $this->validateLoggedIn($request);
        $request_id = $args['request_id'];
        $year = 'current';
        if(isset($args['year']))
        {
            $year = $args['year'];
        }
        $requestDataTable = \Tickets\DB\RequestDataTable::getInstance();
        $filter = false;
        if($year === 'current')
        {
            $settings = \Tickets\DB\TicketSystemSettings::getInstance();
            $year = $settings['year'];
        }
        if($request_id === 'me')
        {
            $email = $this->user->mail;
            if($year === false)
            {
                $filter = new \Data\Filter("mail eq '$email'");
            }
            else
            {
                $filter = new \Data\Filter("mail eq '$email' and year eq $year");
            }
        }
        else if($this->user->isInGroupNamed('TicketAdmins'))
        {
            if($year === false)
            {
                $filter = new \Data\Filter("(request_id eq '$request_id' or mail eq '$request_id')");
            }
            else
            {
                $filter = new \Data\Filter("(request_id eq '$request_id' or mail eq '$request_id') and year eq $year");
            }
        }
        else
        {
            if($year === false)
            {
                $filter = new \Data\Filter('mail eq \''.$this->user->mail.'\' and request_id eq \''.$request_id.'\'');
            }
            else
            {
                $filter = new \Data\Filter('mail eq \''.$this->user->mail.'\' and request_id eq \''.$request_id.'\' and year eq '.$year);
            }
        }
        $odata = $request->getAttribute('odata', new \ODataParams(array()));
        $requests = $requestDataTable->read($filter, $odata->select, $odata->top, $odata->skip, $odata->orderby);
        if($requests === false)
        {
            $requests = array();
        }
        $request_count = count($requests);
        for($i = 0; $i < $request_count; $i++)
        {
            $requests[$i]->enhanceStatus();
        }
        return $response->withJson($requests);
    }

    public function makeRequest($httpRequest, $response, $args)
    {
        $this->validateLoggedIn($httpRequest);
        $obj = $httpRequest->getParsedBody();
        $request = new \Tickets\Flipside\Request($obj);
        if(!isset($request->request_id))
        {
            throw new Exception('Required Parameter request_id is missing', \Http\Rest\INVALID_PARAM);
        }
        if(!isset($request->tickets) || !is_array($request->tickets))
        {
            throw new Exception('Required Parameter tickets is missing', \Http\Rest\INVALID_PARAM);
        }
        $settings = \Tickets\DB\TicketSystemSettings::getInstance();
        $ticket_count = count($obj['tickets']);
        if($ticket_count > $settings['max_tickets_per_request'])
        {
            throw new Exception('Too many tickets for request', \Http\Rest\INVALID_PARAM);
        }
        if(!$this->user->isInGroupNamed('TicketAdmins') && !$this->user->isInGroupNamed('TicketTeam'))
        {
             $request->validateRequestId($this->user->mail);
        }
        $ret = $request->validateTickets(isset($obj['minor_confirm']));
        if($ret !== false)
        {
             return $response->withJson($ret);
        }
        $request->modifiedBy = $this->user->uid;
        $request->modifiedByIP = $_SERVER['REMOTE_ADDR'];
        if(isset($request->minor_confirm))
        {
            unset($request->minor_confirm);
        }
        $requestDataTable = \Tickets\DB\RequestDataTable::getInstance();
        $filter = new \Data\Filter("request_id eq '".$request->request_id."' and year eq ".$settings['year']);
        if($requestDataTable->read($filter) === false)
        {
            $res = $requestDataTable->create($request);
        }
        else
        {
            $requestDataTable->update($filter, $request);
        }
        if(strcasecmp($request->mail, $this->user->mail) !== 0)
        {
            return $response->withJson(true);
        }
        else
        {
            $args['request_id'] = $request->request_id;
            $args['year'] = $settings['year'];
            return $this->sendRequestEmail($httpRequest, $response, $args);
        }
    }

    public function getRequestId($httpRequest, $response, $args)
    {
        $this->validateLoggedIn($httpRequest);
        return $response->withJson($this->returnRequestId());
    }

    public function setCritVols($httpRequest, $response, $args)
    {
        $this->validateLoggedIn($httpRequest);
        if($this->user->isInGroupNamed('AAR') === false)
        {
            return $response->withStatus(401);
        }
        $unprocessed = array();
        $processed = array();
        $string = $httpRequest->getBody()->getContents();
        $list = str_getcsv($string, "\n");
        $count = count($list);
        if($count === 1 && ($list[0][0] === '[' || $list[0][0] === '{'))
        {
            $list = json_decode($string, true);
            $list = array_values(array_filter($list));
            $count = count($list);
        }
        else
        {
            for($i = 0; $i < $count; $i++)
            {
                $list[$i] = str_getcsv($list[$i]);
            }
        }
        $settings = \Tickets\DB\TicketSystemSettings::getInstance();
        $year = $settings['year'];
        $data_set = DataSetFactory::getDataSetByName('tickets');
        $data_table = $data_set['TicketRequest'];
        for($i = 0; $i < $count; $i++)
        {
            $request = $this->getRequestFromListEntry($list[$i], $year, $data_table);
            if($request === false)
            {
                array_push($unprocessed, $list[$i]);
                continue;
            }
            $request->crit_vol = 1;
            $res = $request->update();
            if($res === false)
            {
                array_push($unprocessed, $list[$i]);
                continue;
            }
            array_push($processed, $list[$i]);
        }
        return $response->withJson(array('processed'=>$processed, 'unprocessed'=>$unprocessed));
    }

    public function getRequestPdf($httpRequest, $response, $args)
    {
        $this->validateLoggedIn($httpRequest);
        $request_id = 'me';
        $year = 'current';
        if(isset($args['request_id']))
        {
            $request_id = $args['request_id'];
        }
        if(isset($args['year']))
        {
            $year = $args['year'];
        }
        $request = $this->getRequestHelper($request_id, $year);
        $pdf = new \Tickets\Flipside\RequestPDF($request);
        if($httpRequest->isPost())
        {
            $response = $response->withHeader('Content-Type', 'text/plain');
            $response->getBody()->write(base64_encode($pdf->toPDFBuffer()));
        }
        else
        {
            $response = $response->withHeader('Content-Type', 'application/pdf');
            $response->getBody()->write($pdf->toPDFBuffer());
        }
        return $response;
    }

    public function getRequestDonations($httpRequest, $response, $args)
    {
        $this->validateLoggedIn($httpRequest);
        $request_id = 'me';
        $year = 'current';
        if(isset($args['request_id']))
        {
            $request_id = $args['request_id'];
        }
        if(isset($args['year']))
        {
            $year = $args['year'];
        }
        $requestDataTable = \Tickets\DB\RequestDataTable::getInstance();
        $filter = false;
        if($request_id === 'me')
        {
            $request_id = $this->returnRequestId();
            if($year === 'current')
            {
                $settings = \Tickets\DB\TicketSystemSettings::getInstance();
                $year = $settings['year'];
            }
        }
        else if($this->user->isInGroupNamed('TicketAdmins') || $this->user->isInGroupNamed('TicketTeam'))
        {
        }
        else
        {
            if($request_id !== $this->returnRequestId())
            {
                throw new Exception('Cannot view another person\'s donations!', \Http\Rest\ACCESS_DENIED);
            }
        }
        $filter = new \Data\Filter("request_id eq '$request_id' and year eq $year");
        $odata = $request->getAttribute('odata', new \ODataParams(array()));
        $donations = $requestDataTable->read($filter, array('donations'), $odata->top, $odata->skip, $odata->orderby);
        if($donations !== false)
        {
            $donations = $donations[0]['donations'];
        }
        $donations = $odata->filterArrayPerSelect($donations);
        return $response->withJson($donations);
    }

    public function getRequestTickets($httpRequest, $response, $args)
    {
        $this->validateLoggedIn($httpRequest);
        $request_id = 'me';
        $year = 'current';
        if(isset($args['request_id']))
        {
            $request_id = $args['request_id'];
        }
        if(isset($args['year']))
        {
            $year = $args['year'];
        }
        $requestDataTable = \Tickets\DB\RequestDataTable::getInstance();
        $filter = false;
        if($request_id === 'me')
        {
            $request_id = $this->returnRequestId();
            if($year === 'current')
            {
                $settings = \Tickets\DB\TicketSystemSettings::getInstance();
                $year = $settings['year'];
            }
        }
        else if($this->user->isInGroupNamed('TicketAdmins') || $this->user->isInGroupNamed('TicketTeam'))
        {
        }
        else
        {
            if($request_id !== $this->returnRequestId())
            {
                throw new Exception('Cannot view another person\'s tickets!', \Http\Rest\ACCESS_DENIED);
            }
        }
        $filter = new \Data\Filter("request_id eq '$request_id' and year eq $year");
        $odata = $httpRequest->getAttribute('odata', new \ODataParams(array()));
        $tickets = $requestDataTable->read($filter, array('tickets'), $odata->top, $odata->skip, $odata->orderby);
        if($tickets !== false)
        {
            $tickets = $tickets[0]['tickets'];
        }
        $tickets = $odata->filterArrayPerSelect($tickets);
        return $response->withJson($tickets);
    }

    public function sendRequestEmail($httpRequest, $response, $args)
    {
        $this->validateLoggedIn($httpRequest);
        $request_id = 'me';
        $year = 'current';
        if(isset($args['request_id']))
        {
            $request_id = $args['request_id'];
        }
        if(isset($args['year']))
        {
            $year = $args['year'];
        }
        $request = $this->getRequestHelper($request_id, $year);
        $email_msg = new \Tickets\Flipside\FlipsideTicketRequestEmail($request);
        $email_provider = \EmailProvider::getInstance();
        $res = $email_provider->sendEmail($email_msg);
        if($res === false)
        {
            throw new \Exception('Unable to send email!');
        }
        return $response->withJson(true);
    }

    public function getRequestBucket($httpRequest, $response, $args)
    {
        $this->validateLoggedIn($httpRequest);
        $request_id = 'me';
        $year = 'current';
        if(isset($args['request_id']))
        {
            $request_id = $args['request_id'];
        }
        if(isset($args['year']))
        {
            $year = $args['year'];
        }
        $request = $this->getRequestHelper($request_id, $year);
        if($request === false)
        {
            return $response->withStatus(404);
        }
        $settings = \Tickets\DB\TicketSystemSettings::getInstance();
        $max_buckets = $settings['max_buckets'];
        if($request->crit_vol === '1' || $request->crit_vol === true || $request->crit_vol === 1)
        {
            $request->bucket = 0;
        }
        else if($request->protected === '1' || $request->protected === true || $request->protected === 1)
        {
            $request->bucket = $max_buckets;
        }
        else if($request->bucket !== '-1' && $request->bucket !== -1)
        {
            return $response->withJson($request);
        }
        else
        {
            $request->bucket = (int)mt_rand(1, ($max_buckets-1));
        }
        if($request->update() === false)
        {
            throw new Exception('Unable to save request!');
        }
        return $response->withJson($request);
    }

    public function editRequest($httpRequest, $response, $args)
    {
        $this->validateLoggedIn($httpRequest);
        $request_id = 'me';
        $year = 'current';
        if(isset($args['request_id']))
        {
            $request_id = $args['request_id'];
        }
        if(isset($args['year']))
        {
            $year = $args['year'];
        }
        $obj = $httpRequest->getParsedBody();
        $request = new \Tickets\Flipside\Request($obj);
        $settings = \Tickets\DB\TicketSystemSettings::getInstance();
        if(!$this->user->isInGroupNamed('TicketAdmins') && !$this->user->isInGroupNamed('TicketTeam'))
        {
            if(!isset($request->tickets))
            {
                throw new Exception('Required Parameter tickets is missing', \Http\Rest\INVALID_PARAM);
            }
            $request->validateRequestId($this->user->mail);
            if(isset($request->critvol))
            {
                unset($request->critvol);
            }
            if(isset($request->protected))
            {
                unset($request->protected);
            }
            if(isset($request->total_received))
            {
                unset($request->total_received);
            }
            if(isset($request->status))
            {
                unset($request->status);
            }
            if(isset($request->comments))
            {
                unset($request->comments);
            }
        }
        else
        {
            if(isset($request->status))
            {
                $request->private_status = $request->status;
                unset($request->status);
            }
        }
        $old_request = $this->getRequestHelper($request_id, $year);
        if(isset($request->tickets))
        {
            $ret = $request->validateTickets(isset($obj['minor_confirm']));
            if($ret !== false)
            {
                return $response->withJson($ret);
            }
        }
        if($old_request !== false)
        {
            if(!isset($request->tickets))
            {
                $request->tickets = $old_request->tickets;
            }
            if(!isset($request->donations))
            {
                $request->donations = $old_request->donations;
            }
            if(!isset($request->year) || $request->year === 0)
            {
                $request->year = $settings['year'];
            }
        }
        $request->modifiedBy = $this->user->uid;
        $request->modifiedByIP = $_SERVER['REMOTE_ADDR'];
        if(isset($request->minor_confirm))
        {
            unset($request->minor_confirm);
        }
        if(isset($request->dataentry))
        {
            unset($request->dataentry);
        }
        if(isset($request->id))
        {
            $request->request_id = $request->id;
            unset($request->id);
        }
        if($old_request !== false)
        {
            if(!isset($request->request_id))
            {
                $request->request_id = $old_request->request_id;
            }
            $requestDataTable = \Tickets\DB\RequestDataTable::getInstance();
            $filter = new \Data\Filter("request_id eq '".$request->request_id."' and year eq ".$settings['year']);
            $ret = $requestDataTable->update($filter, $request);
            return $response->withJson(true);
        }
        else
        {
            return $response->withStatus(404);
        }
    }

    public function getProblems($httpRequest, $response, $args)
    {
        $this->validateLoggedIn($httpRequest);
        if(!$this->user->isInGroupNamed('TicketAdmins') && !$this->user->isInGroupNamed('TicketTeam'))
        {
             return $response->withStatus(401);
        }
        $view = 'vProblems';
        if(isset($args['view']))
        {
            $view = $args['view'];
        }
        $ticket_data_set = DataSetFactory::getDataSetByName('tickets');
        $data_table = $ticket_data_set[$view];
        $odata = $httpRequest->getAttribute('odata', new \ODataParams(array()));
        $filter = $odata->filter;
        if($filter === false)
        {
            $settings = \Tickets\DB\TicketSystemSettings::getInstance();
            $year = $settings['year'];
            $filter = new \Data\Filter("year eq $year");
        }
        $data = $data_table->read($filter, $odata->select, $odata->top, $odata->skip, $odata->orderby);
        return $response->withJson($data);
    }

    public function getCountsByStatus($httpRequest, $response, $args)
    {
        $this->validateLoggedIn($httpRequest);
        if(!$this->user->isInGroupNamed('TicketAdmins') && !$this->user->isInGroupNamed('TicketTeam'))
        {
             return $response->withStatus(401);
        }
        $settings = \Tickets\DB\TicketSystemSettings::getInstance();
        $year = $settings['year'];
        if(isset($args['year']))
        {
            $year = $args['year'];
        }
        $ticketDataSet = DataSetFactory::getDataSetByName('tickets');
        $data = $ticketDataSet->raw_query('SELECT count(*),private_status FROM tblTicketRequest WHERE year='.$year.' GROUP BY private_status');
        $count = count($data);
        for($i = 0; $i < $count; $i++)
        {
            $data[$i]['private_status'] = intval($data[$i]['private_status']);
            $data[$i]['count'] = intval($data[$i]['count(*)']);
            unset($data[$i]['count(*)']);
        }
        $count = $ticketDataSet->raw_query('SELECT count(*) FROM tblTicketRequest WHERE year='.$year);
        array_push($data, array('all'=>true, 'count'=>intval($count[0]['count(*)'])));
        return $response->withJson($data);
    }

    public function getDonations($request, $response)
    {
        $this->validateLoggedIn($request);
        if(!$this->user->isInGroupNamed('TicketAdmins'))
        {
             return $response->withStatus(401);
        }
        $settings = \Tickets\DB\TicketSystemSettings::getInstance();
        $year = $settings['year'];
        $ticketDataSet = DataSetFactory::getDataSetByName('tickets');
        $data = $ticketDataSet->raw_query('SELECT SUM(donationAmount) AS amount FROM tblTicketRequest WHERE year='.$year.' AND private_status IN (6,1)');
        if(empty($data))
        {
             return $response->withJson(0);
        }
        $data = $data[0];
        return $response->withJson($data['amount']);
    }
}
/* vim: set tabstop=4 shiftwidth=4 expandtab: */
