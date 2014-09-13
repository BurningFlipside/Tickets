<?php
require_once("static.requests2014.php");
if(strtoupper($_SERVER['REQUEST_METHOD']) == 'GET')
{
    if(!isset($_GET['email']) || !is_string($_GET['email']))
    {
        echo json_encode(array('error' => "Invalid Parameter! Expected email as a string"));
        die();
    }
    for($i = 0; $i < $request; $i++)
    {
        if($request[$i]['email'] == $_GET['email'])
        {
            echo json_encode(array('success'=>0, 'request_id'=>$request[$i]['request_id']));
            die();
        }
    }
    echo json_encode(array('error'=>'Invalid Parameter! No such email!'));
}
else
{
    echo json_encode(array('error' => "Unrecognized Operation ".$_SERVER['REQUEST_METHOD']));
    die();
}
/* vim: set tabstop=4 shiftwidth=4 expandtab: */
?>
