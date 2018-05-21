<?php
class TicketHistoryAPI extends Http\Rest\RestAPI
{
    public function setup($app)
    {
        $app->get('[/]', array($this, 'listTicketHistory'));
        $app->get('/{hash}[/]', 'showTicketHistory');
    }

    public function listTicketHistory($request, $response, $args)
    {
        $this->validateLoggedIn($request);
        if($this->user->isInGroupNamed('TicketAdmins') === false)
        {
            return $response->withStatus(401);
        }
        $odata = $request->getAttribute('odata', new \ODataParams(array()));
        $ticketDataTable = \DataSetFactory::getDataTableByNames('tickets', 'TicketsHistory');
        $tmp = $odata->filter->to_sql_string();
        $sql = 'SELECT * from tblTicketsHistory WHERE '.$tmp.' UNION SELECT * FROM tickets.tblTickets WHERE '.$tmp;
        $tickets = $ticketDataTable->raw_query($sql);
        if($tickets === false)
        {
            $tickets = array();
        }
        else if(!is_array($tickets))
        {
            $tickets = array($tickets);
        }
        $tickets = $odata->filterArrayPerSelect($tickets);
        return $response->withJson($tickets);
    }

    public function showTicketHistory($request, $response, $args)
    {
        $this->validateLoggedIn($request);
        if($this->user->isInGroupNamed('TicketAdmins') === false)
        {
            return $response->withStatus(401);
        }
        $hash = $args['hash'];
        $odata = $request->getAttribute('odata', new \ODataParams(array()));
        $withHistory = $request->getQueryParam('with_history', false);
        if($withHistory === true && $withHistory === '1')
        {
            $ticket = \Tickets\Ticket::get_ticket_history_by_hash($hash);
            if($ticket !== false && $odata->select !== false)
            {
                $tickets = $odata->filterArrayPerSelect(array($ticket));
                $ticket = $tickets[0];
            }
        } 
        else
        {
            $ticket = Ticket::get_ticket_by_hash($hash, $odata->select);
        }
        if($ticket === false)
        {
            return $response->withStatus(404);
        }
        return $response->withJson($ticket);
    }
}
/* vim: set tabstop=4 shiftwidth=4 expandtab: */
