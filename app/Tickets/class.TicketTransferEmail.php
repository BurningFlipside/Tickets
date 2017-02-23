<?php
namespace Tickets;
require_once('Autoload.php');
require_once('app/TicketAutoload.php');


class TicketTransferEmail extends TicketEmail
{
    public function __construct($ticket, $email, $pm = false)
    {
        parent::__construct($ticket, $pm);
        $this->addToAddress($email);
    }

    public function getSubject()
    {
        return 'Burning Flipside '.$this->ticket->year.' Will Call Ticket Transfer';
    }

    private function getBodyFromDB($html=true)
    {
        $barcode        = '<barcode code="'.$this->ticket->hash.'" type="C93"/>';
        $transfer_url   = $this->secureUrl.'/tickets/transfer.php?id='.$this->ticket->hash;
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
            '{$name}'           => $name,
            '{$email}'          => $email,
            '{$type}'           => $type
        );
        $long_text = \Tickets\DB\LongTextStringsDataTable::getInstance();
        $raw_text = $long_text['ticket_transfer_email_source'];
        if($html === true)
        {
            return strtr($raw_text, $vars);
        }
        else
        {
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
