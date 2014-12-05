<?php
require_once('class.SecurePage.php');
require_once('class.FlipSession.php');
require_once('class.FlipsideTicketDB.php');
class TicketAdminPage extends FlipPage
{
    private $user;
    private $is_admin;
    private $is_data;

    function __construct($title)
    {
        $this->user = FlipSession::get_user(TRUE);
        if($this->user == FALSE)
        {
            $this->is_admin = FALSE;
            $this->is_data  = FALSE;
        }
        else
        {
            $this->is_admin = $this->user->isInGroupNamed("TicketAdmins");
            $this->is_data  = $this->user->isInGroupNamed("TicketTeam");
        }
        parent::__construct($title);
        $this->add_tickets_css();
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
        $this->add_js_from_src('/js/bootstrap-switch.min.js');
        $this->add_js_from_src('/js/bootstrap-formhelpers.min.js');
        $this->add_js_from_src('js/metisMenu.min.js');
        $this->add_js_from_src('js/admin.js');
    }

    function add_tickets_css()
    {
        $css_tag = $this->create_open_tag('link', array('rel'=>'stylesheet', 'href'=>'/css/bootstrap-formhelpers.min.css', 'type'=>'text/css'), true);
        $this->add_head_tag($css_tag);

        $css_tag = $this->create_open_tag('link', array('rel'=>'stylesheet', 'href'=>'/css/bootstrap-switch.min.css', 'type'=>'text/css'), true);
        $this->add_head_tag($css_tag);

        $css_tag = $this->create_open_tag('link', array('rel'=>'stylesheet', 'href'=>'/tickets/css/tickets.css', 'type'=>'text/css'), true);
        $this->add_head_tag($css_tag);

        $css_tag = $this->create_open_tag('link', array('rel'=>'stylesheet', 'href'=>'/tickets/_admin/css/admin.css', 'type'=>'text/css'), true);
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

    function add_header()
    {
        $sites = '';
        foreach($this->sites as $link => $site_name)
        {
            $sites .= '<li><a href="'.$site_name.'">'.$link.'</a></li>';
        }
        $aar = '';
        if($this->user != FALSE && $this->user->isInGroupNamed("AAR"))
        {
            $aar = '<li>
                        <a href="#"><span class="glyphicon glyphicon-fire"></span> AAR<span class="glyphicon arrow"></span></a>
                        <ul class="nav nav-second-level collapse">
                            <li><a href="critvols.php">Critical Volunteers</a></li>
                        </ul>
                    </li>';
        }
        $log = '';
        $probs = '';
        $db = new FlipsideTicketDB();
        if($db->getProblemRequestCount() > 0)
        {
            $probs = '<span class="badge">'.$db->getProblemRequestCount().'</span>';
        }
        if(!FlipSession::is_logged_in())
        {
            $log = '<a href="https://profiles.burningflipside.com/login.php?return='.$this->current_url().'"><span class="glyphicon glyphicon-log-in"></span></a>';
        }
        else
        {
            $log = '<a href="https://profiles.burningflipside.com/logout.php"><span class="glyphicon glyphicon-log-out"></span></a>';
        }
        $this->body = '<div id="wrapper">
                  <nav class="navbar navbar-default navbar-static-top" role=navigation" style="margin-bottom: 0">
                      <div class="navbar-header">
                          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target=".navbar-collapse">
                              <span class="sr-only">Toggle Navigation</span>
                              <span class="icon-bar"></span>
                              <span class="icon-bar"></span>
                              <span class="icon-bar"></span>
                          </button>
                          <a class="navbar-brand" href="index.php">Tickets</a>
                      </div>
                      <ul class="nav navbar-top-links navbar-right">
                           <a href="/tickets/">
                              <span class="glyphicon glyphicon-home"></span>
                           </a>
                          &nbsp;&nbsp;
                          '.$log.'
                          <li class="dropdown">
                              <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                                  <span class="glyphicon glyphicon-link"></span>
                                  <b class="caret"></b>
                              </a>
                              <ul class="dropdown-menu dropdown-sites">
                                  '.$sites.'
                              </ul>
                          </li>
                      </ul>
                      <div class="navbar-default sidebar" role="navigation">
                          <div class="sidebar-nav navbar-collapse" style="height: 1px;">
                              <ul class="nav" id="side-menu">
                                  <li>
                                      <a href="index.php"><span class="glyphicon glyphicon-dashboard"></span> Dashboard</a>
                                  </li>
                                  <li>
                                      <a href="#"><span class="glyphicon glyphicon-stats"></span> Charts<span class="glyphicon arrow"></span></a>
                                      <ul class="nav nav-second-level collapse">
                                          <li><a href="chart_requests.php">Request Graphs</a></li>
                                          <li><a href="chart_tickets.php">Tickets Graphs</a></li>
                                          <li><a href="chart_used.php">Gate Graphs</a></li>
                                      </ul>
                                  </li>
                                  <li>
                                      <a href="#"><span class="glyphicon glyphicon-file"></span> Requests<span class="glyphicon arrow"></span></a>
                                      <ul class="nav nav-second-level collapse">
                                          <li><a href="requests.php">All Requests</a></li>
                                          <li><a href="request_tickets.php">Requested Tickets</a></li>
                                          <li><a href="problems.php">Problem Requests '.$probs.'</a></li>
                                      </ul>
                                  </li>
                                  <li>
                                      <a href="#"><span class="glyphicon glyphicon-tag"></span> Tickets<span class="glyphicon arrow"></span></a>
                                      <ul class="nav nav-second-level collapse">
                                          <li><a href="tickets.php">All Tickets</a></li>
                                          <li><a href="sold_tickets.php">Sold Tickets</a></li>
                                          <li><a href="ticket_gen.php">Generate Tickets</a></li>
                                      </ul>
                                  </li>
                                  <li>
                                      <a href="data.php"><span class="glyphicon glyphicon-pencil"></span> Data Entry</a>
                                  </li>
                                  <li>
                                      <a href="pos.php"><span class="glyphicon glyphicon-usd"></span> Sales</a>
                                  </li>
                                  <li>
                                      <a href="gate.php"><span class="glyphicon glyphicon-log-in"></span> Gate</a>
                                  </li>
                                  '.$aar.'
                                  <li>
                                      <a href="#"><span class="glyphicon glyphicon-tower"></span> Admin<span class="glyphicon arrow"></span></a>
                                      <ul class="nav nav-second-level collapse">
                                          <li><a href="donation_type.php">Donation Types</a></li>
                                          <li><a href="ticket_type.php">Ticket Types</a></li>
                                          <li><a href="vars.php">Edit Variables</a></li>
                                          <li><a href="pdf.php">Edit Request PDF</a></li>
                                          <li><a href="emails.php">Edit Ticket Emails</a></li>
                                          <li><a href="ticket_pdf.php">Edit Ticket PDF</a></li>
                                          <li><a href="users.php">Data Entry Users</a></li>
                                      </ul>
                                  </li>
                              </ul>
                          </div>
                      </div>
                  </nav>
                  <div id="page-wrapper" style="min-height: 538px;">'.$this->body.'</div></div>';
    }

    function current_url()
    {
        return 'http'.(isset($_SERVER['HTTPS'])?'s':'').'://'."{$_SERVER['HTTP_HOST']}/{$_SERVER['REQUEST_URI']}";
    }

    function print_page()
    {
        if($this->user == FALSE)
        {
            $this->body = '
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">You must <a href="https://profiles.burningflipside.com/login.php?return='.$this->current_url().'">log in <span class="glyphicon glyphicon-log-in"></span></a> to access the Burning Flipside Ticket system!</h1>
            </div>
        </div>';
        }
        else if($this->is_admin == FALSE && $this->is_data == FALSE)
        {
            $this->body = '
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">You must log in to access the Burning Flipside Ticket system!</h1>
            </div>
        </div>';
        }
        parent::print_page(true);
    }
}
?>
