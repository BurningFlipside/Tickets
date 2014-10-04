<?php
require_once('class.FlipsideTicketDB.php');
require_once('class.FlipsideTicketRequestTicket.php');
require_once('class.FlipsideDonation.php');
require_once('mpdf/mpdf.php');
require_once('class.FlipsideTicketRequestEmail.php');
class FlipsideTicketRequest extends FlipsideDBObject
{
    protected $_tbl_name = 'tblTicketRequest';
    protected $_sql_ignore = array('modifiedOn');

    public $request_id;
    public $year;
    public $givenName;
    public $sn;
    public $mail;
    public $mobile;
    public $c;
    public $street;
    public $zip;
    public $l;
    public $st;
    public $modifiedBy;
    public $modifiedByIP;
    public $modifiedOn;
    public $tickets;
    public $donations;
    public $total_due;
    public $total_received;
    public $crit_vol;
    public $protected;
    public $comments;
    public $bucket;
    public $revisions;

    static function getRequestId($user)
    {
    	$db = new FlipsideTicketDB();
        $id = $db->getRequestIdForUser($uesr);
        if($id != FALSE)
        {
            return $id;
        }
        $id = FlipsideTicketRequest::getOldRequestId($user->mail[0]);
        if($id != FALSE)
        {
            return $id;
        }
        return $db->getNewRequestId();
    }

    static function getOldRequestId($email)
    {
        include('static.requests2014.php');
        for($i = 0; $i < count($request); $i++)
        {
            if($request[$i]['email'] == $email)
            {
                return $request[$i]['request_id'];
            }
        }
        return FALSE;
    }

    static function populate_children($db, &$type)
    {
        $type->tickets = FlipsideTicketRequestTicket::select_from_db_multi_conditions($db, array('request_id'=>'='.$type->request_id, 'year'=>'='.$type->year));
        if($type->tickets != FALSE && !is_array($type->tickets))
        {
            $type->tickets = array($type->tickets);
        }
        $type->donations = FlipsideDonation::select_from_db_multi_conditions($db, array('request_id'=>'='.$type->request_id, 'year'=>'='.$type->year));
        if($type->donations != FALSE && !is_array($type->donations))
        {
            $type->donations = array($type->donations);
        }
        if($type->revisions != FALSE)
        {
            $type->parse_revisions($type->revisions);
        }
    }

    static function select_from_db($db, $col, $value)
    {
        $type = parent::select_from_db($db, $col, $value);
        if($type == FALSE)
        {
            return FALSE;
        }
        if(is_array($type))
        {
            for($i = 0; $i < count($type); $i++)
            {
                self::populate_children($db, $type[$i]);
            }
        }
        else
        {
            self::populate_children($db, $type);
        }
        return $type;
    }

    static function select_from_db_multi_conditions($db, $conds, $conj='AND')
    {
        $type = parent::select_from_db_multi_conditions($db, $conds, $conj);
        if(is_array($type))
        {
            for($i = 0; $i < count($type); $i++)
            {
                self::populate_children($db, $type[$i]);
            }
        }
        else
        {
            self::populate_children($db, $type);
        }
        return $type;
    }

    static function getAll($year)
    {
        $db = new FlipsideTicketDB();
        $type = self::select_from_db($db, 'year', $year);
        if($type == FALSE)
        {
            return FALSE;
        }
        if(!is_array($type))
        {
            $type = array($type);
        }
        return $type;
    }

    static function searchForRequests($type, $value)
    {
        $cond = array();
        switch($type)
        {
            default:
            case '*':
                $cond['request_id'] = '=\''.$value.'\'';
                $cond['givenName'] = ' LIKE \''.$value.'\'';
                $cond['mail'] = ' LIKE \''.$value.'\'';
                $cond['sn'] = ' LIKE \''.$value.'\'';
                break;
            case 'request_id':
                $cond['request_id'] = '=\''.$value.'\'';
                break;
            case 'email':
                $cond['mail'] = ' LIKE \''.$value.'\'';
                break;
            case 'first':
                $cond['givenName'] = ' LIKE \''.$value.'\'';
                break;
            case 'last':
                $cond['sn'] = ' LIKE \''.$value.'\'';
                break;
        }
        $db = new FlipsideTicketDB();
        $type = self::select_from_db_multi_conditions($db, $cond, 'OR');
        return $type;
    }

