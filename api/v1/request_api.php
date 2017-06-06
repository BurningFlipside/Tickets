<?php
require_once('Autoload.php');
require_once('app/TicketAutoload.php');

function request_api_group()
{
    global $app;
    $app->get('(/)', 'listRequests');
    $app->get('/crit_vols', 'getCritVols');
    $app->get('/problems(/:view)', 'getProblems');
    $app->get('/countsByStatus', 'getCountsByStatus');
    $app->get('/:request_id(/:year)', 'getRequest');
    $app->get('/me/:year', 'getRequest');
    $app->get('/:request_id/:year/pdf', 'get_request_pdf');
    $app->get('/:request_id/:year/donations', 'getRequestDonations');
    $app->get('/:request_id/:year/tickets', 'getRequestTickets');
    $app->post('(/)', 'makeRequest');
    $app->post('/Actions/Requests.GetRequestID', 'get_request_id');
    $app->post('/Actions/SetCritVols', 'setCritVols');
    $app->post('/:request_id/:year/Actions/Requests.GetPDF', 'get_request_pdf');
    $app->post('/:request_id/:year/Actions/Requests.SendEmail', 'send_request_email');
    $app->post('/:request_id/:year/Actions/Requests.GetBucket', 'getRequestBucket');
    $app->patch('/:request_id(/:year)', 'editRequest');
}

function safe_json_encode($value)
{
    $encoded = json_encode($value);
    switch(json_last_error())
    {
        case JSON_ERROR_NONE:
            return $encoded;
        case JSON_ERROR_DEPTH:
            return 'Maximum stack depth exceeded'; // or trigger_error() or throw new Exception()
        case JSON_ERROR_STATE_MISMATCH:
            return 'Underflow or the modes mismatch'; // or trigger_error() or throw new Exception()
        case JSON_ERROR_CTRL_CHAR:
            return 'Unexpected control character found';
        case JSON_ERROR_SYNTAX:
            return 'Syntax error, malformed JSON'; // or trigger_error() or throw new Exception()
        case JSON_ERROR_UTF8:
            $clean = utf8ize($value);
            return safe_json_encode($clean);
        default:
            return 'Unknown error'; // or trigger_error() or throw new Exception()
    }
}

function utf8ize($mixed)
{
    if(is_array($mixed))
    {
        foreach($mixed as $key => $value)
        {
            $mixed[$key] = utf8ize($value);
        }
    }
    else if(is_string ($mixed))
    {
        return utf8_encode($mixed);
    }
    return $mixed;
}

function getRequestHelper($request_id, $year)
{
    global $app;
    if($request_id === 'me')
    {
        $request_id = return_request_id();
    }
    if($year === 'current')
    {
        $settings = \Tickets\DB\TicketSystemSettings::getInstance();
        $year = $settings['year'];
    }
    return \Tickets\Flipside\FlipsideTicketRequest::getByIDAndYear($request_id, $year);
} 

function listRequests()
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $params = $app->request->params();
    $requestDataTable = \Tickets\DB\RequestDataTable::getInstance();
    $filter = false;
    $show_children = false;
    if($app->user->isInGroupNamed('TicketAdmins') && $app->odata->filter !== false)
    {
        $filter = $app->odata->filter;
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
        $filter = new \Data\Filter('mail eq \''.$app->user->mail.'\'');
        $show_children = true;
    }
    $search = $app->request->params('$search');
    if($search !== null && $app->user->isInGroupNamed('TicketAdmins'))
    {
        $filter->addToSQLString(" AND (mail LIKE '%$search%' OR sn LIKE '%$search%' OR givenName LIKE '%$search%')");
    }

    $requests = $requestDataTable->read($filter, $app->odata->select, $app->odata->top, $app->odata->skip, $app->odata->orderby);
    if($requests === false)
    {
        echo '[]';
        return;
    }
    if($show_children)
    {
        $request_count = count($requests);
        for($i = 0; $i < $request_count; $i++)
        {
            $requests[$i]->enhanceStatus();
        }
    }
    if($app->odata->count)
    {
        $requests = array('@odata.count'=>count($requests), 'value'=>$requests);
    }
    echo safe_json_encode($requests);
}

