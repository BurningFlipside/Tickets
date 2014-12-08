<?php
require_once('class.Ticket.php');
require_once('class.FlipsideMail.php');
class TicketEmail extends FlipsideMail
{
    private $ticket;
    private $email;
    private $attach_pdf;

    function __construct($ticket, $email, $attachment)
    {
        parent::__construct();
        $this->ticket     = $ticket;
        $this->email      = $email;
        $this->attach_pdf = $attachment;
    }

    public function queue_email()
    {
        $filename = FlipsideSettings::$filesystem['data'].'/pending_ticket_emails';
        $array = array();
        if(file_exists($filename))
        {
            $old_str  = file_get_contents($filename);
            if($old_str !== FALSE && strlen($old_str) > 0)
            {
                $array    = unserialize($old_str);
            }
        }
        array_push($array, $this);
        $str = serialize($array);
        file_put_contents($filename, $str, LOCK_EX);
    }

    public static function pop_queued_emails($count)
    {
        $filename = FlipsideSettings::$filesystem['data'].'/pending_ticket_emails';
        $old_str  = file_get_contents($filename);
        $array    = unserialize($old_str);
        if(count($array) <= $count)
        {
            unlink($filename);
            return $array;
        }
        else
        {
            $ret = array_slice($array, 0, $count);
            $array = array_slice($array, $count);
            $str = serialize($array);
            file_put_contents($filename, $str, LOCK_EX);
            return $ret;
        }
    }

    public function send_HTML()
    {
        $this->From     = 'tickets@burningflipside.com';
        $this->FromName = 'Flipside Tickets';
        $this->clearAllRecipients();
        $barcode        = '<barcode code="'.$this->ticket->hash.'" type="C93"/>';
        $transfer_url   = 'https://secure.burningflipside.com/tickets/transfer.php?id='.$this->ticket->hash;
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
        if($this->email == $this->ticket->email)
        {
            $this->addAddress($this->email, $this->ticket->firstName.' '.$this->ticket->lastName);
            $this->Subject = 'Burning Flipside '.$this->ticket->year.' Will Call Ticket Form';
            $this->Body    = strtr(FlipsideTicketDB::get_long_text('ticket_email_source'), $vars);
            $this->AltBody = strtr(strip_tags(FlipsideTicketDB::get_long_text('ticket_email_source')), $vars);
        }
        else
        {
            $this->addAddress($this->email);
            $this->Subject = 'Burning Flipside '.$this->ticket->year.' Will Call Ticket Transfer';
            $this->Body    = strtr(FlipsideTicketDB::get_long_text('ticket_transfer_email_source'), $vars);
            $this->AltBody = strtr(strip_tags(FlipsideTicketDB::get_long_text('ticket_transfer_email_source')), $vars);
        }
        $this->isHTML(true);
        if($this->attach_pdf)
        {
            $pdf_file_name = $this->ticket->generatePDF();
            $this->addAttachment($pdf_file_name, 'Flipside '.$this->ticket->year.' Will Call Ticket Form.pdf');
        }
        $ret = $this->send();
        if($this->attach_pdf)
        {
            unlink($pdf_file_name);
        }
        return $ret;
    }

}
?>
