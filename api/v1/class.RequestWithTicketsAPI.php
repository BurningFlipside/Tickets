<?php
class RequestWithTicketsAPI extends Flipside\Http\Rest\RestAPI
{
    public function setup($app)
    {
        $app->get('[/]', array($this, 'listRequestsWithTickets'));
        $app->get('/types', array($this, 'getRequestedTypes'));
        $app->get('/minorMails', array($this, 'getMinorMailout'));
        $app->get('/{request_id}[/{year}]', array($this, 'getRequestWithTickets'));
    }

    public function listRequestsWithTickets($request, $response, $args)
    {
        $this->validateLoggedIn($request);
        $requestDataTable = \Tickets\DB\RequestDataTable::getInstance();
        $filter = false;
        $odata = $request->getAttribute('odata', new \Flipside\ODataParams(array()));
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
            $filter = new \Flipside\Data\Filter('mail eq \''.$this->user->mail.'\'');
        }
        $requests = $requestDataTable->read($filter);
        if($requests === false)
        {
            return $response->withJson(array());
        }
        $count = count($requests);
        $returnArray = array();
        for($i = 0; $i < $count; $i++)
        {
            if($requests[$i]['tickets'] === null)
            {
                continue;
            }
            $count2 = count($requests[$i]['tickets']);
            for($j = 0; $j < $count2; $j++)
            {
                $tmp = (array)$requests[$i];
                unset($tmp['tickets']);
                $tmp['first'] = $requests[$i]['tickets'][$j]->first;
                $tmp['last'] = $requests[$i]['tickets'][$j]->last;
                $tmp['type'] = $requests[$i]['tickets'][$j]->type;
                array_push($returnArray, $tmp);
            }
        }
        $requests = $odata->filterArrayPerSelect($returnArray);
        if($odata->count)
        {
            $requests = array('@odata.count'=>count($requests), 'value'=>$requests);
        }
        return $response->withJson($requests);
    }

    public function getMinorMailout($request, $response, $args)
    {
        $this->validateLoggedIn($request);
        $requestDataTable = \Tickets\DB\RequestDataTable::getInstance();
        $filter = false;
        $odata = $request->getAttribute('odata', new \Flipside\ODataParams(array()));
        if(!$this->user->isInGroupNamed('TicketAdmins'))
        {
            return $response->withJson(array());
        }
        else if($odata->filter === false)
        {
            $settings = \Tickets\DB\TicketSystemSettings::getInstance();
            $filter = new \Flipside\Data\Filter('year eq '.$settings['year'].' and private_status eq 6');
        }
        else
        {
            $filter = $odata->filter;
            if($filter->contains('year eq current'))
            {
                $settings = \Tickets\DB\TicketSystemSettings::getInstance();
                $clause = $filter->getClause('year');
                $clause->var2 = $settings['year'];
            }
        }
        $select = false;
        if($odata->select === false)
        {
            $select = ['request_id', 'givenName', 'sn', 'mail', 'street', 'l', 'st', 'zip', 'c', 'tickets'];
        }
        $requests = $requestDataTable->read($filter, $select);
        if($requests === false)
        {
            return $response->withJson(array());
        }
        $count = count($requests);
        $returnArray = array();
        for($i = 0; $i < $count; $i++)
        {
            if($requests[$i]['tickets'] === null)
            {
                continue;
            }
            $count2 = count($requests[$i]['tickets']);
            for($j = 0; $j < $count2; $j++)
            {
                $tmp = (array)$requests[$i];
                unset($tmp['tickets']);
                if($requests[$i]['tickets'][$j]->type !== 'A')
                {
                    $tmp['minorFirst'] = $requests[$i]['tickets'][$j]->first;
                    $tmp['minorLast'] = $requests[$i]['tickets'][$j]->last;
                    $tmp['type'] = $requests[$i]['tickets'][$j]->type;
                    array_push($returnArray, $tmp);
                }
            }
        }
        $requests = $odata->filterArrayPerSelect($returnArray);
        if($odata->count)
        {
            $requests = array('@odata.count'=>count($requests), 'value'=>$requests);
        }
        return $response->withJson($requests);
    }

    public function getRequestWithTickets($httpRequest, $response, $args)
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
        $params = $request->getQueryParams();
        $requestDataTable = \Tickets\DB\RequestDataTable::getInstance();
        $filter = false;
        $filter_str = "request_id eq '$request_id'";
        if($year !== false)
        {
            $filter_str += ' and year eq '+$year;
        }
        if(!$this->user->isInGroupNamed('TicketAdmins'))
        {
            $filter_str += ' and mail eq '+$app->user->mail;
        }
        $filter = new \Flipside\Data\Filter($filter_str);
        $requests = $requestDataTable->read($filter);
        if($requests === false)
        {
            $requests = array();
        }
        $count = count($requests);
        $returnArray = array();
        for($i = 0; $i < $count; $i++)
        {
            if($requests[$i]['tickets'] === null)
            {
                continue;
            }
            $count2 = count($requests[$i]['tickets']);
            for($j = 0; $j < $count2; $j++)
            {
                $tmp = (array)$requests[$i];
                unset($tmp['tickets']);
                $tmp['first'] = $requests[$i]['tickets'][$j]->first;
                $tmp['last'] = $requests[$i]['tickets'][$j]->last;
                $tmp['type'] = $requests[$i]['tickets'][$j]->type;
                array_push($returnArray, $tmp);
            }
        }
        $odata = $request->getAttribute('odata', new \Flipside\ODataParams(array()));
        $requests = $odata->filterArrayPerSelect($returnArray);
        if($odata->count)
        {
            $requests = array('@odata.count'=>count($requests), 'value'=>$requests);
        }
        return $response->withJson($requests);
    }

    public function getRequestedTypes($httpRequest, $response, $args)
    {
        $this->validateLoggedIn($httpRequest);
        if(!$this->user->isInGroupNamed('TicketAdmins'))
        {
            return $response->withStatus(401);
        }
        $settings = \Tickets\DB\TicketSystemSettings::getInstance();
        $year = $settings['year'];
    
        $requestDataTable = \Tickets\DB\RequestDataTable::getInstance();
        $requests = $requestDataTable->read(new \Flipside\Data\Filter('year eq '.$year), array('tickets,private_status'));
        if(empty($requests))
        {
            $requests = array();
        }
        $tmp = array();
        $requestCount = count($requests);
        for($i = 0; $i < $requestCount; $i++)
        {
            $request = $requests[$i];
            if(!is_array($request['tickets']))
            {
                continue;
            }
            $ticketCount = count($request['tickets']);
            for($j = 0; $j < $ticketCount; $j++)
            {
                $ticket = $request['tickets'][$j];
                if(!isset($tmp[$ticket->type]))
                {
                    $tmp[$ticket->type] = array('count'=>0, 'receivedCount'=>0);
                }
                if($request['private_status'] === 6 || $request['private_status'] === 1)
                {
                    $tmp[$ticket->type]['receivedCount']++;
                }
                $tmp[$ticket->type]['count']++;
            }
        }
        $typeDataTable = \Flipside\DataSetFactory::getDataTableByNames('tickets', 'TicketTypes');
        $types = $typeDataTable->read(false, array('typeCode', 'description'));
        $count = count($types);
        for($i = 0; $i < $count; $i++)
        {
            $typeCode = $types[$i]['typeCode'];
            if(isset($tmp[$typeCode]))
            {
                $types[$i]['count'] = $tmp[$typeCode]['count'];
                $types[$i]['receivedCount'] = $tmp[$typeCode]['receivedCount'];
            }
            else
            {
                $types[$i]['count'] = 0;
                $types[$i]['receivedCount'] = 0;
            }
        }
        return $response->withJson($types);
    }
}
/* vim: set tabstop=4 shiftwidth=4 expandtab: */