function getCritVols()
{
    global $app;
    if(!$app->user || !$app->user->isInGroupNamed('TicketAdmins'))
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $ticket_data_set = DataSetFactory::getDataSetByName('tickets');
    $settings = \Tickets\DB\TicketSystemSettings::getInstance();
    $year = $settings['year'];
    $types = $ticket_data_set->raw_query('SELECT crit_vol,protected,COUNT(*) as count FROM tickets.tblTicketRequest WHERE year='.$year.' GROUP BY crit_vol,protected;');
    $count = count($types);
    for($i = 0; $i < $count; $i++)
    {
        $types[$i]['crit_vol'] = boolval($types[$i]['crit_vol']);
        $types[$i]['protected'] = boolval($types[$i]['protected']);
        $types[$i]['count'] = intval($types[$i]['count']);
    }
    echo json_encode($types);
}

function getRequest($request_id, $year = false)
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
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
        $email = $app->user->mail;
        if($year === false)
        {
            $filter = new \Data\Filter("mail eq '$email'");
        }
        else
        {
            $filter = new \Data\Filter("mail eq '$email' and year eq $year");
        }
    }
    else if($app->user->isInGroupNamed('TicketAdmins'))
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
            $filter = new \Data\Filter('mail eq \''.$app->user->mail.'\' and request_id eq \''.$request_id.'\'');
        }
        else
        {
            $filter = new \Data\Filter('mail eq \''.$app->user->mail.'\' and request_id eq \''.$request_id.'\' and year eq '.$year);
        }
    }
    $requests = $requestDataTable->read($filter, $app->odata->select, $app->odata->top, $app->odata->skip, $app->odata->orderby);
    if($requests === false)
    {
        $requests = array();
    }
    $request_count = count($requests);
    for($i = 0; $i < $request_count; $i++)
    {
        $requests[$i]->enhanceStatus();
    }
    echo json_encode($requests);
}

function makeRequest()
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $obj = $app->get_json_body();
    $request = new \Tickets\Flipside\Request($obj);
    if(!isset($request->request_id))
    {
        throw new Exception('Required Parameter request_id is missing', INVALID_PARAM);
    }
    if(!isset($request->tickets) || !is_array($request->tickets))
    {
        throw new Exception('Required Parameter tickets is missing', INVALID_PARAM);
    }
    $settings = \Tickets\DB\TicketSystemSettings::getInstance();
    $ticket_count = count($obj->tickets);
    if($ticket_count > $settings['max_tickets_per_request'])
    {
        throw new Exception('Too many tickets for request', INVALID_PARAM);
    }

    if(!$app->user->isInGroupNamed('TicketAdmins') && !$app->user->isInGroupNamed('TicketTeam'))
    {
        $request->validateRequestId($app->user->mail);
    }

    $ret = $request->validateTickets(isset($obj->minor_confirm));
    if($ret !== false)
    {
        echo json_encode($ret);
        return;
    }
    $request->modifiedBy = $app->user->uid;
    $request->modifiedByIP = $_SERVER['REMOTE_ADDR'];
    if(isset($request->minor_confirm))
    {
        unset($request->minor_confirm);
    }
    $requestDataTable = \Tickets\DB\RequestDataTable::getInstance();
    $filter = new \Data\Filter("request_id eq '".$request->request_id."' and year eq ".$settings['year']);
    if($requestDataTable->read($filter) === false)
    {
        $requestDataTable->create($request);
    }
    else
    {
        $requestDataTable->update($filter, $request);
    }
    if(strcasecmp($request->mail, $app->user->mail) === 0)
    {
        echo 'true';
    }
    else
    {
        send_request_email($request->request_id, $settings['year']);
    }
}

function return_request_id()
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $ticket_data_set = DataSetFactory::getDataSetByName('tickets');
    $request_id_table = $ticket_data_set['RequestIDs'];
    $filter = new \Data\Filter('mail eq \''.$app->user->mail.'\'');
    $request_ids = $request_id_table->read($filter);
    if($request_ids !== false && isset($request_ids[0]) && isset($request_ids[0]['request_id']))
    {
        return $request_ids[0]['request_id'];
    }
    $request_ids = $request_id_table->read(false, array('MAX(request_id)'));
    $id = 'A00000001';
    if($request_ids !== false && isset($request_ids[0]) && isset($request_ids[0]['MAX(request_id)']))
    {
        $id = $request_ids[0]['MAX(request_id)'];
        $id++;
    }
    $data = array('mail'=>$app->user->mail, 'request_id'=>$id);
    $request_id_table->create($data);
    return $id;
}

