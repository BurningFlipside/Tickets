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
        $count = count($groups);
        for($i = 0; $i < $count; $i++)
        {
            $groups[$i] = '\''.$groups[$i]->getGroupName().'\'';
        }
        $groups = implode(',', $groups);
        $pools = $dataTable->raw_query('SELECT * FROM tblPoolMap WHERE group_name IN ('.$groups.')');
        return $response->withJson($pools);
    }
}
/* vim: set tabstop=4 shiftwidth=4 expandtab: */
