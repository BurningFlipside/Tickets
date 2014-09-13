<?php
class FlipsideDonationType
{
    public $entityName;
    public $thirdParty;
    public $url;

    function __construct($name)
    {
        $this->entityName = $name;
    }
}
?>
