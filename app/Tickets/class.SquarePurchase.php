<?php
namespace Tickets;

use \Exception;

use \Square\SquareClient;
use \Square\Environment as SquareEnvironment;
use \Square\Models\OrderLineItem;
use \Square\Models\Money;
use \Square\Models\CreatePaymentLinkRequest;
use \Square\Models\PrePopulatedData;
use \Square\Models\Order;
use \Square\Models\CheckoutOptions;
use \Square\Models\Address;

use \Flipside\DataSetFactory;
use \Tickets\DB\TicketSystemSettings;

class SquarePurchase
{
    private SquareClient $squareClient;
    protected \Flipside\Auth\User $user;
    protected string $buyerEmail;
    protected ?string $buyerFirst;
    protected ?string $buyerLast;
    protected ?int $pool;
    protected array $items;
    protected string $purchaseId;
    protected ?string $personalMessage;
    protected string $saleType;
    protected ?string $requestID;

    public function __construct(\Flipside\Auth\User $user, string $email, ?string $first, ?string $last, ?int $pool, ?string $personalMessage, string $saleType)
    {
        $accessToken = \Flipside\Settings::getInstance()->getGlobalSetting('square')['accessToken'];
        $this->squareClient = new SquareClient(array(
            'accessToken' => $accessToken));
        $this->buyerEmail = $email;
        $this->buyerFirst = $first;
        $this->buyerLast = $last;
        $this->pool = $pool;
        $this->user = $user;
        $this->personalMessage = $personalMessage;
        $this->saleType = $saleType;
        $this->requestID = null;
    }

    public function setRequestID($requestID)
    {
        $this->requestID = $requestID;
    }

