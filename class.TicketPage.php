<?php
require_once('class.SecurePage.php');
require_once('class.FlipSession.php');
require_once('class.FlipsideTicketDB.php');
class TicketPage extends SecurePage
{
    private $user;
    private $is_admin;
    private $is_data;
    public  $ticket_root;

    function __construct($title)
    {
        $this->user = FlipSession::get_user(TRUE);
        if($this->user != FALSE)
        {
            $this->is_admin = $this->user->isInGroupNamed("TicketAdmins");
            $this->is_data  = $this->user->isInGroupNamed("TicketTeam");
        }
        else
        {
            $this->is_admin = FALSE;
            $this->is_data  = FALSE;
        }
        parent::__construct($title);
        $root = $_SERVER['DOCUMENT_ROOT'];
        $script_dir = dirname(__FILE__);
        $this->ticket_root = substr($script_dir, strlen($root));
        $this->add_tickets_css($this->ticket_root);
        if($this->is_admin)
        {
            $this->add_link('Admin', $this->ticket_root.'/_admin/');
        }
        if($this->is_data)
        {
            $this->add_link('Data Entry', $this->ticket_root.'/_admin/data.php');
        }
        if(FlipsideTicketDB::getTestMode())
        {
             if($this->is_admin)
             {
                 $this->add_notification('The ticket system is operating in test mode. Any entries made will be deleted before ticketing starts. To change modes turn off Test Mode on <a href="/tickets/_admin/vars.php" class="alert-link">this page</a>.',
                                         self::NOTIFICATION_WARNING);
             }
             else
             {
                 $this->add_notification('The ticket system is operating in test mode. Any entries made will be deleted before ticketing starts.', 
                                         self::NOTIFICATION_WARNING);
             }
        }
    }

    function add_tickets_css($root)
    {
        $this->add_css_from_src($root.'/css/tickets.css');
    }
}
?>
