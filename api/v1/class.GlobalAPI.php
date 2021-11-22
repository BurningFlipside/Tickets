<?php
require('../../app/TicketAutoload.php');

class GlobalAPI extends \Flipside\Http\Rest\RestAPI
{
    public function setup($app)
    {
        $app->get('/constraints', array($this, 'getConstraints'));
        $app->get('/lists', array($this, 'showLists'));
        $app->get('/window', array($this, 'showWindow'));
        $app->get('/users', array($this, 'getTicketUsers'));
        $app->get('/years', array($this, 'getYears'));
        $app->post('/Actions/generatePreview/{class:.*}', array($this, 'previewPDF'));
    }

    public function getConstraints($request, $response, $args)
    {
        $this->validateLoggedIn($request);
        $settings = \Tickets\DB\TicketSystemSettings::getInstance();
        $constraints = array();
        $constraints['max_tickets_per_request'] = intval($settings['max_tickets_per_request']);
        $dataTable = \Flipside\DataSetFactory::getDataTableByNames('tickets', 'TicketTypes');
        $ticketTypes = $dataTable->read(false);
        if($ticketTypes === false)
        {
            $ticketTypes = array();
        }
        else if(!is_array($ticketTypes))
        {
            $ticketTypes = array($ticketTypes);
        }
        $constraints['ticket_types'] = $ticketTypes;
        return $response->withJson($constraints);
    }

    public function showLists($request, $response, $args)
    {
        $this->validateLoggedIn($request);
        $ret = array();
        $list = new stdClass();
        $list->short_name = 'austin-announce';
        $list->name = 'Austin Announce';
        $list->description = "This is the most important list to be on. It is a low traffic email list (10-20 emails per year) and covers only the most important Flipside announcements. Stuff like when Tickets are going on sale, new important policies, announcements important to you even if you're not going this year, etc.";
        $list->request_condition = '1';
        array_push($ret, $list);

        $list = new stdClass();
        $list->short_name = 'flipside-parents';
        $list->name = 'Flipside Parents';
        $list->description = "This is a list for parents of minor children who are attending Flipside. important announcements relavent to parents will be posted to this list. Any parents of minor children attending the event should subscribe to this list.";
        $list->request_condition = 'C > 0 || K > 0 || T > 0';
        array_push($ret, $list);
        return $response->withJson($ret);
    }

    public function showWindow($request, $response, $args)
    {
        $this->validateLoggedIn($request);
        $settings = \Tickets\DB\TicketSystemSettings::getInstance();
        $window = array();
        $window['request_start_date'] = $settings['request_start_date'];
        $window['request_stop_date']  = $settings['request_stop_date'];
        $window['mail_start_date']    = $settings['mail_start_date'];
        $window['test_mode']          = boolval($settings['test_mode']);
        $window['year']               = intval($settings['year']);
        $window['current']            = date("Y-m-d");
        return $response->withJson($window);
    }

    public function getTicketUsers($request, $response, $args)
    {
        $this->validateLoggedIn($request);
        if($this->user->isInGroupNamed('TicketAdmins') === false)
        {
            return $response->withStatus(401);
        }
        $settings = \Flipside\Settings::getInstance();
        $profilesUrl = $settings->getGlobalSetting('profiles_url', 'https://profiles.burningflipside.com/');
        $context = [ 'http' => [ 'method' => 'GET' ], 'ssl' => [ 'verify_peer' => false, 'allow_self_signed'=> true, 'verify_peer_name'=>false] ];
        $context = stream_context_create($context);
        $full = array();
        $res = file_get_contents($profilesUrl.'api/v1/groups/TicketAdmins?$expand=member&$select=member.givenName,member.sn,member.mail,member.uid,cn', false, $context);
        $full[0] = json_decode($res, true);
        $res = file_get_contents($profilesUrl.'api/v1/groups/TicketTeam?$expand=member&$select=member.givenName,member.sn,member.mail,member.uid,cn', false, $context);
        $full[1] = json_decode($res, true);
        $res = array();
        foreach($full[0]['member'] as $member)
        {
            $found = false;
            foreach($res as $existing)
            {
                if(!isset($member['uid']))
                {
                    continue;
                }
                if($member['uid'] === $existing['uid'])
                {
                    $found = true;
                    break;
                }
            }
            if(!$found)
            {
                $member['admin'] = true;
                array_push($res, $member);
            }
        }
        foreach($full[1]['member'] as $member)
        {
            $found = false;
            foreach($res as $existing)
            {
                if(!isset($member['uid']) || !isset($existing['uid']))
                {
                    continue;
                }
                if($member['uid'] === $existing['uid'])
                {
                    $found = true;
                    break;
                }
            }
            if(!$found)
            {
                $member['admin'] = false;
                array_push($res, $member);
            }
        }
        return $response->withJson($res);
    }

    public function getYears($request, $response, $args)
    {
        $this->validateLoggedIn($request);
        if($this->user->isInGroupNamed('TicketAdmins') === false)
        {
            return $response->withStatus(401);
        }
        $dataSet = \Flipside\DataSetFactory::getDataSetByName('tickets');
        $res = $dataSet->raw_query('SELECT DISTINCT(year) from tblTicketRequest');
        $count = count($res);
        for($i = 0; $i < $count; $i++)
        {
            $res[$i] = intval(array_values($res[$i])[0]);
        }
        $settings = \Tickets\DB\TicketSystemSettings::getInstance();
        $current = intval($settings['year']);
        if(!in_array($current, $res))
        {
            array_push($res, $current);
        }
        return $response->withJson($res);
    }

    public function previewPDF($request, $response, $args)
    {
        $this->validateLoggedIn($request);
        if($this->user->isInGroupNamed('TicketAdmins') === false)
        {
            return $response->withStatus(401);
        }
        $type = '\\'.str_replace('/', '\\', $args['class']);
        $body = $request->getBody()->getContents();
        $pdf = new $type(false, $body);
        $response = $response->withHeader('Content-Type', 'text/html');
        $response->getBody()->write(base64_encode($pdf->toPDFBuffer()));
        return $response;
    }
}
/* vim: set tabstop=4 shiftwidth=4 expandtab: */
