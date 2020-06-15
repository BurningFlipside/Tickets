<?php
class TicketDataTableAPI extends Flipside\Http\Rest\DataTableAPI
{
    public function __construct($dataSet, $dataTable, $primaryKeyName)
    {
        parent::__construct($dataSet, $dataTable, $primaryKeyName);
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
}
/* vim: set tabstop=4 shiftwidth=4 expandtab: */
