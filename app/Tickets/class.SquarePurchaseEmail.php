<?php
namespace Tickets;
require_once('Autoload.php');
require_once(__DIR__.'/../TicketAutoload.php');


class SquarePurchaseEmail extends \Flipside\Email\Email
{
    protected string $purchaseLink;
    protected string $first;
    protected string $last;
    protected ?string $personalMessage;

    public function __construct(string $purchaseLink, string $email, string $first, string $last, ?string $personalMessage = '')
    {
        parent::__construct();
        $this->addToAddress($email);
        $this->purchaseLink = $purchaseLink;
        $this->first = $first;
        $this->last = $last;
        $this->personalMessage = $personalMessage;
        $this->setFromAddress('tickets@burningflipside.com', 'Burning Flipside Tickets');
    }

    public function getSubject()
    {
        $settings = \Tickets\DB\TicketSystemSettings::getInstance();
        $year = $settings['year'];
        return 'Burning Flipside '.$year.' Ticket Purchase';
    }

    private function getBodyFromDB($html=true)
    {
        $settings = \Tickets\DB\TicketSystemSettings::getInstance();
        $vars           = array(
            '{$purchaseLink}'   => $this->purchaseLink,
            '{$firstName}'      => $this->first,
            '{$lastName}'       => $this->last
        );
        $long_text = \Tickets\DB\LongTextStringsDataTable::getInstance();
        $raw_text = $long_text['square_purchase_email_source'];
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
        if(strlen($this->personalMessage) > 0)
        {
            $body='<strong>********************Personal Message*************************</strong><br/>'.$this->personalMessage.'<strong>******************End Personal Message***********************</strong><br/>'.$body;
        }
        return $body;
    }

    public function getTextBody()
    {
        $body = $this->getBodyFromDB(false);
        if(strlen($this->personalMessage) > 0)
        {
            $body="********************Personal Message*************************\n$this->personalMessage>******************End Personal Message***********************\n".$body;
        }
        return $body;
    }
}
?>
