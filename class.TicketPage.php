<?php
require_once('class.SecurePage.php');
require_once('class.FlipSession.php');
require_once('class.FlipsideTicketDB.php');
class TicketPage extends SecurePage
{
    private $user;
    private $is_admin;
    private $is_data;

    function __construct($title)
    {
        $this->user = FlipSession::get_user(TRUE);
        $this->is_admin = $this->user->isInGroupNamed("TicketAdmins");
        $this->is_data  = $this->user->isInGroupNamed("TicketTeam");
        parent::__construct($title);
        $this->add_tickets_css();
        $this->add_tickets_script();
        $this->add_sites();
        $this->add_links();
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

    function add_tickets_css()
    {
        $css_tag = $this->create_open_tag('link', array('rel'=>'stylesheet', 'href'=>'/tickets/css/tickets.css', 'type'=>'text/css'), true);
        $this->add_head_tag($css_tag);
    }

    function add_sites()
    {
        $this->add_site('Profiles', 'http://profiles.burningflipside.com');
        $this->add_site('WWW', 'http://www.burningflipside.com');
        $this->add_site('Pyropedia', 'http://wiki.burningflipside.com');
        $this->add_site('Secure', 'https://secure.burningflipside.com');
    }

    function add_links()
    {
        if(!FlipSession::is_logged_in())
        {
            $this->add_link('Login', 'http://profiles.burningflipside.com/login.php');
        }
        else
        {
            if($this->is_admin)
            {
                $admin_menu = array(
                    'Donation Types'=>'https://secure.burningflipside.com/tickets/_admin/donation_type.php',
                    'Ticket Types'=>'https://secure.burningflipside.com/tickets/_admin/ticket_type.php',
                    'Variable Edit'=>'https://secure.burningflipside.com/tickets/_admin/vars.php'
                );
                $this->add_link('Admin', 'https://secure.burningflipside.com/tickets/_admin/', $admin_menu);
            }
            if($this->is_data)
            {
                $this->add_link('Data Entry', 'https://secure.burningflipside.com/tickets/_admin/data.php');
            }
            $secure_menu = array(
                'Ticket Registration'=>'/tickets/index.php',
                'Ticket Transfer'=>'/tickets/transfer.php',
                'Theme Camp Registration'=>'/theme_camp/registration.php',
                'Art Project Registration'=>'/art/registration.php',
                'Event Registration'=>'/event/index.php'
            );
            $this->add_link('Secure', 'https://secure.burningflipside.com/', $secure_menu);
            $this->add_link('Logout', 'http://profiles.burningflipside.com/logout.php');
        }
        $about_menu = array(
            'Burning Flipside'=>'http://www.burningflipside.com/about/event',
            'AAR, LLC'=>'http://www.burningflipside.com/LLC',
            'Privacy Policy'=>'http://www.burningflipside.com/about/privacy'
        );
        $this->add_link('About', 'http://www.burningflipside.com/about', $about_menu);
    }

    function add_tickets_script()
    {
    }

    function current_url()
    {
        return 'http'.(isset($_SERVER['HTTPS'])?'s':'').'://'."{$_SERVER['HTTP_HOST']}/{$_SERVER['REQUEST_URI']}";
    }
}
?>
