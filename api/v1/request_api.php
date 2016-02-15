<?php
require_once('Autoload.php');
require_once('app/TicketAutoload.php');

function request_api_group()
{
    global $app;
    $app->get('', 'list_requests');
    $app->get('/crit_vols', 'get_crit_vols');
    $app->get('/problems/:view', 'getProblems');
    $app->get('/:request_id(/:year)', 'get_request');
    $app->get('/me/:year', 'get_request');
    $app->get('/:request_id/:year/pdf', 'get_request_pdf');
    $app->get('/:request_id/:year/donations', 'get_request_donations');
    $app->get('/:request_id/:year/tickets', 'get_request_tickets');
    $app->post('(/)', 'make_request');
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

function odata_filter_to_sql_conditions($filter, $db)
{
    $field = strtok($filter, ' ');
    $operator = strtok(' ');
    $rest = strtok("\0");
    switch($operator)
    {
        case 'ne':
            $operator = '!=';
            break;
        case 'eq':
            $operator = '=';
            break;
        case 'lt':
            $operator = '<';
            break;
        case 'le':
            $operator = '<=';
            break;
        case 'gt':
            $operator = '>';
            break;
        case 'ge':
            $operator = '>=';
            break;
    }
    $ret = array($field=>$operator.$rest);
    return $ret;
}

function list_requests()
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $params = $app->request->params();
    $ticket_data_set = DataSetFactory::get_data_set('tickets');
    $request_data_table = $ticket_data_set['TicketRequest'];
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
        $filter = new \Data\Filter('mail eq \''.$app->user->getEmail().'\'');
        $show_children = true;
    }
    $search = $app->request->params('$search');
    if($search !== null && $app->user->isInGroupNamed('TicketAdmins'))
    {
        $filter->addToSQLString(" AND (mail LIKE '%$search%' OR sn LIKE '%$search%' OR givenName LIKE '%$search%')");
    }
    $requests = $request_data_table->read($filter, $app->odata->select, $app->odata->top, $app->odata->skip, $app->odata->orderby);
    if($requests === false)
    {
        $requests = array();
    }
    else if(!is_array($requests))
    {
        $requests = array($requests);
    }
    if($show_children)
    {
        $ticket_data_table   = $ticket_data_set['RequestedTickets'];
        $donation_data_table = $ticket_data_set['RequestDonation'];
        $status_data_table   = $ticket_data_set['RequestStatus'];
        $status_data_table->prefetch_all();
        $request_count = count($requests);
        for($i = 0; $i < $request_count; $i++)
        {
            $filter = new \Data\Filter('request_id eq \''.$requests[$i]['request_id'].'\' and year eq '.$requests[$i]['year']);
            $requests[$i]['tickets']   = $ticket_data_table->read($filter);
            $requests[$i]['donations'] = $donation_data_table->read($filter);
            $requests[$i]['status'] = $status_data_table[$requests[$i]['status']];
        }
    }
    if($app->odata->count)
    {
        $requests = array('@odata.count'=>count($requests), 'value'=>$requests);
    }
    echo safe_json_encode($requests);
}

function get_crit_vols()
{
    global $app;
    if(!$app->user || !$app->user->isInGroupNamed('TicketAdmins'))
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $ticket_data_set = DataSetFactory::get_data_set('tickets');
    $types = $ticket_data_set->raw_query('SELECT crit_vol,protected,COUNT(*) as count FROM tickets.tblTicketRequest GROUP BY crit_vol,protected;');
    echo json_encode($types);
}

function get_request($request_id, $year = false)
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $ticket_data_set = DataSetFactory::get_data_set('tickets');
    $request_data_table = $ticket_data_set['TicketRequest'];
    $filter = false;
    if($year === 'current')
    {
        $settings = \Tickets\DB\TicketSystemSettings::getInstance();
        $year = $settings['year'];
    }
    if($request_id === 'me')
    {
        $email = $app->user->getEmail();
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
            $filter = new \Data\Filter('mail eq \''.$app->user->getEmail().'\' and request_id eq \''.$request_id.'\'');
        }
        else
        {
            $filter = new \Data\Filter('mail eq \''.$app->user->getEmail().'\' and request_id eq \''.$request_id.'\' and year eq '.$year);
        }
    }
    $requests = $request_data_table->read($filter, $app->odata->select, $app->odata->top, $app->odata->skip, $app->odata->orderby);
    if($requests === false)
    {
        $requests = array();
    }
    else if(!is_array($requests))
    {
        $requests = array($requests);
    }
    $ticket_data_table   = $ticket_data_set['RequestedTickets'];
    $donation_data_table = $ticket_data_set['RequestDonation'];
    $status_data_table   = $ticket_data_set['RequestStatus'];
    $status_data_table->prefetch_all();
    $request_count = count($requests);
    for($i = 0; $i < $request_count; $i++)
    {
        $filter = new \Data\Filter('request_id eq \''.$requests[$i]['request_id'].'\' and year eq '.$requests[$i]['year']);
        $requests[$i]['tickets']   = $ticket_data_table->read($filter);
        $requests[$i]['donations'] = $donation_data_table->read($filter);
        $requests[$i]['status'] = $status_data_table[$requests[$i]['status']];
    }
    echo json_encode($requests);
}

