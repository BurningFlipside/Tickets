<?php
class TicketPlugin extends SecurePlugin
{
    function get_secure_menu_entries($page, $user)
    {
        $ret = array('Tickets'=>$page->secure_root.'tickets/index.php');
        if($user->isInGroupNamed('TicketAdmins'))
        {
            $ret['Ticket System Admin']=$page->secure_root.'tickets/_admin/index.php';
        }
        else if($user->isInGroupNamed('TicketTeam'))
        {
            $ret['Ticket System Data Entry']=$page->secure_root.'tickets/_admin/data.php';
        }
        return $ret;
    }

    function get_plugin_entry_point()
    {
        return array('name'=>'Ticket Registration/Transfer', 'link'=>'tickets/index.php');
    }
}
?>
