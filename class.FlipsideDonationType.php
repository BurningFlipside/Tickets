<?php
require_once('class.FlipsideDBObject.php');
class FlipsideDonationType extends FlipsideDBObject
{
    protected $_tbl_name = 'tblDonationTypes';

    public $entityName;
    public $thirdParty;
    public $url;

    function __construct($name='')
    {
        $this->entityName = $name;
    }
}
?>
