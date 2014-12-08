<?php
//if(php_sapi_name() != "cli")
{
//    die('Not running from the CLI... die!');
}
error_reporting(E_ALL);
set_include_path(get_include_path().":.:..:/var/www/common");
require_once('class.TicketEmail.php');
$emails = TicketEmail::pop_queued_emails(500);
if($emails !== FALSE)
{
    for($i = 0; $i < count($emails); $i++)
    {
        $emails[$i]->send_HTML();
    }
}
?>
