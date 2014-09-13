<?php
require_once('class.FlipsideDonationType.php');
class FlipsideDonation
{
    public $type;
    public $amount;

    function __construct($type, $amount)
    {
        $this->type   = new FlipsideDonationType($type);
        $this->amount = $amount;
    }
}
?>
