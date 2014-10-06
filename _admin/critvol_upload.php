<?php
if(!isset($_FILES['file']))
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
$file_content = file_get_contents($_FILES['file']['tmp_name']);
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
        array_push($success, array('token' => $token, 'name'=>$request[0]->givenName.' '.$request[0]->sn));
    }
    $token = strtok("\r\n,");
}
echo json_encode(array('success'=>$success, 'fails'=>$fails));
?>
