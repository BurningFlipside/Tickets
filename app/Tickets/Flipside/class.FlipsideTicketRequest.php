<?php
namespace Tickets\Flipside;

class FlipsideTicketRequest extends \SerializableObject
{
    function __construct($data = false)
    {
        if($data !== false)
        {
            foreach($data as $key=>$value)
            {
                $this->$key = $value;
            }
        }
    }

    static function createTicketRequest($request)
    {
         $settings = \Tickets\DB\TicketSystemSettings::getInstance();
         if(!isset($request->year))
         {
              $request->year = $settings['year'];
         }
         $request->test = $settings['test_mode'];
         $filter = new FlipsideRequestDefaultFilter($request->request_id, $request->year);
         $dataSet = \DataSetFactory::get_data_set('tickets');
         $requestDataTable = $dataSet['TicketRequest'];
         $donationDataTable = $dataSet['RequestDonation'];
         $requestedTicketDataTable = $dataSet['RequestedTickets'];
         $requests = $requestDataTable->read($filter);
         if($requests !== false && isset($requests[0]))
         {
             return static::updateRequest($request, $requests[0]);
         }
         $request->total_due = 0;
         if(isset($request->donations) && count((array)$request->donations) > 0)
         {
             $donations = (array)$request->donations;
             foreach($donations as $key=>$value)
             {
                 if($value->amount > 0)
                 {
                     $array = array();
                     $array['request_id'] = $request->request_id;
                     $array['year']       = $request->year;
                     $array['type']       = $key;
                     $array['amount']     = $value->amount;
                     if(isset($value->disclose))
                     {
                         $array['disclose'] = 1;
                     }
                     else
                     {
                         $array['disclose'] = 0;
                     }
                     $array['test']       = $request->test;
                     $donationDataTable->create($array);
                     $request->total_due += $value->amount;
                 }
             }
             unset($donations); unset($request->donations);
         }
         if(isset($request->tickets))
         {
             $count = count($request->tickets);
             for($i = 0; $i < $count; $i++)
             {
                 $array = array();
                 $array['request_id'] = $request->request_id;
                 $array['year']       = $request->year;
                 $array['first']      = $request->tickets[$i]->first;
                 $array['last']       = $request->tickets[$i]->last;
                 $array['type']       = $request->tickets[$i]->type;
                 $array['test']       = $request->test;
                 $requestedTicketDataTable->create($array);
                 $request->total_due += \Tickets\TicketType::getCostForType($request->tickets[$i]->type);
             }
             unset($request->tickets);
         }
         if(isset($request->lists) && count((array)$request->lists) > 0)
         {
             //TODO Email lists
             unset($request->lists);
         }
         return $requestDataTable->create((array)$request);
    }

    static function updateRequest($new_request, $old_request)
    {
        print_r($new_request);
        print_r($old_request);
        die();
    }

    static function getByIDAndYear($request_id, $year)
    {
         $filter = new FlipsideRequestDefaultFilter($request_id, $year);
         $dataSet = \DataSetFactory::get_data_set('tickets');
         $requestDataTable = $dataSet['TicketRequest'];
         $donationDataTable = $dataSet['RequestDonation'];
         $requestedTicketDataTable = $dataSet['RequestedTickets'];
         $requests = $requestDataTable->read($filter);
         if($requests !== false && isset($requests[0]))
         {
             $requests[0]['tickets']   = $requestedTicketDataTable->read($filter);
             $requests[0]['donations'] = $donationDataTable->read($filter);
             return new static($requests[0]);
         }
         throw new \Exception('Not found');
    }
}
?>
