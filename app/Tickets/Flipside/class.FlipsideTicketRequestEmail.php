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
        if($source === false)
        {
            $vars = \Tickets\DB\LongTextStringsDataTable::getInstance();
            $this->source = $vars['request_email_source'];
        }
        else
        {
            $this->source = $source;
        }
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

    private function getBodyFromDB($html=true)
    {
        $update_url     = $this->settings['ticket_system_uri'];
        $year           = $this->request->year;
        $start_date     = $this->settings['mail_start_date'];
        $stop_date      = $this->settings['request_stop_date'];
        $vars           = array(
            '{$update_url}'   => $transfer_url,
            '{$year}'           => $year,
            '{$start_date}'     => $start_date,
            '{$stop_date}'      => $stop_date
        );
        $long_text = \Tickets\DB\LongTextStringsDataTable::getInstance();
        $raw_text = $long_text['request_email_source'];
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
        return $this->getBodyFromDB();
    }

    public function getTextBody()
    {
        return $this->getBodyFromDB(false);
    }
}

?>