    static function test_request()
    {
         $type = new static();
         $type->request_id   = '000000';
         $type->year         = FlipsideTicketDB::get_var('year');
         $type->givenName    = 'Test';
         $type->sn           = 'User';
         $type->mail         = 'test@test.org';
         $type->mobile       = '+1 (234) 567-8901';
         $type->c            = 'US';
         $type->street       = '123 Fake Street';
         $type->zip          = '12345';
         $type->l            = 'Fake Town';
         $type->st           = 'TX';
         $type->modifiedBy   = 'noone';
         $type->modifiedByIP = '127.0.0.1';
         $type->modifiedOn   = 'today';
         $type->tickets      = array();
         $type->donations    = array();

         array_push($type->tickets, FlipsideTicketRequestTicket::test_ticket($type->request_id, $type->year, 1));
         array_push($type->tickets, FlipsideTicketRequestTicket::test_ticket($type->request_id, $type->year, 2));
         array_push($type->tickets, FlipsideTicketRequestTicket::test_ticket($type->request_id, $type->year, 3));
         array_push($type->tickets, FlipsideTicketRequestTicket::test_ticket($type->request_id, $type->year, 4));

         array_push($type->donations, FlipsideDonation::test_donation($type->request_id, $type->year, 1));

         $type->total_due    = $type->getTotalAmount();

         return $type;
    }

    function __construct($request_id='', $new = TRUE, $year = '')
    {
        $this->request_id = $request_id;
        $db = new FlipsideTicketDB();
        if($year == '')
        {
            $this->year = $db->getVariable('year');
        }
        else
        {
            $this->year = $year;
        }
        if($new)
        {
        }
        else
        {
            $this->populateFromDB($db);
        }
    }

    function populateFromDB($db)
    {
        $type = self::select_from_db_multi_conditions($db, array('request_id'=>'='.$this->request_id, 'year'=>'='.$this->year));
        foreach(get_object_vars($type) as $key => $value)
        {
            $this->$key = $value;
        }
    }

    function populateFromPOSTData($data)
    {
        $this->givenName = $data['givenName'];
        $this->sn        = $data['sn'];
        $this->mail      = $data['mail'];
        $this->mobile    = $data['mobile'];
        $this->c         = $data['c'];
        $this->street    = $data['street'];
        $this->zip       = $data['zip'];
        $this->l         = $data['l'];
        $this->st        = $data['st'];
        $this->populateTicketDataFromPOSTData($data);
        $this->populateDonationDataFromPOSTData($data);
        $this->total_due = $this->getTotalAmount();
        $old = new FlipsideTicketRequest($this->request_id, FALSE, $this->year);
        if($old == FALSE)
        {
            $this->total_received = 0;
            $this->crit_vol       = false;
            $this->protected      = false;
        }
        else
        {
            $this->total_received = $old->total_received;
            $this->crit_vol       = $old->crit_vol;
            $this->protected      = $old->protected;
        }
    }

    function populateTicketDataFromPOSTData($data)
    {
        $ticket_data = array();
        foreach($data as $key => $value)
        {
            $exp_key = explode('_', $key);
            if($exp_key[0] == 'ticket')
            {
                $ticket_data[$exp_key[2]][$exp_key[1]] = $value;
            }
        }
        $this->populateTicketData($ticket_data);
    }

    function populateDonationDataFromPOSTData($data)
    {
        $donation_data = array();
        foreach($data as $key => $value)
        {
            $exp_key = explode('_', $key);
            if($exp_key[0] == 'donation')
            {
                $donation_data[$exp_key[2]][$exp_key[1]] = $value;
            }
        }
        $this->populateDonationData($donation_data);
    }

    function populateTicketData($data)
    {
        $this->tickets = array();
        for($i = 0; $i < count($data); $i++)
        {
            $data[$i]['request_id'] = $this->request_id;
            $data[$i]['year'] = $this->year;
            array_push($this->tickets, new FlipsideTicketRequestTicket($data[$i]));
        }
    }

