<?php
class AdminTicketDataTableAPI extends \Flipside\Http\Rest\DataTableAPI
{
    public function __construct($dataSetName, $dataTableName, $primaryKeyName = false)
    {
        parent::__construct($dataSetName, $dataTableName, $primaryKeyName);
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

    protected function processEntry($obj, $request)
    {
        $args = $request->getAttribute('route')->getArguments();
        if(empty($args))
        {
            return $obj;
        }
        return $obj['value'];
    }

    public function updateEntry($request, $response, $args)
    {
        if($this->canRead($request) === false)
        {
            return $response->withStatus(401);
        }
        $filter = $this->getFilterForPrimaryKey($args['name']);
        $dataTable = $this->getDataTable();
        $entry = $dataTable->read($filter);
        if(empty($entry))
        {
            return $response->withStatus(404);
        }
        if($this->canUpdate($request, $entry) === false)
        {
            return $response->withStatus(401);
        }
        $obj = $request->getParsedBody();
        if($obj === null)
        {
            $body = $request->getBody();
            $body->rewind();
            $tmp = $body->getContents();
            $obj = array('value'=>json_decode($tmp));
            if($obj['value'] === null)
            {
                $obj['value'] = $tmp;
            }
        }
        if($this->validateUpdate($obj, $request, $entry) === false)
        {
            return $response->withStatus(400);
        }
        $ret = $dataTable->update($filter, $obj, true);
        return $response->withJson($ret);
    }

}
/* vim: set tabstop=4 shiftwidth=4 expandtab: */
