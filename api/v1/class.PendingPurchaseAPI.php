<?php
use \Square\SquareClient;
use \Square\Environment as SquareEnvironment;

class PendingPurchaseAPI extends AdminTicketDataTableAPI
{
    private SquareClient $squareClient;
    private string $accessToken;
    private array $squareData;

    public function __construct()
    {
        parent::__construct('tickets', 'tblPendingPurchases', 'purchaseId');
        $accessToken = \Flipside\Settings::getInstance()->getGlobalSetting('square')['accessToken'];
        $this->squareClient = new SquareClient(array(
            'accessToken' => $accessToken));
        $this->accessToken = $accessToken;
        $this->squareData = array();
    }

    protected function canCreate($request)
    {
        //These are created via the ticket sales API
        return false;
    }

    protected function canUpdate($request, $entity)
    {
        return false;
    }

    protected function canDelete($request, $entity)
    {
        $this->validateLoggedIn($request);
        return $this->user->isInGroupNamed('TicketAdmins');
    }

    protected function processEntry($entry, $request)
    {
        $ticketIds = json_decode($entry['ticketIds']);
        $tickets = array();
        $count = count($ticketIds);
        for($i = 0; $i < $count; $i++)
        {
            if($request->getQueryParam('$expand') === 'tickets')
            {
                array_push($tickets, \Tickets\Ticket::get_ticket_by_hash($ticketIds[$i]));
            }
            else
            {
                array_push($tickets, $request->getUri()->getBasePath().'/tickets/'.$ticketIds[$i]);
            }
        }
        $entry['tickets'] = $tickets;
        unset($entry['ticketIds']);
        if($entry['type'] === 'square')
        {
            $purchaseId = $entry['purchaseId'];
            if(!isset($this->squareData[$purchaseId]))
            {
                $squareResponse = $this->squareClient->getCheckoutApi()->listPaymentLinks();
                if(!$squareResponse->isSuccess())
                {
                    throw new Exception('Unable to get square purchase links!');
                }
                $getLinkResponse = $squareResponse->getResult();
                $links = $getLinkResponse->getPaymentLinks();
                $count = count($links);
                for($i = 0; $i < $count; $i++)
                {
                    $link = $links[$i];
                    $linkText = $link->getCheckoutOptions()->getRedirectUrl();
                    $queryStr = parse_url($linkText, PHP_URL_QUERY);
                    parse_str($queryStr, $queryParams);
                    $this->squareData[$queryParams['purchaseId']] = $link;
                }
                $cursor = $squareResponse->getCursor();
                while($cursor)
                {
                    $squareResponse = $this->squareClient->getCheckoutApi()->listPaymentLinks($cursor);
                    if(!$squareResponse->isSuccess())
                    {
                        throw new Exception('Unable to get square purchase links!');
                    }
                    $getLinkResponse = $squareResponse->getResult();
                    $links = $getLinkResponse->getPaymentLinks();
                    $count = count($links);
                    for($i = 0; $i < $count; $i++)
                    {
                        $link = $links[$i];
                        $linkText = $link->getCheckoutOptions()->getRedirectUrl();
                        $queryStr = parse_url($linkText, PHP_URL_QUERY);
                        parse_str($queryStr, $queryParams);
                        $this->squareData[$queryParams['purchaseId']] = $link;
                    }
                    $cursor = $squareResponse->getCursor();
                }
            }
            if(isset($this->squareData[$purchaseId]))
            {
                $entry['squareInformation'] = $this->squareData[$purchaseId];
            }
        }
        return $entry;
    }

    protected function postDeleteAction($entry)
    {
        $entry = $entry[0];
        //Need to also delete the square payment link...
        $squareResponse = $this->squareClient->getCheckoutApi()->listPaymentLinks();
        if(!$squareResponse->isSuccess())
        {
            throw new Exception('Unable to get square purchase links!');
        }
        $getLinkResponse = $squareResponse->getResult();
        $links = $getLinkResponse->getPaymentLinks();
        $count = count($links);
        for($i = 0; $i < $count; $i++)
        {
            $link = $links[$i];
            $linkText = $link->getCheckoutOptions()->getRedirectUrl();
            $queryStr = parse_url($linkText, PHP_URL_QUERY);
            parse_str($queryStr, $queryParams);
            if($queryParams['purchaseId'] === $entry['purchaseId'])
            {
                $squareResponse = $this->squareClient->getCheckoutApi()->deletePaymentLink($link->getId());
                if(!$squareResponse->isSuccess())
                {
                    throw new Exception('Unable to delete square purchase link!');
                }
            }
        }
        $ticketIds = json_decode($entry['ticketIds']);
        $count = count($ticketIds);
        for($i = 0; $i < $count; $i++)
        {
            $ticket = \Tickets\Ticket::get_ticket_by_hash($ticketIds[$i]);
            if($ticket === false)
            {
                continue;
            }
            $ticket->transferInProgress = 0;
            $res = $ticket->replace_in_db(); //Don't spin the hash...
            if($res === false)
            {
                throw new Exception('Unable to update one or more tickets!');
            }
        }
        return true;
    }
}
/* vim: set tabstop=4 shiftwidth=4 expandtab: */
