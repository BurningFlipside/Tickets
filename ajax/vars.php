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

if(strtoupper($_SERVER['REQUEST_METHOD']) == 'GET')
{
    if(!$is_admin)
    {
        echo json_encode(array('error' => "Access Denied! User must be a member of TicketAdmins!"));
    }
    else
    {
        $db = new FlipsideTicketDB();
        $vars = $db->getAllVars();
        if($vars === FALSE)
        {
            echo json_encode(array('error' => "Internal Error! Failed to obtain variables!"));
        }
        else
        {
            echo json_encode(array('success' => 0, 'vars' => $vars));
        }
    } 
}
else if(strtoupper($_SERVER['REQUEST_METHOD']) == 'POST')
{
    if(!$is_admin)
    {
        echo json_encode(array('error' => "Access Denied! User must be a member of TicketAdmins!"));
        die();
    }
    if(isset($_POST['delete']))
    {
        $db = new FlipsideTicketDB();
        if($db->deleteVariable($_POST['delete']) === FALSE)
        {
            echo json_encode(array('error' => "Internal Error! Failed to delete variable!"));
        }
        else
        {
            echo json_encode(array('success' => 0));
        }
    }
    else
    {
        if(!isset($_POST['name']) || !is_string($_POST['name']))
        {
            echo json_encode(array('error' => "Invalid Parameter! Expected name as a string"));
            die();
        }
        if(!isset($_POST['value']) || !is_string($_POST['value']))
        {
            echo json_encode(array('error' => "Invalid Parameter! Expected value as a string"));
            die();
        }
        $db = new FlipsideTicketDB();
        if($db->setVariable($_POST['name'], $_POST['value']) === FALSE)
        {
            echo json_encode(array('error' => "Internal Error! Failed to set variable!"));
        }
        else
        {
            echo json_encode(array('success' => 0));
        }
    }
}
else
{
    echo json_encode(array('error' => "Unrecognized Operation ".$_SERVER['REQUEST_METHOD']));
    die();
}
/* vim: set tabstop=4 shiftwidth=4 expandtab: */
?>
