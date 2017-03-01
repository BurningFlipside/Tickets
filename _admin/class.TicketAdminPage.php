<?php
require_once('class.FlipSession.php');
require_once('app/TicketAutoload.php');
class TicketAdminPage extends FlipAdminPage
{
    private $is_data;
    public  $ticketSettings;

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
        $this->ticketSettings = \Tickets\DB\TicketSystemSettings::getInstance();
        if($this->ticketSettings->isTestMode())
        {
             if($this->is_admin)
             {
                 $this->addNotification('The ticket system is operating in test mode. Any entries made will be deleted before ticketing starts. To change modes turn off Test Mode on <a href="/tickets/_admin/vars.php" class="alert-link">this page</a>.',
                                        self::NOTIFICATION_WARNING);
             }
             else
             {
                 $this->addNotification('The ticket system is operating in test mode. Any entries made will be deleted before ticketing starts.',
                                        self::NOTIFICATION_WARNING);
             }
        }
        $this->addWellKnownJS(JS_METISMENU);
        $this->addJSByURI('js/admin.js');
    }

    function add_tickets_css()
    {
	$this->addCSSByURI('../css/tickets.css');
    }

    function add_links()
    {
        if($this->user === false && $this->user === null)
        {
            return;
        }
        if(!$this->is_admin)
        {
            $this->addLink('<span class="fa fa-pencil"></span> Data Entry', 'data.php');
            return;
        }
        $probs = '';
        $data_set = DataSetFactory::getDataSetByName('tickets');
        $data_table = $data_set['Problems'];
        $year = $this->ticketSettings['year'];
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
            'Problem Requests '.$probs => 'problems.php',
            'Invalid Requests' => 'requests_invalid.php'
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
        $this->addLink('<span class="fa fa-tachometer"></span> Dashboard', 'index.php');
        $this->addLink('<span class="fa fa-bar-chart"></span> Charts', '#', $charts_menu);
        $this->addLink('<span class="fa fa-file"></span> Requests', '#', $request_menu);
        $this->addLink('<span class="fa fa-ticket"></span> Tickets', '#', $ticket_menu);
        $this->addLink('<span class="fa fa-pencil"></span> Data Entry', 'data.php');
        $this->addLink('<span class="fa fa-usd"></span> Sales', 'pos.php');
        $this->addLink('<span class="fa fa-sign-in"></span> Gate', 'gate.php');
        if($this->user->isInGroupNamed('AAR'))
        {
            $aar_menu = array(
                'Critical Volunteers' => 'critvols.php',
                'Discretionary Management' => 'discretionary.php',
                'Gate Control' => 'gateControl.php',
                'Pool Management' => 'pools.php'
            );
            $this->addLink('<span class="fa fa-fire"></span> AAR', '#', $aar_menu);
        }
        $this->addLink('<span class="fa fa-cog"></span> Admin', '#', $admin_menu);
    }

    public function isAdmin()
    {
        return ($this->is_admin === true || $this->is_data === true);
    }
}
?>
