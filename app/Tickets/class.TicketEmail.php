<?php
namespace Tickets;
require_once('Autoload.php');
require_once('app/TicketAutoload.php');

class TicketEmail extends \Email\Email
{
    protected $pm;
    protected $ticket;

    public function __construct($ticket, $pm = false)
    {
        parent::__construct();
        $this->pm         = $pm;
        $this->addToAddress($ticket->email);
        $this->setFromAddress('tickets@burningflipside.com', 'Burning Flipside Tickets');
        $this->ticket = $ticket;
        $pdf = new \Tickets\TicketPDF($ticket);
        $this->addAttachmentFromBuffer($ticket->hash.'.pdf', $pdf->toPDFBuffer(), 'application/pdf');
    }

    public function getSubject()
    {
        return 'Burning Flipside '.$this->ticket->year.' Will Call Receipt';
    }

    private function getBodyFromDB($html=true)
    {
        $barcode        = '<barcode code="'.$this->ticket->hash.'" type="C93"/>';
        $transfer_url   = $this->secureURL.'/tickets/transfer.php?id='.$this->ticket->hash;
        $transfer_qr    = '<barcode code="'.$transfer_url.'" type="QR" class="barcode" size="1" error="M" />';
        $year           = $this->ticket->year;
        $ticket_id      = $this->ticket->hash;
        $short_id       = substr($this->ticket->hash, 0, 8);
        $word_code      = Ticket::hash_to_words($this->ticket->hash);
        $name           = $this->ticket->firstName.' '.$this->ticket->lastName;
        $email          = $this->ticket->email;
        $type           = $this->ticket->type;
        $vars           = array(
            '{$barcode}'        => $barcode,
            '{$transfer_url}'   => $transfer_url,
            '{$transfer_qr}'    => $transfer_qr,
            '{$year}'           => $year,
            '{$ticket_id}'      => $ticket_id,
            '{$short_id}'       => $short_id,
            '{$word_code}'      => $word_code,
            '{$firstName}'      => $this->ticket->firstName,
            '{$lastName}'       => $this->ticket->lastName,
            '{$name}'           => $name,
            '{$email}'          => $email,
            '{$type}'           => $type
        );
        $long_text = \Tickets\DB\LongTextStringsDataTable::getInstance();
        $raw_text = $long_text['ticket_email_source'];
        if($html === true)
        {
            $text = strtr($raw_text, $vars);
            return $text;
        }
        else
        {
            $index = strpos($raw_text, "<script");
            if($index !== false)
            {
                $end = strpos($raw_text, "</script>");
                if($index === 0)
                {
                    $raw_text = substr($raw_text, $end+9);
                }
            }
            return strtr(strip_tags($raw_text), $vars);
        }
    }

    public function getHTMLBody()
    {
        $body = $this->getBodyFromDB();
        if($this->pm !== false)
        {
            $body='<strong>********************Personal Message*************************</strong><br/>'.$this->pm.'<strong>******************End Personal Message***********************</strong><br/>'.$body;
        }
        return $body;
    }

    public function getTextBody()
    {
        $body = $this->getBodyFromDB(false);
        if($this->pm !== false)
        {
            $body="********************Personal Message*************************\n$this->pm>******************End Personal Message***********************\n".$body;
        }
        return $body;
    }
}
?>