function make_request()
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $obj = $app->get_json_body();
    if(!isset($obj->request_id))
    {
        throw new Exception('Required Parameter request_id is missing', INVALID_PARAM);
    }
    if(!isset($obj->tickets))
    {
        throw new Exception('Required Parameter tickets is missing', INVALID_PARAM);
    }
    $settings = \Tickets\DB\TicketSystemSettings::getInstance();
    $ticket_count = count($obj->tickets);
    if($ticket_count > $settings['max_tickets_per_request'])
    {
        throw new Exception('Too many tickets for request', INVALID_PARAM);
    }
    $ticket_data_set = DataSetFactory::get_data_set('tickets');
    $request_id_table = $ticket_data_set['RequestIDs'];
    $filter = new \Data\Filter('mail eq \''.$app->user->getEmail().'\'');
    $request_ids = $request_id_table->read($filter);
    if($request_ids === false && !isset($request_ids[0]) && !isset($request_ids[0]['request_id']))
    {
        throw new Exception('Request ID not retrievable! Call GetRequestID first.', INVALID_PARAM);
    }
    else if($request_ids[0]['request_id'] !== $obj->request_id)
    {
        throw new Exception('Request ID not correct!', INVALID_PARAM);
    }
    $typeCounts = array();
    for($i = 0; $i < $ticket_count; $i++)
    {
        if(!isset($obj->minor_confirm) && \Tickets\TicketType::typeIsMinor($obj->tickets[$i]->type))
        {
            echo json_encode(array('need_minor_confirm'=>true));
            return;
        }
        if(isset($typeCounts[$obj->tickets[$i]->type]))
        {
            $typeCounts[$obj->tickets[$i]->type]++;
        }
        else
        {
            $typeCounts[$obj->tickets[$i]->type] = 1;
        }
    }
    $count = count($typeCounts);
    $keys = array_keys($typeCounts);
    for($i = 0; $i < $count; $i++)
    {
        if($typeCounts[$keys[$i]] > 1)
        {
            $type = \Tickets\TicketType::getTicketType($keys[$i]);
            if($type->maxPerRequest < $typeCounts[$keys[$i]])
            {
                 throw new Exception('Too many tickets of type '.$keys[$i].' for request', INVALID_PARAM);
            }
        }
    }
    $obj->modifiedBy = $app->user->getUid();
    $obj->modifiedByIP = $_SERVER['REMOTE_ADDR'];
    if(isset($obj->minor_confirm))
    {
        unset($obj->minor_confirm);
    }
    \Tickets\Flipside\FlipsideTicketRequest::createTicketRequest($obj);
    send_request_email($obj->request_id, $settings['year']);
}

function return_request_id()
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $ticket_data_set = DataSetFactory::get_data_set('tickets');
    $request_id_table = $ticket_data_set['RequestIDs'];
    $filter = new \Data\Filter('mail eq \''.$app->user->getEmail().'\'');
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
    $data = array('mail'=>$app->user->getEmail(), 'request_id'=>$id);
    $request_id_table->create($data);
    return $id;
}

