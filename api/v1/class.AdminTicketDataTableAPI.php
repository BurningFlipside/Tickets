<?php
class AdminTicketDataTableAPI extends Http\Rest\DataTableAPI
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
}
/* vim: set tabstop=4 shiftwidth=4 expandtab: */
