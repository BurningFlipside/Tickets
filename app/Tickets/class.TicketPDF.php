<?php
namespace Tickets;
require_once(__DIR__.'/../TicketAutoload.php');

class TicketPDF extends \Flipside\PDF\PDF
{
    private $ticket;
    public  $source;

    function __construct($ticket, $source = false)
    {
        parent::__construct();
        if($ticket === false)
        {
            $this->ticket = \Tickets\Ticket::test_ticket();
        }
        else
        {
            $this->ticket = $ticket;
        }
        if($source === false)
        {
            $vars = \Tickets\DB\LongTextStringsDataTable::getInstance();
            $this->source = $vars['ticket_pdf_source'];
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
        //$barcode_hash   = $this->ticket->getBarcodeHash();
        $tmp = substr($this->ticket->hash, 0, 8).substr($this->ticket->hash, 24, 8);
        //$remainder    = gmp_init($tmp, 16);
        //$barcode_hash = gmp_strval($remainder);
        //if((strlen($barcode_hash) % 2) === 1)
        //{
        //     $barcode_hash = '0'.$barcode_hash;
        //}
        $barcode_hash   = $tmp;
        $barcode        = '<barcode code="'.$barcode_hash.'" type="C128B"/>';
        $transfer_qr    = '<barcode code="'.$settings['ticket_system_uri'].'/transfer.php?id='.$this->ticket->hash.'" type="QR" class="barcode" size="1" error="M" />';
        $year           = $this->ticket->year;
        $ticket_id      = $this->ticket->hash;
        $short_id       = substr($this->ticket->hash, 0, 8);
        $word_code      = Ticket::hash_to_words($this->ticket->hash);
        $name           = $this->ticket->firstName.' '.$this->ticket->lastName;
        $email          = $this->ticket->email;
        $type           = $this->ticket->type;
        $vars           = array(
            '{$barcode}'        => $barcode,
            '{$transfer_qr}'    => $transfer_qr,
            '{$year}'           => $year,
            '{$ticket_id}'      => $ticket_id,
            '{$short_id}'       => $short_id,
            '{$word_code}'      => $word_code,
            '{$name}'           => $name,
            '{$email}'          => $email,
            '{$type}'           => $type
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
