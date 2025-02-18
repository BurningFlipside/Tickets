<?php
namespace Tickets\Flipside;

class Request extends \Flipside\SerializableObject
{
    protected static $statuses = false;

    public function __construct($array = false)
    {
        if($array !== false)
        {
            if(is_object($array))
            {
                $array = get_object_vars($array);
            }
            if(is_array($array))
            {
                foreach($array as $key => $value)
                {
                    switch($key)
                    {
                        case 'year':
                        case 'status':
                        case 'private_status':
                        case 'bucket':
                            $this->{$key} = intval($value);
                            break;
                        case 'ticketAmount':
                        case 'donationAmount':
                        case 'total_due':
                        case 'total_received':
                            $this->{$key} = floatval($value);
                            break;
                        case 'crit_vol':
                        case 'protected':
                        case 'envelopeArt':
                        case 'survivalGuide':
                            $this->{$key} = boolval($value);
                            break;
                        case 'tickets':
                        case 'donations':
                        case 'revisions':
                            if(is_array($value))
                            {
                                $this->{$key} = $value;
                            }
                            else
                            {
                                $this->{$key} = json_decode($value);
                            }
                            break;
                        default:
                            $this->{$key} = $value;
                            break;
                    }
                }
            }
        }
    }

    public function enhanceStatus()
    {
        if(static::$statuses === false)
        {
            $dataTable = \Flipside\DataSetFactory::getDataTableByNames('tickets', 'RequestStatus');
            $statusesData = $dataTable->read(false);
            $count = count($statusesData);
            static::$statuses = array();
            for($i = 0; $i < $count; $i++)
            {
                $statusId = intval($statusesData[$i]['status_id']);
                $statusesData[$i]['status_id'] = $statusId;
                static::$statuses[$statusId] = $statusesData[$i];
            }
        }

	$this->status = static::$statuses[$this->status];
    }

    public function validateTickets($minorConfirm, $paymentMethod)
    {
        $settings = \Tickets\DB\TicketSystemSettings::getInstance();
        $count = count($this->tickets);
        if($count > $settings['max_tickets_per_request'])
        {
            throw new Exception('Too many tickets for request', INVALID_PARAM);
        }
        $ticketDataSet = \Flipside\DataSetFactory::getDataSetByName('tickets');
        
        $typeCounts = array();
        for($i = 0; $i < $count; $i++)
        {
            $ticket = $this->tickets[$i];
            if(is_array($ticket))
            {
                $tmp = new \stdClass();
                $tmp->first = $ticket['first'];
                $tmp->last = $ticket['last'];
                $tmp->type = $ticket['type'];
                $type = \Tickets\TicketType::getTicketType($ticket['type']);
                switch($paymentMethod)
                {
                    case 'cc':
                    case 'CC':
                        $tmp->cost = $type['squareCost'];
                        break;
                    default:
                        $tmp->cost = $type['cost'];
                        break;
                }
                $tmp->cost = $type['cost'];
                $ticket = $tmp;
                $this->tickets[$i] = $tmp;
            }
            if($minorConfirm !== true && \Tickets\TicketType::typeIsMinor($ticket->type))
            {
                return array('need_minor_confirm'=>true);
            }
            if(isset($typeCounts[$ticket->type]))
            {
                $typeCounts[$ticket->type]++;
            }
            else
            {
                $typeCounts[$ticket->type] = 1;
            }
        }
        $count = count($typeCounts);
        $keys = array_keys($typeCounts);
        for($i = 0; $i < $count; $i++)
        {
            if($typeCounts[$keys[$i]] > 1)
            {
                $type = \Tickets\TicketType::getTicketType($keys[$i]);
                if($type->maxPerRequest < $typeCounts[$keys[$i]])
                {
                    throw new \Exception('Too many tickets of type '.$keys[$i].' for request', \Flipside\Http\Rest\INVALID_PARAM);
                }
            }
        }
        return false;
    }

    public function validateRequestId($email)
    {
        $ticketDataSet = \Flipside\DataSetFactory::getDataSetByName('tickets');
        $requestIdTable = $ticketDataSet['RequestIDs'];
        $filter = new \Flipside\Data\Filter("mail eq '$email'");
        $requestIds = $requestIdTable->read($filter);
        if($requestIds === false || !isset($requestIds[0]) || !isset($requestIds[0]['request_id']))
        {
            throw new \Exception('Request ID not retrievable! Call GetRequestID first.', \Flipside\Http\Rest\INVALID_PARAM);
        }
        if($requestIds[0]['request_id'] !== $this->request_id)
        {
            throw new \Exception('Request ID not correct!', \Flipside\Http\Rest\INVALID_PARAM);
        }
    }

