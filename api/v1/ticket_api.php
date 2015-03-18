<?php
require_once('class.Ticket.php');

function ticket_api_group()
{
    global $app;
    $app->get('', 'list_tickets');
    $app->get('/types', 'list_ticket_types');
    $app->get('/discretionary', 'list_discretionary_tickets');
    $app->get('/search/:data', 'search_tickets');
    $app->get('/:hash', 'show_ticket');
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
    if(isset($params['fmt']))
    {
       unset($params['fmt']);
    }
    $tickets = array();
    if(isset($params['with_pool']) && $params['with_pool'])
    {
        unset($params['with_pool']);
        process_params($params);
        $tickets = Ticket::get_tickets_for_user_and_pool($app->user, $params);
    }
    else
    {
        process_params($params);
        $tickets = Ticket::get_tickets_for_user($app->user, $params);
    }
    if($tickets === FALSE)
    {
        $tickets = array();
    }
    else if(!is_array($tickets))
    {
        $tickets = array($tickets);
    }
    echo json_encode($tickets);
}

function show_ticket($id)
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $ticket = Ticket::get_ticket_by_hash($id);
    if(is_array($ticket))
    {
        $ticket = $ticket[0];
    }
    echo json_encode($ticket);
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
    if(!$app->user->isInGroupNamed("TicketAdmins"))
    {
        throw new Exception('Must be member of TicketAdmins group', ACCESS_DENIED);
    }
    $ticket = Ticket::get_ticket_by_hash($id);
    if(is_array($ticket))
    {
        $ticket = $ticket[0];
    }
    $body   = $app->request->getBody();
    $patch  = json_decode($body);
    $ticket = (object)array_merge((array)$ticket, (array)$patch);
    $ticket = typecast($ticket, 'Ticket');
    $db = new FlipsideTicketDB();
    if($ticket->replace_in_db($db))
    {
        echo json_encode($ticket);
    }
    else
    {
        throw new Exception('Failed to update ticket', INTERNAL_ERROR);
    }
}

function list_discretionary_tickets()
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    if(!$app->user->isInGroupNamed("AAR"))
    {
        throw new Exception('Must be member of AAR group', ACCESS_DENIED);
    }
    $db = new FlipsideTicketDB();
    $conds = array('email' => '=\''.$app->user->getEmail().'\'', 'discretionary'=>'=1', 'used'=>'=0');
    $tickets = Ticket::select_from_db_multi_conditions($db, $conds);
    if($tickets === false)
    {
        echo json_encode(false);
        return;
    }
    else if(!is_array($tickets))
    {
        $tickets = array($tickets);
    }
    echo json_encode($tickets);
}

function search_tickets($data)
{
    $tickets = Ticket::searchForTickets('*', $data, TRUE);
    if($tickets === false)
    {
        echo json_encode(false);
        return;
    }
    else if(!is_array($tickets))
    {
        $tickets = array($tickets);
    }
    if(isset($tickets['history']))
    {
        unset($tickets['history']);
        echo json_encode(array('old_tickets'=>$tickets));
    }
    else
    {
        echo json_encode($tickets);
    }
}

function list_ticket_types()
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $db = new FlipsideTicketDB();
    $constraints = $db->getFlipsideTicketConstraints();
    echo json_encode($constraints);
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

function process_params(&$params)
{
    foreach($params as $key=>$value)
    {
        if($key === '_')
        {
            unset($params[$key]);
        }
        else
        {
            $params[$key] = '='.$value;
        }
    }
}

?>
