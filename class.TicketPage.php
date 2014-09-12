<?php
require_once('class.SecurePage.php');
require_once('class.FlipSession.php');
class TicketPage extends SecurePage
{
    function __construct($title)
    {
        parent::__construct($title);
        $this->add_tickets_css();
        $this->add_tickets_script();
        $this->add_sites();
        $this->add_links();
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
            'AAR, LLC'=>'http://www.burningflipside.com/about/aar',
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