function get_request_id()
{
    $id = return_request_id();
    echo json_encode($id);
}

function getRequestByID($id, $year)
{
    try
    {
        return \Tickets\Flipside\FlipsideTicketRequest::getByIDAndYear($entry['id'], $year);
    }
    catch(Exception $e)
    {
        return false;
    }
}

function getRequestByMail($mail, $year, $dataTable)
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

function getRequestFromListEntry($entry, $year, $dataTable)
{
    $request = false;
    if(isset($entry['id']))
    {
        $request = getRequestByID($entry['id'], $year);
        if($request !== false)
        {
            return $request;
        }
    }
    if(isset($entry['mail']))
    {
        $request = getRequestByMail($entry['mail'], $year, $dataTable);
        if($request !== false)
        {
            return $request;
        }
    }
    if(isset($entry[0]))
    {
        $request = getRequestByID($entry[0], $year);
        if($request !== false)
        {
            return $request;
        }
        $request = getRequestByMail($entry[0], $year, $dataTable);
        if($request !== false)
        {
            return $request;
        }
    }
    if(isset($entry[1]))
    {
        $request = getRequestByID($entry[1], $year);
        if($request !== false)
        {
            return $request;
        }
        $request = getRequestByMail($entry[1], $year, $dataTable);
        if($request !== false)
        {
            return $request;
        }
    }
    return false;
}

function setCritVols()
{
    global $app;
    if(!$app->user || !$app->user->isInGroupNamed('AAR'))
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $unprocessed = array();
    $processed = array();
    $list = $app->request->getBody();
    $list = str_getcsv($list, "\n");
    $count = count($list);
    if($count === 1 && ($list[0][0] === '[' || $list[0][0] === '{'))
    {
        $list = $app->getJsonBody(true);
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
        $request = getRequestFromListEntry($list[$i], $year, $data_table);
        if($request === false)
        {
            array_push($unprocessed, $list[$i]);
            continue;
        }
        $request->crit_vol = 1;
        $filter = new \Data\Filter("request_id eq '{$request->request_id}' and year eq $year");
        $data_table->update($filter, $request);
        array_push($processed, $list[$i]);
    }
    echo json_encode(array('processed'=>$processed, 'unprocessed'=>$unprocessed));
}

function get_request_pdf($request_id, $year)
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $request = getRequestHelper($request_id, $year);
    $pdf = new \Tickets\Flipside\RequestPDF($request);
    $app->fmt = 'passthru';
    if($app->request->isPost())
    {
        $app->response->headers->set('Content-Type', 'text/plain');
        echo base64_encode($pdf->toPDFBuffer());
    }
    else
    {
        $app->response->headers->set('Content-Type', 'application/pdf');
        echo $pdf->toPDFBuffer();
    }
}

function getRequestDonations($request_id, $year)
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $requestDataTable = \Tickets\DB\RequestDataTable::getInstance();
    $filter = false;
    if($request_id === 'me')
    {
        $request_id = return_request_id();
        if($year === 'current')
        {
            $settings = \Tickets\DB\TicketSystemSettings::getInstance();
            $year = $settings['year'];
        }
    }
    else if($app->user->isInGroupNamed('TicketAdmins') || $app->user->isInGroupNamed('TicketTeam'))
    {
    }
    else
    {
        if($request_id !== return_request_id())
        {
            throw new Exception('Cannot view another person\'s donations!', ACCESS_DENIED);
        }
    }
    $filter = new \Data\Filter("request_id eq '$request_id' and year eq $year");
    $donations = $requestDataTable->read($filter, array('donations'), $app->odata->top, $app->odata->skip, $app->odata->orderby);
    if($donations !== false)
    {
        $donations = $donations[0]['donations'];
    }
    $donations = $app->odata->filterArrayPerSelect($donations);
    echo json_encode($donations);
}

