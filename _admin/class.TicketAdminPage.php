<?php
require_once('class.SecurePage.php');
require_once('class.FlipSession.php');
require_once('app/TicketAutoload.php');
class TicketAdminPage extends FlipAdminPage
{
    private $is_data;
    public  $settings;

    function __construct($title)
    {
        parent::__construct($title, 'TicketAdmins');
        if($this->user !== false && $this->user !== null && $this->is_admin === false)
        {
            $this->is_data  = $this->user->isInGroupNamed('TicketTeam');
            $this->is_admin = $this->is_data;
        }
        $this->add_tickets_css();
        $this->add_links();
        $this->settings = \Tickets\DB\TicketSystemSettings::getInstance();
        if($this->settings->isTestMode())
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
        $this->add_js(JS_METISMENU);
        $this->add_js_from_src('js/admin.js');
    }

    function add_tickets_css()
    {
	$this->add_css_from_src('../css/tickets.css');
    }

    function add_links()
    {
        if(!$this->is_admin)
        {
            $this->add_link('<span class="glyphicon glyphicon-pencil"></span> Data Entry', 'data.php');
            return;
        }
        $probs = '';
        $data_set = DataSetFactory::get_data_set('tickets');
        $data_table = $data_set['Problems'];
        $year = $this->settings['year'];
        $count = $data_table->count(new \Data\Filter('year eq '.$year));
        if($count > 0)
        {
            $probs = '<span class="badge">'.$count.'</span>';
        }
        $charts_menu = array(
            'Request Statistics' => 'chart_requests.php',
            'Tickets Graphs' => 'chart_tickets.php',
            'Gate Graphs'    => 'chart_used.php'
        );
        $request_menu = array(
            'All Requests'      => 'requests.php',
            'Requested Tickets' => 'request_tickets.php',
            'Problem Request '.$probs => 'problems.php'
        );
        $ticket_menu = array(
            'All Tickets'      => 'tickets.php',
            'Sold Tickets'     => 'sold_tickets.php',
            'Unsold Tickets'   => 'unsold_tickets.php',
            'Generate Tickets' => 'ticket_gen.php'
        );
        $admin_menu = array(
            'Donation Types'     => 'donation_type.php',
            'Ticket Types'       => 'ticket_type.php',
            'Edit Variables'     => 'vars.php',
            'Edit Request PDF'   => 'pdf.php',
            'Edit Ticket Emails' => 'emails.php',
            'Edit Ticket PDF'    => 'ticket_pdf.php',
            'Data Entry Users'   => 'users.php'
        );
        $this->add_link('<span class="glyphicon glyphicon-dashboard"></span> Dashboard', 'index.php');
        $this->add_link('<span class="glyphicon glyphicon-stats"></span> Charts', '#', $charts_menu);
        $this->add_link('<span class="glyphicon glyphicon-file"></span> Requests', '#', $request_menu);
        $this->add_link('<span class="glyphicon glyphicon-tag"></span> Tickets', '#', $ticket_menu);
        $this->add_link('<span class="glyphicon glyphicon-pencil"></span> Data Entry', 'data.php');
        $this->add_link('<span class="glyphicon glyphicon-usd"></span> Sales', 'pos.php');
        $this->add_link('<span class="glyphicon glyphicon-log-in"></span> Gate', 'gate.php');
        if($this->user->isInGroupNamed('AAR'))
        {
            $aar_menu = array(
                'Critical Volunteers' => 'critvols.php'
            );
            $this->add_link('<span class="glyphicon glyphicon-fire"></span> AAR', '#', $aar_menu);
        }
        $this->add_link('<span class="glyphicon glyphicon-tower"></span> Admin', '#', $admin_menu);
    }

    function print_page($header = true)
    {
        if($this->user === false)
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
        parent::print_page($header);
    }
}
?>
