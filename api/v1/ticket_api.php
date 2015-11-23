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
    $app->post('/pos/sell', 'sell_multiple_tickets');
    $app->post('/Actions/VerifyShortCode/:code', 'verifyShortCode');
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
    }
    else
    {
        $filter = new \Tickets\DB\TicketDefaultFilter($app->user->getEmail());
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
    $array = $app->get_json_body(true);
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
    if(!$app->user->isInGroupNamed('AAR'))
    {
        throw new Exception('Must be member of AAR group', ACCESS_DENIED);
    }
    $ticket_data_table = \Tickets\DB\TicketsDataTable::getInstance();
    $filter = new \Tickets\DB\TicketDefaultFilter($app->user->getEmail(), true);
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
    $ticket_data_set = DataSetFactory::get_data_set('tickets');
    $ticket_type_data_table = $ticket_data_set['TicketTypes'];
    $ticket_types = $ticket_type_data_table->search($app->odata->filter, $app->odata->select, $app->odata->top, $app->odata->skip, $app->odata->orderby);
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
    if($email_provider->sendEmail(false, $email_msg) === false)
    {
        throw new \Exception('Unable to send password reset email!');
    }
    echo 'true';
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
    $res = \Tickets\Ticket::do_sale($app->user, $obj['email'], $obj['tickets'], $message);
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
    $filter = new \Data\Filter('substring(hash, "'.$code.'"');
    $ticket_data_table = \Tickets\DB\TicketsDataTable::getInstance();
    $res = $ticket_data_table->read($filter);
    if($res === false) echo 'false';
    else echo 'true';
}

?>