function getRequestTickets($request_id, $year)
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $requestDataTable = \Tickets\DB\RequestDataTable::getInstance();
    $filter = false;
    if($request_id === 'me')
    {
        $request_id = return_request_id();
        if($year === 'current')
        {
            $settings = \Tickets\DB\TicketSystemSettings::getInstance();
            $year = $settings['year'];
        }
    }
    else if($app->user->isInGroupNamed('TicketAdmins') || $app->user->isInGroupNamed('TicketTeam'))
    {
    }
    else
    {
        if($request_id !== return_request_id())
        {
            throw new Exception('Cannot view another person\'s tickets!', ACCESS_DENIED);
        }
    }
    $filter = new \Data\Filter("request_id eq '$request_id' and year eq $year");
    $tickets = $requestDataTable->read($filter, array('tickets'), $app->odata->top, $app->odata->skip, $app->odata->orderby);
    if($tickets !== false)
    {
        $tickets = $tickets[0]['tickets'];
    }
    $tickets = $app->odata->filterArrayPerSelect($tickets);
    echo json_encode($tickets);
}

function send_request_email($request_id, $year)
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $request = getRequestHelper($request_id, $year);
    $email_msg = new \Tickets\Flipside\FlipsideTicketRequestEmail($request);
    $email_provider = \EmailProvider::getInstance();
    if($email_provider->sendEmail($email_msg) === false)
    {
        throw new \Exception('Unable to send email!');
    }
    echo 'true';
}

function getRequestBucket($request_id, $year)
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $request = getRequestHelper($request_id, $year);
    if($request === false)
    {
        $app->notFound();
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
    else
    {
        $request->bucket = (int)mt_rand(1, ($max_buckets-1));
    }
    if($request->update() === false)
    {
        throw new Exception('Unable to save request!');
    }
    echo json_encode($request);
}

function editRequest($request_id, $year=false)
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $obj = $app->get_json_body();
    $request = new \Tickets\Flipside\Request($obj);
    $settings = \Tickets\DB\TicketSystemSettings::getInstance();
    if(!$app->user->isInGroupNamed('TicketAdmins') && !$app->user->isInGroupNamed('TicketTeam'))
    {
        if(!isset($request->tickets))
        {
            throw new Exception('Required Parameter tickets is missing', INVALID_PARAM);
        }
        $request->validateRequestId($app->user->mail);
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
    $ret = $request->validateTickets(isset($obj->minor_confirm));
    if($ret !== false)
    {
        echo json_encode($ret);
        return;
    }
    $request->modifiedBy = $app->user->uid;
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
    $old_request = getRequestHelper($request_id, $year);
    if($old_request !== false)
    {
        if(!isset($request->request_id))
        {
            $request->request_id = $old_request->request_id;
        }
        $requestDataTable = \Tickets\DB\RequestDataTable::getInstance();
        $filter = new \Data\Filter("request_id eq '".$request->request_id."' and year eq ".$settings['year']);
        $requestDataTable->update($filter, $request);
        echo 'true';
    }
    else
    {
        $app->notFound();
    }
}

function getProblems($view = false)
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    else if(!$app->user->isInGroupNamed('TicketAdmins') && !$app->user->isInGroupNamed('TicketTeam'))
    {
        throw new Exception('Must be Ticket Admin or Ticket Lead', ACCESS_DENIED);
    }
    $ticket_data_set = DataSetFactory::getDataSetByName('tickets');
    if($view === false)
    {
        $view = 'vProblems';
    }
    $data_table = $ticket_data_set[$view];
    $filter = $app->odata->filter;
    if($filter === false)
    {
        $settings = \Tickets\DB\TicketSystemSettings::getInstance();
        $year = $settings['year'];
        $filter = new \Data\Filter("year eq $year");
    }
    $data = $data_table->read($filter, $app->odata->select, $app->odata->top, $app->odata->skip, $app->odata->orderby);
    echo safe_json_encode($data);
}

function getCountsByStatus()
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    else if(!$app->user->isInGroupNamed('TicketAdmins') && !$app->user->isInGroupNamed('TicketTeam'))
    {
        throw new Exception('Must be Ticket Admin or Ticket Lead', ACCESS_DENIED);
    }
    $settings = \Tickets\DB\TicketSystemSettings::getInstance();
    $year = $settings['year'];
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
    echo json_encode($data);
}
