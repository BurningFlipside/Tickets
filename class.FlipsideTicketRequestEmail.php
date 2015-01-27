<?php
require_once('class.FlipsideTicketRequest.php');
require_once('class.FlipsideMail.php');
class FlipsideTicketRequestEmail extends FlipsideMail
{
    private $request;

    function __construct($request)
    {
        parent::__construct();
        $this->request = $request;
    }

    public function send_HTML($mail = FALSE)
    {
        $this->From     = 'tickets@burningflipside.com';
        $this->FromName = 'Flipside Tickets';
        $this->clearAllRecipients();
        $this->addAddress($this->request->mail, $this->request->givenName.' '.$this->request->sn);
        $this->isHTML(true);

        $this->Subject = 'Burning Flipside '.$this->request->year.' Ticket Request Confirmation & Mail-In Form';
        $this->Body    = '
            <!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
            <html>
                <body>
                We\'ve received your ticket registration for Burning Flipside '.$this->request->year.' and your Ticket Request form for mailing in with your payment is attached. 
                <b>Please print the Ticket Request form (PDF) and follow the instructions on it to complete your ticket request.</b><br><br>
                Reminder:  The dates for mailing your ticket request envelope are 01-07-2015 through 01-22-2015. Your postmark must be within that date range.<br><br>
                If you need to update your ticket registration before mailing in your ticket request, return to <a href="https://secure.burningflipside.com/tickets/">secure.burningflipside.com/tickets/</a>.<br><br>
                You will receive a confirmation message each time you Save your online ticket registration. Be sure to mail in the most recent Ticket Request form (check the Last Updated time on the form if you are unsure).<br><br>
                Caution: We recommend that you use a form of payment you can easily return for a refund if necessary. <br><br>
                For help with how to fill out a money order, something most of us rarely do, see http://www.wikihow.com/Fill-Out-a-Money-Order.<br><br>
                __ _ _<br><br>
                Your '.$this->request->year.' Flipside Ticket Team<br>
                </body>
            </html>';
        $this->AltBody = '
            We\'ve received your ticket registration for Burning Flipside '.$this->request->year.' and your Ticket Request form for mailing in with your payment is attached.
            Please print the Ticket Request form (PDF) and follow the instructions on it to complete your ticket request.
            Reminder:  The dates for mailing your ticket request envelope are 01-07-2015 through 01-22-2015. Your postmark must be within that date range.
            If you need to update your ticket registration before mailing in your ticket request, return to https://secure.burningflipside.com/tickets/.
            You will receive a confirmation message each time you Save your online ticket registration. Be sure to mail in the most recent Ticket Request form (check the Last Updated time on the form if you are unsure).
            Caution: We recommend that you use a form of payment you can easily return for a refund if necessary.
            For help with how to fill out a money order, something most of us rarely do, see http://www.wikihow.com/Fill-Out-a-Money-Order.
            __ _ _<
            Your '.$this->request->year.' Flipside Ticket Team';
        $pdf_file_name = $this->request->generatePDF();
        $this->addAttachment($pdf_file_name, 'Flipside '.$this->request->year.' Ticket Request Form.pdf');
        $ret = $this->send();
        unlink($pdf_file_name);
        return $ret;
    }

}
?>
