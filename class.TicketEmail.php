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

    public function send_HTML()
    {
        $this->From     = 'tickets@burningflipside.com';
        $this->FromName = 'Flipside Tickets';
        $this->clearAllRecipients();
        if($this->email == $ticket->email)
        {
            $this->addAddress($this->email, $this->ticket->firstName.' '.$this->ticket->lastName);
            $this->Subject = 'Burning Flipside '.$this->ticket->year.' Will Call Ticket Form';
            $this->Body    = FlipsideTicketDB::get_long_text('ticket_email_source');
            $this->AltBody = strip_tags(FlipsideTicketDB::get_long_text('ticket_email_source'));
        }
        else
        {
            $this->addAddress($this->email);
            $this->Subject = 'Burning Flipside '.$this->ticket->year.' Will Call Ticket Transfer';
            $this->Body    = FlipsideTicketDB::get_long_text('ticket_transfer_email_source');
            $this->AltBody = strip_tags(FlipsideTicketDB::get_long_text('ticket_transfer_email_source'));
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