    function populateDonationData($data)
    {
        $this->donations = array();
        foreach($data as $key => $value)
        {
            $data[$key]['request_id'] = $this->request_id;
            $data[$key]['year'] = $this->year;
            array_push($this->donations, new FlipsideDonation($key, $data[$key]));
        }
    }

    function hasMinors()
    {
        foreach($this->tickets as $ticket)
        {
            if($ticket->isMinor())
            {
                return TRUE;
            }
        }
        return FALSE;
    }

    function getTotalAmount()
    {
        $total = 0;
        foreach($this->tickets as $ticket)
        {
            $total += $ticket->getCost();
        }
        foreach($this->donations as $donation)
        {
            $total += $donation->amount;
        }
        return $total;
    }

    function getMailingAddress($linebreak)
    {
        $address  = $this->givenName.' '.$this->sn.$linebreak;
        $address .= $this->street.$linebreak;
        $address .= $this->l.', '.$this->st.' '.$this->zip.$linebreak;
        if($this->c != 'US')
        {
            $address .= $this->c.$linebreak;
        }
        return $address;
    }

    function getTicketsAsTable()
    {
        $table  = '<table style="margin-left:auto; margin-right:auto; width:100%;">';
        $table .= '<tr><td></td><th>First Name</th><th>Last Name</th><th>Ticket Type</th><th>Cost</th></tr>';
        for($i = 0; $i < count($this->tickets); $i++)
        {
            $table .= '<tr>';
            $table .= '<td>Ticket '.($i+1).'</td>';
            $table .= '<td>'.$this->tickets[$i]->first.'</td>';
            $table .= '<td>'.$this->tickets[$i]->last.'</td>';
            $table .= '<td>'.$this->tickets[$i]->getTypeName().'</td>';
            $table .= '<td>$'.$this->tickets[$i]->getCost().'</td>';
            $table .= '</tr>';
        }
        $table .= '</table>';
        return $table;
    }

    function getDonationsAsTable()
    {
        if($this->donations == FALSE || count($this->donations) == 0)
        {
            return "No donations";
        }
        $table  = '<table style="margin-left:auto; margin-right:auto; width:100%;">';
        $table .= '<tr><td></td><th>Entity Name</th><th>Amount</th></tr>';
        for($i = 0; $i < count($this->donations); $i++)
        {
            $table .= '<tr>';
            $table .= '<td>Donation '.($i+1).'</td>';
            $table .= '<td>'.$this->donations[$i]->type->entityName.'</td>';
            $table .= '<td>'.$this->donations[$i]->amount.'</td>';
            $table .= '</tr>';
        }
        $table .= '</table>';
        return $table;
    }

