<?php
require_once("class.FlipsideTicketDB.php");
require_once("class.FlipJax.php");
class StatusAjax extends FlipJaxSecure
{
    function get($params)
    {
        if(!$this->is_logged_in())
        {
            return array('err_code' => self::ACCESS_DENIED, 'reason' => "Not Logged In!");
        }
        if(!$this->user_in_group("TicketAdmins") && !$this->user_in_group("TicketTeam"))
        {
            return array('err_code' => self::ACCESS_DENIED, 'reason' => "User must be a member of TicketAdmins or TicketTeam!");
        }

        $db = new FlipsideTicketDB();
        $data = $db->select('tblRequestStatus');
        $res = array('data' => $data);
        return $res;
    }
}

$ajax = new StatusAjax();
$ajax->run();
/* vim: set tabstop=4 shiftwidth=4 expandtab: */
?>
