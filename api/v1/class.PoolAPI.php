<?php
class PoolAPI extends AdminTicketDataTableAPI
{
    public function __construct()
    {
        parent::__construct('tickets', 'PoolMap', 'pool_id');
    }

    public function setup($app)
    {
        $app->get('/me', array($this, 'listPoolsForUser'));
        $app->post('/{poolId}/Actions/Pool.Assign', array($this, 'assignTicketsToPool'));
        parent::setup($app);
    }

    protected function canRead($request)
    {
        $this->validateLoggedIn($request);
        return $this->user->isInGroupNamed('TicketAdmins');
    }
    protected function canCreate($request)
    {
        $this->validateLoggedIn($request);
        return $this->user->isInGroupNamed('TicketAdmins');
    }

    protected function canUpdate($request, $entity)
    {
        $this->validateLoggedIn($request);
        return $this->user->isInGroupNamed('TicketAdmins');
    }

    protected function canDelete($request, $entity)
    {
        $this->validateLoggedIn($request);
        return $this->user->isInGroupNamed('TicketAdmins');
    }

    public function listPoolsForUser($request, $response, $args)
    {
        if($this->canRead($request) === false)
        {
            return $response->withStatus(401);
        }
        $dataTable = $this->getDataTable();
        $groups = $this->user->getGroups();
        if(empty($groups))
        {
            return $response->withJson(array());
        }
        $count = count($groups);
        $af = false;
        for($i = 0; $i < $count; $i++)
        {
            $name = $groups[$i]->getGroupName();
            $groups[$i] = '\''.$name.'\'';
            if($name === 'AAR')
            {
                $af = true;
            }
        }
        if($af)
        {
            array_push($groups, '\'AFs\'');
        }
        $groups = implode(',', $groups);
        $pools = $dataTable->raw_query('SELECT * FROM tblPoolMap WHERE group_name IN ('.$groups.')');
        return $response->withJson($pools);
    }

    public function assignTicketsToPool($request, $response, $args)
    {
        if($this->canRead($request) === false)
        {
            return $response->withStatus(401);
        }
        $poolId = (int)$args['poolId'];
        $year = \Tickets\DB\TicketSystemSettings::getYear(); 
        $dataTable = \Tickets\DB\TicketsDataTable::getInstance();
        $array = $request->getParsedBody();
        foreach($array as $type => $value)
        {
            //Be paranoid as I'm about to pass this directly to DB
            if(strlen($type) > 1)
            {
                throw new Exception('Invalid type "'.$type.'"');
            }
            $intVal = (int)$value;
            $res = $dataTable->raw_query("UPDATE tblTickets SET pool_id=$poolId WHERE type='$type' AND pool_id=-1 AND assigned=0 AND year=$year LIMIT $intVal");
            if($res === false)
            {
                throw new Exception('Unable to allocate '.$type.' tickets to pool '.$poolId);
            }
        }
        return $response->withJson(true);
    }
}
/* vim: set tabstop=4 shiftwidth=4 expandtab: */
