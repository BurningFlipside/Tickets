<?php
namespace Tickets\Flipside;

class RequestPDF extends \PDF\PDF
{
    private $request;
    public  $source;

    function __construct($request, $source = false)
    {
        parent::__construct();
        if($request === false)
        {
            $this->request = FlipsideTicketRequest::testRequest();
        }
        else
        {
            $this->request = $request;
        }
        if($source === false)
        {
            $vars = \Tickets\DB\LongTextStringsDataTable::getInstance();
            $this->source = $vars['pdf_source'];
        }
        else
        {
            $this->source = $source;
        }
        $this->createPDFBody();
    }

    private function createPDFBody()
    {
        $settings = \Tickets\DB\TicketSystemSettings::getInstance(); 

        $ticket_count   = count($this->request->tickets);
        $barcode        = '<barcode code="'.$this->request->request_id.'" type="C39"/>';
        $year           = $this->request->year;
        $request_id     = $this->request->request_id;
        $total_due      = $this->request->total_due;
        $open_date      = $settings['mail_start_date'];
        $close_date     = $settings['request_stop_date'];
        if($this->request->c != 'US')
        {
            $address .= $this->request->c.$linebreak;
        }
        $last           = $this->request->sn;
        $address        = $this->request->givenName.' '.$this->request->sn.'<br/>';
        $address       .= $this->request->street.'<br/>';
        $address       .= $this->request->l.', '.$this->request->st.' '.$this->request->zip.'<br/>';
        if($this->request->c !== 'US')
        {
            $address   .= $this->request->c.'<br/>';
        }
        $ticket_table   = '<table style="margin-left:auto; margin-right:auto; width:100%;">';
        $ticket_table  .= '<tr><td></td><th>First Name</th><th>Last Name</th><th>Ticket Type</th><th>Cost</th></tr>';
        $count = count($this->request->tickets);
        for($i = 0; $i < $count; $i++)
        {
            $ticket_table .= '<tr>';
            $ticket_table .= '<td>Ticket '.($i+1).'</td>';
            $ticket_table .= '<td>'.$this->request->tickets[$i]->first.'</td>';
            $ticket_table .= '<td>'.$this->request->tickets[$i]->last.'</td>';
            $type = \Tickets\TicketType::getTicketType($this->request->tickets[$i]->type);
            $ticket_table .= '<td>'.$type->description.'</td>';
            $ticket_table .= '<td>$'.$type->cost.'</td>';
            $ticket_table .= '</tr>';
        }
        $ticket_table .= '</table>';
        $donation_table = 'No donations';
        if($this->request->donations !== false && $this->request->donations !== null && count((array)$this->request->donations) !== 0)
        {
            $donation_table  = '<table style="margin-left:auto; margin-right:auto; width:100%;">';
            $donation_table .= '<tr><td></td><th>Entity Name</th><th>Amount</th></tr>';
            $donations = get_object_vars($this->request->donations);
            foreach($donations as $type => $donation);
            {
                $donation_table .= '<tr><td>Donation '.($i+1).'</td><td>'.$type.'</td>';
                $donation_table .= '<td>$'.$donation->amount.'</td></tr>';
            }
            $donation_table .= '</table>';
        }
        $requestor      = $this->request->givenName.' '.$this->request->sn;
        $email          = $this->request->mail;
        $phone          = $this->request->mobile;
        $request_date   = $this->request->modifiedOn;
        $envelopeArtText = '';
        if(isset($this->request->envelopeArt) && $this->request->envelopeArt === '1')
        {
            $envelopeArtText = 'AAR, LLC may use my envelope art in the Survival Guide or on the Burning Flipside Website with credit given to the name provided on the envelope\'s return address label.';
        }
        $vars           = array(
            '{$ticket_count}'   => $ticket_count,
            '{$barcode}'        => $barcode,
            '{$year}'           => $year,
            '{$request_id}'     => $request_id,
            '{$total_due}'      => $total_due,
            '{$open_date}'      => $open_date,
            '{$close_date}'     => $close_date,
            '{$address}'        => $address,
            '{$ticket_table}'   => $ticket_table,
            '{$donation_table}' => $donation_table,
            '{$requestor}'      => $requestor,
            '{$email}'          => $email,
            '{$phone}'          => $phone,
            '{$request_date}'   => $request_date,
            '{$last}'           => $last,
            '{$envelopeArtText}'=> $envelopeArtText
        );
        $html           = strtr($this->source, $vars);
        $this->setPDFFromHTML($html);
    }

    function generatePDF($std_out = false)
    {
        if($std_out === false)
        {
            $filename = '/var/www/secure/tickets/tmp/'.hash('sha512', json_encode($this->request)).'.pdf';
            $mpdf->Output($filename);
            return $filename;
        }
        else
        {
            $mpdf->Output();
        }
    }
}
?>
