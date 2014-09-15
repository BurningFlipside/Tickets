<?php
require_once('class.FlipsideTicketDB.php');
require_once('class.FlipsideDBObject.php');
class FlipsideTicketType extends FlipsideDBObject
{
    protected $_tbl_name = 'tblTicketTypes';

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
