<?php
if($_SERVER["HTTPS"] != "on")
{
    header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
    exit();
}
require_once("class.FlipSession.php");
require_once("class.FlipsideTicketDB.php");
$user = FlipSession::get_user(TRUE);
if($user == FALSE)
{
    echo json_encode(array('error' => "Not Logged In!"));
    die();
}
$is_admin = $user->isInGroupNamed("TicketAdmins");
$db = new FlipsideTicketDB();

if(strtoupper($_SERVER['REQUEST_METHOD']) == 'GET')
{
    $request = $db->getRequestForUser($user);
    if($request == FALSE)
    {
        //Not an error condition, they just don't have a request yet!
        echo json_encode(array('success'=>0));
    }
    else
    {
        echo json_encode(array('success'=>0, 'request'=>$request->toArray()));
    }
}
else
{
    $request = new FlipsideTicketRequest();
    echo json_encode(array('error' => "Unrecognized Operation ".$_SERVER['REQUEST_METHOD']));
    die();
}
/* vim: set tabstop=4 shiftwidth=4 expandtab: */
?>
