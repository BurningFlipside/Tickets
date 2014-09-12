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
    $constraints = $db->getFlipsideTicketConstraints();
    if($constraints == FALSE)
    {
        echo json_encode(array('error'=>'Internal Error! Unable to obtain constraints'));
    }
    else
    {
        echo json_encode(array('success'=>0, 'constraints'=>$constraints));
    }
}
else
{
    echo json_encode(array('error' => "Unrecognized Operation ".$_SERVER['REQUEST_METHOD']));
    die();
}
/* vim: set tabstop=4 shiftwidth=4 expandtab: */
?>
