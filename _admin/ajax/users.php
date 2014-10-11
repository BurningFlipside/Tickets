<?php
require_once("class.FlipsideLDAPServer.php");
require_once("class.FlipJax.php");
class UserAjax extends FlipJaxSecure
{
    function post_pdf($source)
    {
        $pdf = new FlipsideTicketRequestPDF(FALSE, $source);
        $file_name = $pdf->generatePDF();
        if($file_name == FALSE)
        {
            return array('err_code' => self::INTERNAL_ERROR, 'reason' => "Failed to generate PDF!");
        }
        else
        {
            $file_name = substr($file_name, strpos($file_name, 'tmp/'));
            return array('pdf' => '../'.$file_name);
        }
    }

    function post_save($source)
    {
        FlipsideTicketDB::set_long_text('pdf_source', $source);
        return self::SUCCESS;
    }

    function post($params)
    {
        if(!$this->is_logged_in())
        {
            return array('err_code' => self::ACCESS_DENIED, 'reason' => "Not Logged In!");
        }
        if(isset($params['preview']))
        {
            return $this->post_pdf($params['preview']);
        }
        if(isset($params['save']))
        {
            return $this->post_save($params['save']);
        }
        else
        {
            return self::SUCCESS;
        }
    }

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
        $server = new FlipsideLDAPServer();
        $groups = $server->getGroups("(cn=TicketTeam)");
        if($groups == FALSE || !isset($groups[0]))
        {
            return array('err_code' => self::INTERNAL_ERROR, 'reason' => "Unable to locate TicketTeam Group!");
        }
        $members = $groups[0]->getMembers();
        $members = array_unique($members);
        $res = array();
        foreach($members as $key => $member)
        {
            $user = $server->getUserByDN($member);
            if($user != FALSE)
            {
                array_push($res, array('name' => $user->givenName[0].' '.$user->sn[0],
                                       'email'=>$user->mail[0],
                                       'uid'=>$user->uid[0],
                                       'admin'=>$user->isInGroupNamed("TicketAdmins")));
            }
        }
        return array('data'=>$res);
    }
}

$ajax = new UserAjax();
$ajax->run();
/* vim: set tabstop=4 shiftwidth=4 expandtab: */
?>
