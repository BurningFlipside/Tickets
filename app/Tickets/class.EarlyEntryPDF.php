<?php
namespace Tickets;
require_once(__DIR__.'/../TicketAutoload.php');

class EarlyEntryPDF extends \Flipside\PDF\PDF
{
    private $eePass;
    public  $source;

    function __construct($eePass, $source = false)
    {
        parent::__construct();
        if($eePass === false)
        {
            $this->eePass = array('id' => '0123456789abcdef0123456789abcdef',
                                  'year' => 2024,
                                  'type' => 'Generic Early Entry',
                                  'assignedTo' => 'test@example.com');
        }
        else
        {
            $this->eePass = $eePass;
        }
        if($source === false)
        {
            $vars = \Tickets\DB\LongTextStringsDataTable::getInstance();
            $this->source = $vars['ee_pdf_source'];
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
        $tmp = substr($this->eePass['id'], 0, 8).substr($this->eePass['id'], 24, 8);
        $barcode_hash   = $tmp;
        $barcode        = '<barcode code="'.$barcode_hash.'" type="C128B"/>';
        $qr             = '<barcode code="'.$this->eePass['id'].'" type="QR" class="barcode" size="2" error="L" />';
        $year           = $this->eePass['year'];
        $pass_id        = $this->eePass['id'];
        $assignedTo     = $this->eePass['assignedTo'];
        $type           = $this->eePass['type'];
        $vars           = array(
            '{$barcode}'        => $barcode,
            '{$qr}'             => $qr,
            '{$year}'           => $year,
            '{$pass_id}'        => $pass_id,
            '{$email}'          => $assignedTo,
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
