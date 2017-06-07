<?php
if(!isset($_FILES['file']) && !isset($_REQUEST['data']))
{
    echo json_encode(array('error'=>'Invalid Parameter! No file uploaded.'));
    die();
}
require_once('Autoload.php');
$user = FlipSession::getUser();
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
$auth = AuthProvider::getInstance();
$group = $auth->getGroupByName('TicketTeam');
while($token !== false)
{
    $filter = new \Data\Filter('mail eq '.$token);
    $users = $auth->getUsersByFilter($filter);
    if($users === false || !isset($users[0]))
    {
        array_push($fails, $token);
    }
    if($group->addMember($users[0]->uid) === false)
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