    /**
     * Add a set of tickets to the purchase
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function addTickets(array $tickets)
    {
        $year = TicketSystemSettings::getYear();
        $dataTable2 = DataSetFactory::getDataTableByNames('tickets', 'PendingPurchases');
        $ticketTypes = TicketType::getAllTicketTypes();
        if($ticketTypes === false)
        {
            throw new Exception('Unable to get ticket types!');
        }
        $this->items = array();
        $ticketCodes = array();
        foreach($tickets as $typeCode=>$qty)
        {
            if($qty > 0)
            {
                $type = $ticketTypes[$typeCode];
                $desc = $type['description'];
                $item = new OrderLineItem($qty);
                $item->setName("Burning Flipside $desc Ticket - $year");
                $money = new Money();
                $money->setAmount($type['squareCost']*100); //This is in pennies...
                $money->setCurrency('USD');
                $item->setBasePriceMoney($money);
                array_push($this->items, $item);
                $tickets = TicketPool::getTicketsByPoolAndUser($this->pool, $this->user, $qty, $typeCode);
                if($tickets === false || count($tickets) !== $qty)
                {
                    throw new Exception('Unable to locate enough tickets in pool for type '.$typeCode);
                }
                //Set the ticket(s) to transfer in progress...
                for($i = 0; $i < $qty; $i++)
                {
                    $tickets[$i]->transferInProgress = 1;
                    $res = $tickets[$i]->replace_in_db(); //Don't spin the hash...
                    if($res === false)
                    {
                        throw new Exception('Unable to update one or more tickets!');
                    }
                    array_push($ticketCodes, $tickets[$i]->hash);
                }
            }
        }
        $ticketIds = json_encode($ticketCodes);
        $this->purchaseId = hash('haval128,5', $ticketIds);
        $dbData = array('purchaseId'=>$this->purchaseId, 'type'=>'square', 'ticketIds'=>$ticketIds, 'purchaserEmail'=>$this->buyerEmail, 'firstName'=>$this->buyerFirst, 'lastName'=>$this->buyerLast, 'seller'=>$this->user->mail, 'saleType'=>$this->saleType);
        if($this->requestID !== null)
        {
            $dbData['requestID'] = $this->requestID;
        }
        $res = $dataTable2->create($dbData);
        if($res === false)
        {
            throw new Exception('Unable to create pending purchase!');
        }
    }

    public function addTicketsFromRequest(array $requestTickets) {
        $year = TicketSystemSettings::getYear();
        $ticketDataTable = \Tickets\DB\TicketsDataTable::getInstance();
        $dataTable2 = DataSetFactory::getDataTableByNames('tickets', 'PendingPurchases');
        $ticketTypes = TicketType::getAllTicketTypes();
        if($ticketTypes === false)
        {
            throw new Exception('Unable to get ticket types!');
        }
        try {
            $ticketDataTable->beginTransaction();
            $this->items = array();
            $ticketCount = count($requestTickets);
            $ticketCodes = array();
            for($i = 0; $i < $ticketCount; $i++) {
                $typeCode = $requestTickets[$i]->type;
                $type = $ticketTypes[$typeCode];
                $desc = $type['description'];
                $item = new OrderLineItem(1);
                $item->setName("Burning Flipside $desc Ticket - $year");
                $money = new Money();
                $money->setAmount($type['squareCost']*100); //This is in pennies...
                $money->setCurrency('USD');
                $item->setBasePriceMoney($money);
                array_push($this->items, $item);
                $filter = new \Flipside\Data\Filter("year eq $year and type eq '$typeCode' and sold eq 0 and transferInProgress eq 0 and pool_id eq -1 and discretionary eq 0 and assigned eq 0");
                $realTicket = $ticketDataTable->read($filter, false, 1);
                if($realTicket === false)
                {
                    throw new Exception('Unable to locate enough tickets for type '.$typeCode);
                }
                $realTicket = new \Tickets\Ticket($realTicket[0]);
                $realTicket->transferInProgress = 1;
                $realTicket->firstName = $requestTickets[$i]->first;
                $realTicket->lastName = $requestTickets[$i]->last;
                $res = $realTicket->replace_in_db();
                if($res === false)
                {
                    throw new Exception('Unable to update one or more tickets!');
                }
                array_push($ticketCodes, $realTicket->hash);
            }
            $ticketIds = json_encode($ticketCodes);
            $this->purchaseId = hash('haval128,5', $ticketIds);
            $dbData = array('purchaseId'=>$this->purchaseId, 'type'=>'square', 'ticketIds'=>$ticketIds, 'purchaserEmail'=>$this->buyerEmail, 'firstName'=>$this->buyerFirst, 'lastName'=>$this->buyerLast, 'seller'=>$this->user->mail, 'saleType'=>$this->saleType);
            if($this->requestID !== null)
            {
                $dbData['requestID'] = $this->requestID;
            }
            $res = $dataTable2->create($dbData);
            if($res === false)
            {
                throw new Exception('Unable to create pending purchase!');
            }
            $ticketDataTable->commit();
        }
        catch(\Exception $e) {
            $ticketDataTable->rollback();
            throw $e;
        }
    }

    public function addSpecificTicket(\Tickets\Ticket $ticket)
    {
        $dataTable2 = DataSetFactory::getDataTableByNames('tickets', 'PendingPurchases');
        $year = TicketSystemSettings::getYear();
        $type = TicketType::getTicketType($ticket->type);
        $desc = $type['description'];
        $item = new OrderLineItem(1);
        $item->setName("Burning Flipside $desc Ticket - $year");
        $money = new Money();
        $money->setAmount($type['squareCost']*100); //This is in pennies...
        $money->setCurrency('USD');
        $item->setBasePriceMoney($money);
        $ticket->transferInProgress = 1;
        $res = $ticket->replace_in_db(); //Don't spin the hash...
        if($res === false)
        {
            throw new Exception('Unable to update one or more tickets!');
        }
        $this->items = array($item);
        $ticketIds = json_encode(array($ticket->hash));
        $this->purchaseId = hash('haval128,5', $ticketIds);
        $dbData = array('purchaseId'=>$this->purchaseId, 'type'=>'square', 'ticketIds'=>$ticketIds, 'purchaserEmail'=>$this->buyerEmail, 'firstName'=>$this->buyerFirst, 'lastName'=>$this->buyerLast, 'seller'=>$this->user->mail, 'saleType'=>$this->saleType);
        if($this->requestID !== null)
        {
            $dbData['requestID'] = $this->requestID;
        }
        $res = $dataTable2->create($dbData);
        if($res === false)
        {
            throw new Exception('Unable to create pending purchase!');
        }
    }

    public function createLink()
    {
        $url = 'https://secure.burningflipside.com/tickets/squareFinish.php?purchaseId='.$this->purchaseId;
        $squareRequest = new CreatePaymentLinkRequest();
        $paymentData = new PrePopulatedData();
        $paymentData->setBuyerEmail($this->buyerEmail);
        if($this->buyerFirst !== null)
        {
            $address = new Address();
            $address->setFirstName($this->buyerFirst);
            $address->setLastName($this->buyerLast);
            $paymentData->setBuyerAddress($address);
        }
        $squareRequest->setPrePopulatedData($paymentData);
        $order = new Order($this->getLocationID());
        $order->setCustomerId(str_replace(array('@','.','+'), '_', $this->buyerEmail));
        $order->setLineItems($this->items);
        $squareRequest->setOrder($order);
        $options = new CheckoutOptions();
        $options->setRedirectUrl($url);
        $options->setMerchantSupportEmail('tickets@burningflipside.com');
        $squareRequest->setCheckoutOptions($options);
        $squareResponse = $this->squareClient->getCheckoutApi()->createPaymentLink($squareRequest);
        if(!$squareResponse->isSuccess())
        {
            var_dump($squareResponse);
            throw new Exception('Unable to sell ticket(s)!');
        }
        $createLinkResponse = $squareResponse->getResult();
        $purchaseUrl = $createLinkResponse->getPaymentLink()->getUrl();
        $squareID = $createLinkResponse->getPaymentLink()->getId();
        $orderID = $createLinkResponse->getPaymentLink()->getOrderId();
        $dataTable2 = DataSetFactory::getDataTableByNames('tickets', 'PendingPurchases');
        $res = $dataTable2->update(new \Flipside\Data\Filter('purchaseId eq \''.$this->purchaseId.'\''), array('squareLink'=>$purchaseUrl, 'squareID'=>$squareID, 'orderIds'=>json_encode(array($orderID))));
        $email = new SquarePurchaseEmail($purchaseUrl, $this->buyerEmail, $this->buyerFirst, $this->buyerLast, $this->personalMessage);
        if($this->requestID !== null)
        {
            $email->setRequestID($this->requestID);
        }
        $emailProvider = \Flipside\EmailProvider::getInstance();
        $res = $emailProvider->sendEmail($email);
        if($res === false) 
        {
            throw new Exception('Failed to send email!');
        }
        return $purchaseUrl;
    }

    protected function getLocationID()
    {
        $apiResponse = $this->squareClient->getLocationsApi()->listLocations();
        if(!$apiResponse->isSuccess())
        {
            throw new Exception('Unable to get default square location!');
        }
        $result = $apiResponse->getResult();
        $locations = $result->getLocations();
        return $locations[0]->getId();
    }
}