    protected function calculateTicketTotal()
    {
        $amt = 0;
        $count = count($this->tickets);
        for($i = 0; $i < $count; $i++)
        {
            $ticket = $this->tickets[$i];
            $type = \Tickets\TicketType::getTicketType($ticket->type);
            switch($this->paymentMethod)
            {
                case 'cc':
                case 'CC':
                    $amt += $type->squareCost;
                    break;
                default:
                    $amt += $type->cost;
                    break;
            }
        }
        return $amt;
    }

    protected function calculateDonationTotal()
    {
        $amt = 0;
        if(!isset($this->donations))
        {
            return 0;
        }
        foreach($this->donations as $donation)
        {
             if(is_array($donation))
             {
                 $amt += (int)$donation['amount'];
             }
             else if(property_exists($donation, 'amount'))
             {
                 $amt += (int)$donation->amount;
             }
        }
        return $amt;
    }

    protected function encodeForSQL()
    {
        $settings = \Tickets\DB\TicketSystemSettings::getInstance();
        if(!isset($this->year))
        {
            $this->year = $settings['year'];
        }
        unset($this->lists);
        $count = count($this->tickets);
        for($i = 0; $i < $count; $i++)
        {
            unset($this->tickets[$i]->cost);
        }
        $this->ticketAmount = $this->calculateTicketTotal();
        $this->donationAmount = $this->calculateDonationTotal();
        $this->total_due = $this->ticketAmount + $this->donationAmount;
        if(property_exists($this, 'survivalGuide') && ($this->survivalGuide || $this->survivalGuide === 1 || $this->survivalGuide === '1'))
        {
            $this->total_due += 6;
        }
        if(property_exists($this, 'total_received') && ($this->total_received - 6 == $this->total_due))
        {
            $this->total_due = $this->total_received;
        }
        $ret = (array)$this;
        if(isset($ret['tickets']))
        {
            $ret['tickets'] = json_encode($ret['tickets']);
        }
        else
        {
            $ret['tickets'] = 'null';
        }
        if(isset($ret['donations']))
        {
            $ret['donations'] = json_encode($ret['donations']);
        }
        else
        {
            $ret['donations'] = 'null';
        }
        $requestDataTable = \Tickets\DB\RequestDataTable::getInstance();
        $filter = new \Flipside\Data\Filter("request_id eq '".$this->request_id."' and year eq ".$this->year);
        $requests = $requestDataTable->read($filter);
        if($requests === false)
        {
            $ret['revisions'] = '[]';
        }
        else
        {
            $oldRevs = $requests[0]['revisions'];
            unset($requests[0]['revisions']);
            array_push($oldRevs, $requests[0]);
            $ret['revisions'] = json_encode($oldRevs);
        }
        return $ret;
    }

    public function preUpdate()
    {
        return $this->encodeForSQL();
    }

    public function preCreate()
    {
        $ret = $this->encodeForSQL();
        $ret['total_received'] = 0;
        $ret['crit_vol'] = 0;
        $ret['protected'] = 0;
        $ret['status'] = 0;
        $ret['private_status'] = 0;
        $ret['comments'] = '';
        $ret['bucket'] = -1;
        return $ret;
    }

    function update($datatable=false)
    {
        if($datatable === false)
        {
            $datatable = self::getDataTable();
        }
        $data = $this->encodeForSQL();
        $reqId = $data['request_id'];
        $year = $this['year'];
        return $datatable->update(new \Flipside\Data\Filter("request_id eq '$reqId' and year eq $year"), $data);
    }

    static function getRequestByID($requestId, $year = false) {
        if($year === false)
        {
            $settings = \Tickets\DB\TicketSystemSettings::getInstance();
            $year = $settings['year'];
        }
        $dataTable = \Flipside\DataSetFactory::getDataTableByNames('tickets', 'TicketRequest');
        $filter = new \Flipside\Data\Filter("request_id eq '$requestId' and year eq $year");
        $requests = $dataTable->read($filter);
        if($requests === false || count($requests) === 0)
        {
            return false;
        }
        return new Request($requests[0]);
    }

    protected static function getDataTable()
    {
        return \Flipside\DataSetFactory::getDataTableByNames('tickets', 'TicketRequest');
    }
}
