<?php
use \Square\SquareClient;
use \Square\Environment as SquareEnvironment;

class CompletedPurchaseAPI extends AdminTicketDataTableAPI
{
    private SquareClient $squareClient;
    private string $accessToken;
    private array $squareData;

    public function __construct()
    {
        parent::__construct('tickets', 'tblCompletedCCSales', 'purchaseId');
        $accessToken = \Flipside\Settings::getInstance()->getGlobalSetting('square')['accessToken'];
        $this->squareClient = new SquareClient(array(
            'accessToken' => $accessToken));
        $this->accessToken = $accessToken;
        $this->squareData = array();
    }

    public function setup($app)
    {
        $app->get('/square/order/{id}', array($this, 'getSquareOrder'));
        $app->get('/square/purchase/{id}', array($this, 'getSquarePurchase'));
        $app->post('/square/Actions/UpdateZeroAmounts', array($this, 'updateZeroAmounts'));
        parent::setup($app);
    }

    protected function canCreate($request)
    {
        //These are created via users completing a sale
        return false;
    }

    protected function canUpdate($request, $entity)
    {
        return false;
    }

    protected function canDelete($request, $entity)
    {
        return false;
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
        $orderIds = json_decode($entry['orderIds']);
        $orders = array();
        $count = count($orderIds);
        for($i = 0; $i < $count; $i++)
        {
            array_push($orders, $orderIds[$i]);
        }
        $entry['orders'] = $orders;
        unset($entry['orderIds']);
        return $entry;
    }

    public function getSquareOrder($request, $response, $args)
    {
        $orderId = $args['id'];
        $squareResponse = $this->squareClient->getOrdersApi()->retrieveOrder($orderId);
        return $response->withJson($squareResponse->getResult());
    }

    public function getSquarePurchase($request, $response, $args)
    {
        $purchaseId = $args['id'];
        $squareResponse = $this->squareClient->getPaymentsApi()->getPayment($purchaseId);
        return $response->withJson($squareResponse->getResult());
    }

    public function updateZeroAmounts($request, $response, $args)
    {
        $dataTable = $this->getDataTable();
        $data = $dataTable->read(new \Flipside\Data\Filter('amount eq 0'));
        $count = count($data);
        if($count === 0)
        {
            return $response->withJson(array('message' => 'No zero amount sales found'));
        }
        $updated = 0;
        for($i = 0; $i < $count; $i++)
        {
            $entry = $data[$i];
            $orderIds = json_decode($entry['orderIds']);
            $count2 = count($orderIds);
            if($count2 === 0)
            {
                continue;
            }
            $orderId = $orderIds[0];
            $squareResponse = $this->squareClient->getOrdersApi()->retrieveOrder($orderId);
            $order = $squareResponse->getResult()->getOrder();
            $money = $order->getTotalMoney();
            $amount = $money->getAmount();
            if($amount !== null) {
                $entry['amount'] = (float)$amount/100;
                $res = $dataTable->update(new \Flipside\Data\Filter('purchaseId eq "'.$entry['purchaseId'].'"'), $entry);
                if($res === false)
                {
                    return $response->withJson(array('message' => 'Failed to update entry'));
                }
                $updated++;
            }
        }
        return $response->withJson(array('message' => 'Updated '.$updated.' entries'));
    }
}
/* vim: set tabstop=4 shiftwidth=4 expandtab: */