    function generatePDF()
    {
        $totalAmount = $this->getTotalAmount();

        $mpdf = new mPDF();
        $pdf = '
            <table width="100%">
                <tr>
                    <td style="text-align: center"><h1>'.count($this->tickets).'</h1></td>
                    <td style="text-align: center"><h2>Burning Flipside 2015 Ticket Request</h2></td>
                    <td style="text-align: right"><barcode code="'.$this->request_id.'" type="C39"/></td>
                </tr>
                <tr>
                    <td>Tickets</td>
                    <td></td>
                    <td style="text-align: center">'.$this->request_id.'</td>
                </tr>
            </table>
            <hr/>
            <strong>This page is your mail-in ticket request form.</strong><br/>
            Instructions:<br/>
            <ol>
                <li>
                    Get a money order, cashier\'s check or teller\'s check for $'.$totalAmount.' made out to Austin Artistic Reconstruction.<br/>
                    <ol type="a">
                        <li>Write '.$this->request_id.' in the memo field.</li>
                        <li><strong>Make sure you save your payment receipt, you will need it for lost mail or returns.</strong></li>
                        <li>Sign your money order if a signature is required.</li>
                    </ol>
                </li>
                <li>Print this form on a sheet of white, 8.5x11 paper (standard letter-size paper).</li>
                <li>
                    Put this form (the whole page) and your money order / cashier\'s check / teller\'s check in a decorated, stamped envelope with your
                    return address on it and address it to:<br/>
                    &nbsp;&nbsp;&nbsp;&nbsp;Austin Artistic Reconstruction, Ticket Request<br/>
                    &nbsp;&nbsp;&nbsp;&nbsp;P. O. Box 9987<br/>
                    &nbsp;&nbsp;&nbsp;&nbsp;Austin, TX 78766<br/>
                </li>
                <li>Mail your envelope so that the postmark is no earlier than 01-15-2015 and not later than 01-23-2015.</li>
            </ol>
            Note: Tickets are limited. If we receive valid requests for more than 2,499 tickets, orders will be filled randomly from total requests. Any unfilled request will be returned.
            <br/><br/>
            <hr/>
            <br/><br/>
            <table style="margin-left:auto; margin-right:auto;">
                <tr>
                    <td>Total Due:</td><td>$'.$totalAmount.'</td>
                    <td>Mail To:</td><td>'.$this->getMailingAddress('<br/>').'</td>
                </tr>
            </table>
            <hr/>
            <h3>Tickets</h3>
            '.$this->getTicketsAsTable().'
            <hr/>
            <h3>Donations</h3>
            '.$this->getDonationsAsTable().'
            <hr/>
        ';
        $mpdf->WriteHTML($pdf);
        $footer = '
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="text-align: center; border-right: 1 solid black"><h1>$'.$totalAmount.'</h1></td>
                    <td style="text-align: center; border-right: 1 solid black">'.$this->givenName.' '.$this->sn.'</td>
                    <td style="text-align: center; border-right: 1 solid black">'.$this->mail.'</td>
                    <td style="text-align: center; border-right: 1 solid black">'.$this->mobile.'</td>
                    <td style="text-align: right" rowspan="2">
                        Request Rev ID:<br/>
                        Request Date:<br/>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: center; border-right: 1 solid black">Total Due</td>
                    <td style="text-align: center; border-right: 1 solid black">Requestor</td>
                    <td style="text-align: center; border-right: 1 solid black">Email</td>
                    <td style="text-align: center; border-right: 1 solid black">Phone</td>
                </tr>
            </table>
        ';
        $mpdf->SetHTMLFooter($footer);
        $filename = '../tmp/'.hash('sha512', json_encode($this)).'.pdf';
        $mpdf->Output($filename);
        return $filename;
    }

    function sendEmail()
    {
        $mail = new FlipsideTicketRequestEmail($this);
        $ret = $mail->send_HTML();
        return $ret;
    }

    function get_random_id($min, $max)
    {
        return (int)(((double)mt_rand()/(mt_getrandmax()+1))*($max-$min+1)+$min);
    }

    function genBucket()
    {
        if($this->crit_vol)
        {
            $this->bucket = 0;
        }
        else if($this->protected)
        {
            $this->bucket = FlipsideTicketDB::getMaxBuckets() - 1;
        }
        else if($this->bucket === FALSE || $this->bucket == -1)
        {
            $this->bucket = $this->get_random_id(1, FlipsideTicketDB::getMaxBuckets() - 2);
        }
    }

    function parse_revisions($revs)
    {
        $this->revisions = array();
        if($revs == NULL)
        {
            return;
        }
        $this->revisions = json_decode($revs);
    }

    function flatten_array($array)
    {
        $children = array();
        $ret = array();
        for($i = 0; $i < count($array); $i++)
        {
            if(is_subclass_of($array[$i], 'FlipsideDBObject'))
            {
                $ret[$i] = $array[$i]->to_value_array(FALSE, $children);
            }
            else
            {
                $ret[$i] = $array[$i];
            }
        }
        if(count($children) > 0)
        {
        }
        return $ret;
    }

    function create_revisions()
    {
        $old = new FlipsideTicketRequest($this->request_id, FALSE, $this->year);
        if($old == FALSE)
        {
            $this->revisions = NULL;
            return;
        }
        $children = array();
        $vals = $old->to_value_array(FALSE, $children);
        foreach($children as $key => $data)
        {
            $flattened = $this->flatten_array($data);
            $vals[$key] = $flattened;
        }
        $this->revisions = $vals['revisions'];
        unset($vals['revisions']);
        array_push($this->revisions, $vals);
    }

    function replace_in_db($db)
    {
        $this->create_revisions();
        $this->revisions = json_encode($this->revisions);
        parent::replace_in_db($db);
    }
}
?>
