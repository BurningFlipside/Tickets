<?php
require_once('Autoload.php');
require_once('class.Ticket.php');

function ticket_api_group()
{
    global $app;
    $app->get('', 'list_tickets');
    $app->get('/types', 'list_ticket_types');
    $app->get('/discretionary', 'list_discretionary_tickets');
    $app->get('/:hash', 'show_ticket');
    $app->get('/:hash/pdf', 'get_pdf');
    $app->patch('/:hash', 'update_ticket');
    $app->post('/pos/sell', 'sell_multiple_tickets');
}

function list_tickets()
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $params = $app->request->params();
    $filter = false;
    $select = false;
    if(isset($params['select']))
    {
        $select = explode(',',$params['select']);
    }
    if($app->user->isInGroupNamed('TicketAdmins') && isset($params['filter']))
    {
        $filter = new \Data\Filter($params['filter']);
    }
    else
    {
        $filter = new \Data\Filter('email eq \''.$app->user->getEmail().'\' and discretionary eq 0');
    }
    $ticket_data_set = DataSetFactory::get_data_set('tickets');
    $ticket_data_table = $ticket_data_set['Tickets'];
    $tickets = $ticket_data_table->read($filter, $select);
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

function show_ticket($hash)
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $params = $app->request->params();
    $select = false;
    if(isset($params['select']))
    {
        $select = explode(',',$params['select']);
    }
    if(isset($params['with_history']) && $params['with_history'] === '1')
    {
        $ticket = Ticket::get_ticket_history_by_hash($hash);
    }
    else
    {
        $ticket = Ticket::get_ticket_by_hash($hash, $select);
    }
    if($ticket === false)
    {
        throw new Exception('Unknown ticket', INVALID_PARAM);
    }
    echo $ticket->serializeObject($app->fmt, $select);
}

function get_pdf($hash)
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $ticket = Ticket::get_ticket_by_hash($hash);
    $pdf = new TicketPDF($ticket);
    $app->fmt = 'passthru';
    $app->response->headers->set('Content-Type', 'application/pdf');
    $pdf->generatePDF(true);
}

function typecast($old_object, $new_classname) {
  if(class_exists($new_classname)) {
    // Example serialized object segment
    // O:5:"field":9:{s:5:...   <--- Class: Field
    $old_serialized_prefix  = "O:".strlen(get_class($old_object));
    $old_serialized_prefix .= ":\"".get_class($old_object)."\":";

    $old_serialized_object = serialize($old_object);
    $new_serialized_object = 'O:'.strlen($new_classname).':"'.$new_classname . '":';
    $new_serialized_object .= substr($old_serialized_object,strlen($old_serialized_prefix));
   return unserialize($new_serialized_object);
  }
  else
   return false;
}


function update_ticket($id)
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $params = $app->request->params();
    $ticket_data_set = DataSetFactory::get_data_set('tickets');
    $ticket_data_table = $ticket_data_set['Tickets'];
    $filter = new \Data\Filter('hash eq \''.$id.'\'');    
    $body = $app->request->getBody();
    $array = json_decode($body, true);
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
    $params = $app->request->params();
    $ticket_data_set = DataSetFactory::get_data_set('tickets');
    $ticket_data_table = $ticket_data_set['Tickets'];
    $select = false;
    if(isset($params['select']))
    {
        $select = explode(',',$params['select']);
    }
    $filter = new \Data\Filter('email eq \''.$app->user->getEmail().'\' and discretionary eq 1 and used eq 0');
    $tickets = $ticket_data_table->search($filter, $select);
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
    $params = $app->request->params();
    $ticket_data_set = DataSetFactory::get_data_set('tickets');
    $ticket_type_data_table = $ticket_data_set['TicketTypes'];
    $filter = false;
    $select = false;
    if(isset($params['select']))
    {
        $select = explode(',',$params['select']);
    }
    if(isset($params['filter']))
    {
        $filter = new \Data\Filter($params['filter']);
    }
    $ticket_types = $ticket_type_data_table->search($filter, $select);
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

function sell_multiple_tickets()
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $body = $app->request->getBody();
    $obj  = json_decode($body, true);
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
    $message = FALSE;
    if(isset($obj['message']))
    {
        $message = $obj['message'];
    }
    $res = Ticket::do_sale($app->user, $obj['email'], $obj['tickets'], $message);
    echo json_encode($res); 
}

?>
