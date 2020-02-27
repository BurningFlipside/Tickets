<?php
require_once('class.FlipSession.php');
require_once('app/TicketAutoload.php');
require_once('../../class.SecurePage.php');
class TicketAdminPage extends \Http\FlipAdminPage
{
    use SecureWebPage;

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
        $this->addTemplateDir('../../templates', 'Secure');
        $this->addTemplateDir('../templates', 'Tickets');
        $this->secure_root = $this->getSecureRoot();
        $this->content['loginUrl'] = $this->secure_root.'api/v1/login';
	$this->addCSS('../css/tickets.css');
        $this->ticketSettings = \Tickets\DB\TicketSystemSettings::getInstance();
        $this->add_links();
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
        $this->addJS('js/admin.js');
        $this->addJS('../js/TicketSystem.js');
    }

    function add_links()
    {
        $this->content['header']['sidebar'] = array();
        if($this->user === false && $this->user === null)
        {
            return;
        }
        if(!$this->is_admin)
        {
            $this->content['header']['sidebar']['Data Entry'] = array('icon' => 'fa-pencil', 'url' => 'data.php');
            return;
        }
        $probs = '';
        $data_set = DataSetFactory::getDataSetByName('tickets');
        $data_table = $data_set['Problems'];
        $year = $this->ticketSettings['year'];
        $count = $data_table->count(new \Data\Filter('year eq '.$year));
        if($count > 0)
        {
            $probs = '<span class="badge  badge-secondary">'.$count.'</span>';
        }
        $charts_menu = array(
            'Request Statistics' => 'chart_requests.php',
            'Tickets Graphs' => 'chart_tickets.php',
            'Gate Graphs'    => 'chart_used.php',
            'Common Reports' => 'reports_common.php'
        );
        $request_menu = array(
            'All Requests'      => 'requests.php',
            'Requested Tickets' => 'request_tickets.php',
            'Problem Requests '.$probs => 'problems.php',
            'Invalid Requests' => 'requests_invalid.php',
            'Secondary Requests' => 'secondary.php'
        );
        $ticket_menu = array(
            'All Tickets'      => 'tickets.php',
            'Sold Tickets'     => 'tickets.php?sold=1',
            'Unsold Tickets'   => 'tickets.php?sold=0',
            'Used Tickets'     => 'tickets.php?used=1',
            'Generate Tickets' => 'ticket_gen.php'
        );
        $admin_menu = array(
            'Donation Types'     => 'donation_type.php',
            'Ticket Types'       => 'ticket_type.php',
            'Edit Variables'     => 'vars.php',
            'Edit Request PDF'   => 'pdf.php',
            'Edit Ticket Emails' => 'emails.php',
            'Edit Ticket PDF'    => 'ticket_pdf.php'
        );
        if($this->user->isInGroupNamed('AAR') || $this->user->isInGroupNamed('AFs'))
        {
            $ticket_menu['My Discretionary Tickets'] = 'tickets.php?discretionaryUser='.$this->user['mail'];
        }
        $this->content['header']['sidebar']['Dashboard'] = array('icon' => 'fa-tachometer-alt', 'url' => 'index.php');
        $this->content['header']['sidebar']['Reports'] = array('icon' => 'fa-chart-bar', 'menu' => $charts_menu);
        $this->content['header']['sidebar']['Requests'] = array('icon' => 'fa-file', 'menu' => $request_menu);
        $this->content['header']['sidebar']['Tickets'] = array('icon' => 'fa-ticket-alt', 'menu' => $ticket_menu);
        $this->content['header']['sidebar']['Data Entry'] = array('icon' => 'fa-pencil-alt', 'url' => 'data.php');
        $this->content['header']['sidebar']['Sales'] = array('icon' => 'fa-dollar-sign', 'url' => 'pos.php');
        $this->content['header']['sidebar']['Gate'] = array('icon' => 'fa-sign-in-alt', 'url' => 'gate.php');
        if($this->user->isInGroupNamed('AAR'))
        {
            $aar_menu = array(
                'Critical Volunteers' => 'critvols.php',
                'Discretionary Management' => 'discretionary.php',
                'Gate Control' => 'gateControl.php',
                'Pool Management' => 'pools.php',
                'Status Management' => 'status.php'
            );
            $this->content['header']['sidebar']['AAR'] = array('icon' => 'fa-fire', 'menu' => $aar_menu);
        }
        $this->content['header']['sidebar']['Admin'] = array('icon' => 'fa-cog', 'menu' => $admin_menu);
    }

    public function isAdmin()
    {
        return ($this->is_admin === true || $this->is_data === true);
    }
}
?>
