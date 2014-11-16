<?php
require_once('class.Ticket.php');
require_once('mpdf/mpdf.php');

class TicketPDF
{
    private $ticket;
    public  $source;

    function __construct($ticket, $source = FALSE)
    {
        if($ticket == FALSE)
        {
            $this->ticket = Ticket::test_ticket();
        }
        else
        {
            $this->ticket = $ticket;
        }
        if($source == FALSE)
        {
            $this->source = FlipsideTicketDB::get_long_text('ticket_pdf_source');
        }
        else
        {
            $this->source = $source;
        }
    }

    function generatePDF()
    {
        $mpdf = new mPDF();
        $barcode        = '<barcode code="'.$this->ticket->hash.'" type="C93"/>';
        $year           = $this->ticket->year;
        $ticket_id      = $this->ticket->hash;
        $short_id       = substr($this->ticket->hash, 0, 8);
        $name           = $this->ticket->firstName.' '.$this->ticket->lastName;
        $email          = $this->ticket->email;
        $type           = $this->ticket->type;
        $vars           = array(
            '{$barcode}'        => $barcode,
            '{$year}'           => $year,
            '{$ticket_id}'      => $ticket_id,
            '{$short_id}'       => $short_id,
            '{$name}'           => $name,
            '{$email}'          => $email,
            '{$type}'           => $type
        );
        $html           = strtr($this->source, $vars);
        $mpdf->WriteHTML($html);
        $filename = '/var/www/secure/tickets/tmp/'.hash('sha512', json_encode($this->request)).'.pdf';
        $mpdf->Output($filename);
        return $filename;
    }
}
?>
