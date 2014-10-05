<?php
require_once("class.FlipJax.php");
require_once("class.FlipsideTicketDB.php");
class VariableAjax extends FlipJaxSecure
{
    function get($params)
    {
        if(!$this->is_logged_in())
        {
            return array('err_code' => self::ACCESS_DENIED, 'reason' => "Not Logged In!");
        }
        if(!$this->user_in_group("TicketAdmins"))
        {
            return array('err_code' => self::ACCESS_DENIED, 'reason' => "User must be a member of TicketAdmins!");
        }
        $db = new FlipsideTicketDB();
        $vars = $db->getAllVars();
        if($vars === FALSE)
        {
            return array('err_code' => self::INTERNAL_ERROR, 'reason' => "Failed to obtain variables!");
        }
        else
        {
            return array('vars' => $vars);
        }
    }

    function post($params)
    {
        if(!$this->is_logged_in())
        {
            return array('err_code' => self::ACCESS_DENIED, 'reason' => "Not Logged In!");
        }
        if(!$this->user_in_group("TicketAdmins"))
        {
            return array('err_code' => self::ACCESS_DENIED, 'reason' => "User must be a member of TicketAdmins!");
        }
        if(isset($params['delete']))
        {
            $db = new FlipsideTicketDB();
            if($db->deleteVariable($_POST['delete']) === FALSE)
            {
                return array('err_code' => self::INTERNAL_ERROR, 'reason' => "Failed to delete variable!");
            }
            else
            {
                return self::SUCCESS;
            }
        }
        else
        {
            $res = $this->validate_params($params, array('name'=>'string', 'value'=>'string'));
            if($res == self::SUCCESS)
            {
                if($db->setVariable($_POST['name'], $_POST['value']) === FALSE)
                {
                    $res = array('err_code' => self::INTERNAL_ERROR, 'reason' => "Failed to set variable!");
                }
                else
                {
                    $res = self::SUCCESS;
                }
            }
            return $res;
        }
    }
}

$ajax = new VariableAjax();
$ajax->run();
/* vim: set tabstop=4 shiftwidth=4 expandtab: */
?>
