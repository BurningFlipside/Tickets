<?php
if(!isset($_FILES['file']) && !isset($_REQUEST['data']))
{
    echo json_encode(array('error'=>'Invalid Parameter! No file uploaded.'));
    die();
}
require_once('class.FlipSession.php');
require_once('class.FlipsideLDAPServer.php');
$user = FlipSession::get_user(TRUE);
if($user == FALSE)
{
    echo json_encode(array('error' => "Not Logged In!"));
    die();
}
$is_admin = $user->isInGroupNamed("TicketAdmins");
if(!$is_admin)
{
    echo json_encode(array('error' => "Not Ticket Admin!"));
    die();
}
$file_content = '';
if(isset($_FILES['file']))
{
    $file_content = file_get_contents($_FILES['file']['tmp_name']);
}
else
{
    $file_content = $_REQUEST['data'];
}
$token = strtok($file_content, "\r\n,");
$success = array();
$fails = array();
$server = new FlipsideLDAPServer();
$groups = $server->getGroups("(cn=TicketTeam)");
if($groups == FALSE || !isset($groups[0]))
{
    return json_encode(array('error' => "Internal Error! Unable to locate TicketTeam Group!"));
}
while($token !== false)
{
    if($groups[0]->addMemberByEmail($token) == FALSE)
    {
        array_push($fails, $token);
    }
    else
    {
        array_push($success, $token);
    }
    $token = strtok("\r\n,");
}
echo json_encode(array('success'=>$success, 'fails'=>$fails));
?>
