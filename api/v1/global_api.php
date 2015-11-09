<?php
require_once('app/TicketAutoload.php');

function global_api_group()
{
    global $app;
    $app->get('/constraints', 'show_constraints');
    $app->get('/donation_types', 'show_donation_types');
    $app->post('/donation_types', 'createDonationType');
    $app->delete('/donation_types/:id', 'deleteDonationType');
    $app->get('/lists', 'show_lists');
    $app->get('/window', 'show_window');
    $app->get('/statuses', 'list_statuses');
    $app->get('/vars', 'get_vars');
    $app->get('/vars/:name', 'get_var');
    $app->patch('/vars/:name', 'set_var');
    $app->post('/vars/:name', 'create_var');
    $app->delete('/vars/:name', 'del_var');
}

function show_constraints()
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $settings = \Tickets\DB\TicketSystemSettings::getInstance();
    $constraints = array();
    $constraints['max_tickets_per_request'] = $settings['max_tickets_per_request'];
    $ticket_data_set = DataSetFactory::get_data_set('tickets');
    $ticket_type_data_table = $ticket_data_set['TicketTypes'];
    $ticket_types = $ticket_type_data_table->search();
    if($ticket_types === false)
    {
        $ticket_types = array();
    }
    else if(!is_array($ticket_types))
    {
        $ticket_types = array($ticket_types);
    }
    $constraints['ticket_types'] = $ticket_types;
    echo json_encode($constraints);
}

function show_donation_types()
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $ticket_data_set = DataSetFactory::get_data_set('tickets');
    $donation_type_data_table = $ticket_data_set['DonationTypes'];
    $donation_types = $donation_type_data_table->read();
    echo json_encode($donation_types);
}

function createDonationType()
{
    global $app;
    if(!$app->user || !$app->user->isInGroupNamed('TicketAdmins'))
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $data = $app->getJsonBody();
    $ticket_data_set = DataSetFactory::get_data_set('tickets');
    $donation_type_data_table = $ticket_data_set['DonationTypes'];
    $res = $donation_type_data_table->create($data);
    if($res === false)
    {
        $filter = new \Data\Filter("entityName eq '{$data->entityName}'");
        $res = $donation_type_data_table->update($filter, $data);
    }
    echo json_encode($res);
}

function deleteDonationType($id)
{
    global $app;
    if(!$app->user || !$app->user->isInGroupNamed('TicketAdmins'))
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $ticket_data_set = DataSetFactory::get_data_set('tickets');
    $donation_type_data_table = $ticket_data_set['DonationTypes'];
    $filter = new \Data\Filter("entityName eq '$id'");
    $res = $donation_type_data_table->delete($filter);
    echo json_encode($res);
}

function show_lists()
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
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
    echo json_encode($ret);
}

function show_window()
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $settings = \Tickets\DB\TicketSystemSettings::getInstance();
    $window = array();
    $window['request_start_date'] = $settings['request_start_date'];
    $window['request_stop_date']  = $settings['request_stop_date'];
    $window['mail_start_date']    = $settings['mail_start_date'];
    $window['test_mode']          = $settings['test_mode'];
    $window['year']               = $settings['year'];
    $window['current']            = date("Y-m-d");
    echo json_encode($window);
}

function list_statuses()
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $params = $app->request->params();
    $ticket_data_set = DataSetFactory::get_data_set('tickets');
    $status_data_table = $ticket_data_set['RequestStatus'];
    $statuses = $status_data_table->read($app->odata->filter, $app->odata->select);
    if($statuses === false)
    {
        $statuses = array();
    }
    else if(!is_array($statuses))
    {
        $statuses = array($statuses);
    }
    echo json_encode($statuses);
}

function get_vars()
{
    global $app;
    if(!$app->user || !$app->user->isInGroupNamed('TicketAdmins'))
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $settings = \Tickets\DB\TicketSystemSettings::getInstance();
    $vars = $settings->toArray();
    echo json_encode($vars);
}

function get_var($name)
{
    global $app;
    if(!$app->user || !$app->user->isInGroupNamed('TicketAdmins'))
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $settings = \Tickets\DB\TicketSystemSettings::getInstance();
    echo json_encode($settings[$name]);
}

function set_var($name)
{
    global $app;
    if(!$app->user || !$app->user->isInGroupNamed('TicketAdmins'))
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $settings = Tickets\DB\TicketSystemSettings::getInstance();
    $val = $app->get_json_body();
    $ret = $settings[$name] = $val;
    echo json_encode($ret);
}

function create_var($name)
{
    global $app;
    if(!$app->user || !$app->user->isInGroupNamed('TicketAdmins'))
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $settings = \Tickets\DB\TicketSystemSettings::getInstance();
    $val = $app->get_json_body();
    $ret = $settings[$name] = $val;
    echo json_encode($ret);
}

function del_var($name)
{
     global $app;
    if(!$app->user || !$app->user->isInGroupNamed('TicketAdmins'))
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $settings = \Tickets\DB\TicketSystemSettings::getInstance();
    unset($settings[$name]);
    echo 'true';
}

?>
