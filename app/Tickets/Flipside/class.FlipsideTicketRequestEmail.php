<?php
namespace Tickets\Flipside;

class FlipsideTicketRequestEmail extends \Email\Email
{
    private $request;
    private $settings;

    function __construct($request)
    {
        parent::__construct();
        $this->request = $request;
        $this->settings = \Tickets\DB\TicketSystemSettings::getInstance();
        $pdf = new \Tickets\Flipside\RequestPDF($request);
        $this->addAttachmentFromBuffer('Flipside '.$this->request->year.' Ticket Request Form.pdf', $pdf->toPDFBuffer(), 'application/pdf');
    }

    public function getFromAddress()
    {
        return 'Burning Flipside Ticket System <tickets@burningflipside.com>';
    }

    public function getToAddresses()
    {
        return $this->request->givenName.' '.$this->request->sn.' <'.$this->request->mail.'>';
    }

    public function getSubject()
    {
        return 'Burning Flipside '.$this->request->year.' Ticket Request';
    }

    public function getHTMLBody()
    {
        return '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
            <html>
                <body>
                We\'ve received your ticket registration for Burning Flipside '.$this->request->year.' 
                and your Ticket Request form for mailing in with your payment is attached.
                <b>Please print the Ticket Request form (PDF) and follow the instructions on it to complete your ticket request.</b><br><br>
                Reminder:  The dates for mailing your ticket request envelope are '.$this->settings['mail_start_date'].' through '
                .$this->settings['request_stop_date'].'. Your postmark must be within that date range.<br><br>
                If you need to update your ticket registration before mailing in your ticket request, return to 
                <a href="'.$this->secureUrl.'/tickets/">secure.burningflipside.com/tickets/</a>.<br><br>
                You will receive a confirmation message each time you save your online ticket registration. Be sure to mail in the most recent 
                Ticket Request form.<br><br>
                Caution: We recommend that you use a form of payment you can easily return for a refund if necessary. <br><br>
                For help with how to fill out a money order, something most of us rarely do, see http://www.wikihow.com/Fill-Out-a-Money-Order.<br><br>
                ____<br><br>
                Your '.$this->request->year.' Flipside Ticket Team<br>
                </body>
            </html>';
    }

    public function getTextBody()
    {
        return "We\'ve received your ticket registration for Burning Flipside ".$this->request->year."
                and your Ticket Request form for mailing in with your payment is attached.
                Please print the Ticket Request form (PDF) and follow the instructions on it to complete your ticket request.\n\n
                Reminder:  The dates for mailing your ticket request envelope are ".$this->settings['mail_start_date']." through "
                .$this->settings['request_stop_date'].". Your postmark must be within that date range.\n\n
                If you need to update your ticket registration before mailing in your ticket request, return to
                '.$this->secureUrl.'/tickets/.\n\n
                You will receive a confirmation message each time you save your online ticket registration. Be sure to mail in the most recent
                Ticket Request form.\n\n
                Caution: We recommend that you use a form of payment you can easily return for a refund if necessary.\n\n
                For help with how to fill out a money order, something most of us rarely do, see http://www.wikihow.com/Fill-Out-a-Money-Order.\n\n
                ____\n\n
                Your ".$this->request->year." Flipside Ticket Team";
    }
}

?>