function get_request_id()
{
    $id = return_request_id();
    echo json_encode($id);
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
    for($i = 0; $i < $count; $i++)
    {
        $list[$i] = str_getcsv($list[$i]);
    }
    $settings = \Tickets\DB\TicketSystemSettings::getInstance();
    $year = $settings['year'];
    $data_set = DataSetFactory::get_data_set('tickets');
    $data_table = $data_set['TicketRequest'];
    for($i = 0; $i < $count; $i++)
    {
        $request = false;
        try{
        $request = \Tickets\Flipside\FlipsideTicketRequest::getByIDAndYear($list[$i][0], $year);
        } catch(Exception $e) {}
        if($request === false)
        {
            if(isset($list[$i][1]))
            {
                try{
                $request = \Tickets\Flipside\FlipsideTicketRequest::getByIDAndYear($list[$i][1], $year);
                } catch(Exception $e) {}
            }
            if($request === false)
            {
                $filter = new \Data\Filter("mail eq '{$list[$i][0]}' and year eq $year");
                $requests = $data_table->read($filter);
                if($requests !== false && isset($requests[0]))
                {
                    $request = new \Tickets\Flipside\FlipsideTicketRequest($requests[0]);
                }
                else if(isset($list[$i][1]))
                {
                    $filter = new \Data\Filter("mail eq '{$list[$i][1]}' and year eq $year");
                    $requests = $data_table->read($filter);
                    if($requests !== false && isset($requests[0]))
                    {
                        $request = new \Tickets\Flipside\FlipsideTicketRequest($requests[0]);
                    }
                }
                if($request === false)
                {
                    array_push($unprocessed, $list[$i]);
                    continue;
                }
            }
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
    $request = \Tickets\Flipside\FlipsideTicketRequest::getByIDAndYear($request_id, $year);
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

function get_request_donations($request_id, $year)
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $ticket_data_set = DataSetFactory::get_data_set('tickets');
    $donation_data_table = $ticket_data_set['RequestDonation'];
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
    $donations = $donation_data_table->read($filter, $app->odata->select, $app->odata->top, $app->odata->skip, $app->odata->orderby);
    echo json_encode($donations);
}

function get_request_tickets($request_id, $year)
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $ticket_data_set = DataSetFactory::get_data_set('tickets');
    $tickets_data_table = $ticket_data_set['RequestedTickets'];
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
    $tickets = $tickets_data_table->read($filter, $app->odata->select, $app->odata->top, $app->odata->skip, $app->odata->orderby);
    echo json_encode($tickets);
}

function send_request_email($request_id, $year)
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $request = \Tickets\Flipside\FlipsideTicketRequest::getByIDAndYear($request_id, $year);
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
    $settings = \Tickets\DB\TicketSystemSettings::getInstance();
    if($year === 'current')
    {
        $year = $settings['year'];
    }
    $request = \Tickets\Flipside\FlipsideTicketRequest::getByIDAndYear($request_id, $year);
    if($request === false)
    {
        $app->notFound();
    }
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
    $settings = \Tickets\DB\TicketSystemSettings::getInstance();
    if(!$app->user->isInGroupNamed('TicketAdmins') && !$app->user->isInGroupNamed('TicketTeam'))
    {
        if(!isset($obj->tickets))
        {
            throw new Exception('Required Parameter tickets is missing', INVALID_PARAM);
        }
        $ticket_count = count($obj->tickets);
        if($ticket_count > $settings['max_tickets_per_request'])
        {
            throw new Exception('Too many tickets for request', INVALID_PARAM);
        }
        $ticket_data_set = DataSetFactory::get_data_set('tickets');
        $request_id_table = $ticket_data_set['RequestIDs'];
        $filter = new \Data\Filter('mail eq \''.$app->user->getEmail().'\'');
        $request_ids = $request_id_table->read($filter);
        if($request_ids === false && !isset($request_ids[0]) && !isset($request_ids[0]['request_id']))
        {
            throw new Exception('Request ID not retrievable! Call GetRequestID first.', INVALID_PARAM);
        }
        else if($request_ids[0]['request_id'] !== $obj->request_id)
        {
            throw new Exception('Request ID not correct!', INVALID_PARAM);
        }
        if(isset($obj->critvol))
        {
            unset($obj->critvol);
        }
        if(isset($obj->protected))
        {
            unset($obj->protected);
        }
        if(isset($obj->total_received))
        {
            unset($obj->total_received);
        }
        if(isset($obj->status))
        {
            unset($obj->status);
        }
        if(isset($obj->comments))
        {
            unset($obj->comments);
        }
    }
    $ticket_count = 0;
    if(isset($obj->tickets))
    {
        $ticket_count = count($obj->tickets);
    }
    $typeCounts = array();
    for($i = 0; $i < $ticket_count; $i++)
    {
        if(!isset($obj->minor_confirm) && \Tickets\TicketType::typeIsMinor($obj->tickets[$i]->type))
        {
            echo json_encode(array('need_minor_confirm'=>true));
            return;
        }
        if(isset($typeCounts[$obj->tickets[$i]->type]))
        {
            $typeCounts[$obj->tickets[$i]->type]++;
        }
        else
        {
            $typeCounts[$obj->tickets[$i]->type] = 1;
        }
    }
    $count = count($typeCounts);
    $keys = array_keys($typeCounts);
    for($i = 0; $i < $count; $i++)
    {
        if($typeCounts[$keys[$i]] > 1)
        {
            $type = \Tickets\TicketType::getTicketType($keys[$i]);
            if($type->maxPerRequest < $typeCounts[$keys[$i]])
            {
                 throw new Exception('Too many tickets of type '.$keys[$i].' for request', INVALID_PARAM);
            }
        }
    }
    $obj->modifiedBy = $app->user->getUid();
    $obj->modifiedByIP = $_SERVER['REMOTE_ADDR'];
    if(isset($obj->minor_confirm))
    {
        unset($obj->minor_confirm);
    }
    $old_request = \Tickets\Flipside\FlipsideTicketRequest::getByIDAndYear($request_id, $year);
    if($old_request !== false)
    {
        \Tickets\Flipside\FlipsideTicketRequest::updateRequest($obj, $old_request);
        echo 'true';
    }
    else
    {
        $app->notFound();
    }
}

function getProblems($view)
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
    $ticket_data_set = DataSetFactory::get_data_set('tickets');
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

?>
