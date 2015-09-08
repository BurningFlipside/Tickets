<?php
require_once('class.FlipsideTicketRequest.php');
require_once('/var/www/common/libs/mpdf/mpdf.php');

class FlipsideTicketRequestPDF
{
    private $request;
    public  $source;

    function __construct($request, $source = FALSE)
    {
        if($request == FALSE)
        {
            $this->request = FlipsideTicketRequest::test_request();
        }
        else
        {
            $this->request = $request;
        }
        if($source == FALSE)
        {
            $this->source = FlipsideTicketDB::get_long_text('pdf_source');
        }
        else
        {
            $this->source = $source;
        }
    }

    function generatePDF()
    {
        $mpdf = new mPDF();
        $ticket_count   = count($this->request->tickets);
        $barcode        = '<barcode code="'.$this->request->request_id.'" type="C39"/>';
        $year           = $this->request->year;
        $request_id     = $this->request->request_id;
        $total_due      = $this->request->total_due;
        $open_date      = FlipsideTicketDB::get_var('mail_start_date');
        $close_date     = FlipsideTicketDB::get_var('request_stop_date');
        $address        = $this->request->getMailingAddress('<br/>'); 
        $ticket_table   = $this->request->getTicketsAsTable();
        $donation_table = $this->request->getDonationsAsTable();
        $requestor      = $this->request->givenName+' '+$this->request->sn;
        $email          = $this->request->mail;
        $phone          = $this->request->mobile;
        $request_date   = $this->request->modifiedOn;
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
            '{$request_date}'   => $request_date
        );
        $html           = strtr($this->source, $vars);
        $mpdf->WriteHTML($html);
        $filename = '/var/www/secure/tickets/tmp/'.hash('sha512', json_encode($this->request)).'.pdf';
        $mpdf->Output($filename);
        return $filename;
    }
}
?>
