<?php
if(!isset($_FILES['file']) && !isset($_REQUEST['auto']))
{
    echo json_encode(array('error'=>'Invalid Parameter! No file uploaded.'));
    die();
}
require_once('class.FlipSession.php');
require_once('class.FlipsideTicketRequest.php');
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
    require_once('class.FlipsideLDAPServer.php');
    $server = new FlipsideLDAPServer();

    $groups = $server->getGroups("(cn=Leads)");
    if($groups == FALSE || !isset($groups[0]))
    {
        echo json_encode(array('error' => "Unable to locate Leads Group!"));
        die();
    }
    $members = $groups[0]->getMembers();
    $members = array_unique($members);
    $res = array();
    foreach($members as $key => $member)
    {
        $user = $server->getUserByDN($member);
        $file_content .= $user->mail[0].',';
    } 
}
$token = strtok($file_content, "\r\n,");
$success = array();
$fails = array();
$db = new FlipsideTicketDB();
while($token !== false)
{
    $request = FlipsideTicketRequest::find_request($token);
    if($request === FALSE)
    {
        array_push($fails, $token);
    }
    else
    {
        $request[0]->crit_vol = 1; 
        $request[0]->replace_in_db($db);
        array_push($success, array('token' => $token, 'name'=>$request[0]->givenName.' '.$request[0]->sn, 'tickets'=>$request[0]->tickets));
    }
    $token = strtok("\r\n,");
}
echo json_encode(array('success'=>$success, 'fails'=>$fails));
?>
