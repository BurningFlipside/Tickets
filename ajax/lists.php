<?php
if($_SERVER["HTTPS"] != "on")
{
    header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
    exit();
}
require_once("class.FlipSession.php");
require_once("class.FlipsideMailingListInfo.php");
$user = FlipSession::get_user(TRUE);
if($user == FALSE)
{
    echo json_encode(array('error' => "Not Logged In!"));
    die();
}
$is_admin = $user->isInGroupNamed("TicketAdmins");

if(strtoupper($_SERVER['REQUEST_METHOD']) == 'GET')
{
    $lists = FlipsideMailingListInfo::GetAllMailingListInfo();
    if($lists == FALSE)
    {
        echo json_encode(array('error'=>'Internal Error! Unable to obtain lists'));
    }
    else
    {
        echo json_encode(array('success'=>0, 'lists'=>$lists));
    }
}
else
{
    echo json_encode(array('error' => "Unrecognized Operation ".$_SERVER['REQUEST_METHOD']));
    die();
}
/* vim: set tabstop=4 shiftwidth=4 expandtab: */
?>
