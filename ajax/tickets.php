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
$is_data  = $user->isInGroupNamed("TicketTeam");

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
    if(isset($_GET['sold']))
    {
        if(!$is_admin && !$is_data)
        {
            echo json_encode(array('error' => "Access Denied! User must be a member of TicketAdmins or TicketTeam!"));
        }
        else
        {
            $db = new FlipsideTicketDB();
            $sold = $db->getTicketSoldCount();
            $unsold = $db->getTicketUnsoldCount();
            if($sold === FALSE)
            {
                echo json_encode(array('error' => "Internal Error! Failed to obtain sold ticket count!"));
            }
            else if($unsold === FALSE)
            {
                echo json_encode(array('error' => "Internal Error! Failed to obtain unsold ticket count!"));
            }
            else
            {
                echo json_encode(array('success' => 0, 'sold' => $sold, 'unsold' => $unsold));
            }
        }
    }
    else
    {
        $data = array();
        echo json_encode(array('data'=>$data));
    }
}
else
{
    echo json_encode(array('error' => "Unrecognized Operation ".$_SERVER['REQUEST_METHOD']));
    die();
}
/* vim: set tabstop=4 shiftwidth=4 expandtab: */
?>
