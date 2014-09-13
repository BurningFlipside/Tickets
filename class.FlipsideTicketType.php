<?php
require_once('class.FlipsideTicketDB.php');
class FlipsideTicketType
{
    public $typeCode;
    public $description;
    public $cost;
    public $max_per_request;

    function __construct($type = null)
    {
        $this->typeCode = $type;
        if($type != null)
        {
            $data = FlipsideTicketDB::getTicketTypeByType($type);
            $this->description     = $data->description;
            $this->cost            = $data->cost;
            $this->max_per_request = $data->max_per_request;
        }
    }
}
?>
