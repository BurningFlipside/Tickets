<?php
require_once('class.FlipsideTicketType.php');
class FlipsideTicketRequestTicket
{
    public $first;
    public $last;
    public $type;

    function __construct($data)
    {
        $this->first = $data['first'];
        $this->last  = $data['last'];
        $this->type  = new FlipsideTicketType($data['type']);
    }

    function getCost()
    {
        return $this->type->cost;
    }

    function getTypeName()
    {
        return $this->type->description;
    }
}
?>
