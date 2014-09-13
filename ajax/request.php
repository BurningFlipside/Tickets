<?php
if($_SERVER["HTTPS"] != "on")
{
    header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
    exit();
}
require_once("class.FlipSession.php");
require_once("class.FlipsideTicketRequest.php");
$user = FlipSession::get_user(TRUE);
if($user == FALSE)
{
    echo json_encode(array('error' => "Not Logged In!"));
    die();
}
$is_admin = $user->isInGroupNamed("TicketAdmins");

function get_single_value_from_array($array)
{
    if(!is_array($array))
    {
        return $array;
    }
    if(isset($array[0]))
    {
        return $array[0];
    }
    else
    {
        return '';
    }
}

if(strtoupper($_SERVER['REQUEST_METHOD']) == 'GET')
{
    if(!isset($_GET['id']))
    {
        $id = FlipsideTicketRequest::getRequestId($user);
        if($id == FALSE)
        {
            echo json_encode(array('error' => "Internal Error! Failed to obtain request ID for user!"));
        }
        else
        {
            echo json_encode(array('success' => 0, 'id' => $id));
        }
    }
    else
    {
        $data = array();
        echo json_encode(array('data'=>$data));
    }
}
else if(strtoupper($_SERVER['REQUEST_METHOD']) == 'POST')
{
    if(!isset($_POST['request_id']) || !is_string($_POST['request_id']))
    {
        echo json_encode(array('error' => "Invalid Parameter! Expected request_id as a string"));
        die();
    }
    if(!isset($_POST['givenName']) || !is_string($_POST['givenName']))
    {
        echo json_encode(array('error' => "Invalid Parameter! Expected givenName as a string"));
        die();
    }
    if(!isset($_POST['sn']) || !is_string($_POST['sn']))
    {
        echo json_encode(array('error' => "Invalid Parameter! Expected sn as a string"));
        die();
    }
    if(!isset($_POST['mail']) || !is_string($_POST['mail']))
    {
        echo json_encode(array('error' => "Invalid Parameter! Expected mail as a string"));
        die();
    }
    if(!isset($_POST['mobile']) || !is_string($_POST['mobile']))
    {
        echo json_encode(array('error' => "Invalid Parameter! Expected mobile as a string"));
        die();
    }
    if(!isset($_POST['c']) || !is_string($_POST['c']))
    {
        echo json_encode(array('error' => "Invalid Parameter! Expected c as a string"));
        die();
    }
    if(!isset($_POST['street']) || !is_string($_POST['street']))
    {
        echo json_encode(array('error' => "Invalid Parameter! Expected street as a string"));
        die();
    }
    if(!isset($_POST['zip']) || !is_string($_POST['zip']))
    {
        echo json_encode(array('error' => "Invalid Parameter! Expected zip as a string"));
        die();
    }
    if(!isset($_POST['l']) || !is_string($_POST['l']))
    {
        echo json_encode(array('error' => "Invalid Parameter! Expected l as a string"));
        die();
    }
    if(!isset($_POST['st']) || !is_string($_POST['st']))
    {
        echo json_encode(array('error' => "Invalid Parameter! Expected st as a string"));
        die();
    }
    $request = new FlipsideTicketRequest($_POST['request_id'], TRUE);
    $request->populateFromPOSTData($_POST);
    $request->generateBarcode();
    echo json_encode($request);
}
else
{
    echo json_encode(array('error' => "Unrecognized Operation ".$_SERVER['REQUEST_METHOD']));
    die();
}
/* vim: set tabstop=4 shiftwidth=4 expandtab: */
?>
