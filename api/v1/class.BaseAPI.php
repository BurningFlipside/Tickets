<?php
class BaseAPI extends \Flipside\Http\Rest\RestAPI
{
    public function setup($app)
    {
        $app->get('', array($this, 'getRoot'));
    }

    public function getRoot($request, $response, $args)
    {
        $ret = array();
        $root = $request->getUri()->getBasePath();
        $ret['@odata.context'] = $root.'/$metadata';
        $ret['value'] = array();
        $ret['value']['Tickets'] = array('@odata.id'=>$root.'/tickets');
        $ret['value']['TicketsHistory'] = array('@odata.id'=>$root.'/tickets_history');
        $ret['value']['Requests'] = array('@odata.id'=>$root.'/requests');
        $ret['value']['Globals'] = array('@odata.id'=>$root.'/globals');
        return $response->withJson($ret);
    }
}
/* vim: set tabstop=4 shiftwidth=4 expandtab: */
