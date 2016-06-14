<?php
require_once('Autoload.php');
require_once('app/TicketAutoload.php');

function ticket_history_api_group()
{
    global $app;
    $app->get('', 'list_ticket_history');
    $app->get('/:hash', 'show_ticket_history');
}

function list_ticket_history()
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    if(!$app->user->isInGroupNamed('TicketAdmins'))
    {
        throw new Exception('Must be a ticket admin to view history', ACCESS_DENIED);
    }
    $ticket_data_set = DataSetFactory::getDataSetByName('tickets');
    $ticket_data_table = $ticket_data_set['TicketsHistory'];
    $tmp = $app->odata->filter->to_sql_string();
    $sql = 'SELECT * from tblTicketsHistory WHERE '.$tmp.' UNION SELECT * FROM tickets.tblTickets WHERE '.$tmp;
    $tickets = $ticket_data_table->raw_query($sql);
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

function show_ticket_history($hash)
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

