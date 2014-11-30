<?php
require_once("class.FlipsideTicketDB.php");
require_once("class.FlipJax.php");
class EmailAjax extends FlipJaxSecure
{
    function post_save($type, $source)
    {
        FlipsideTicketDB::set_long_text($type, $source);
        return self::SUCCESS;
    }

    function post($params)
    {
        if(!$this->is_logged_in())
        {
            return array('err_code' => self::ACCESS_DENIED, 'reason' => "Not Logged In!");
        }
        if(isset($params['save']))
        {
            return $this->post_save($params['type'], $params['save']);
        }
        else
        {
            return self::SUCCESS;
        }
    }
}

$ajax = new EmailAjax();
$ajax->run();
/* vim: set tabstop=4 shiftwidth=4 expandtab: */
?>
