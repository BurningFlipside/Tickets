<?php
require_once("class.FlipsideDB.php");
require_once('class.FlipsideTicketConstraints.php');
require_once('class.FlipsideDonationType.php');
require_once('class.FlipsideTicketRequest.php');
class FlipsideTicketDB extends FlipsideDB
{
    function getRequestIdForUser($uesr)
    {
        return FALSE;
    }

    function getNewRequestId()
    {
        return 'A0000001';
    }

    function getRequestForUser($user)
    {
        return FALSE;
    }

    function getFlipsideTicketConstraints()
    {
        $ret = new FlipsideTicketConstraints();
        //TODO - OPbtain from DB
        $ret->max_total_tickets = 4;
        $ret->ticket_types = array();
        $type = FlipsideTicketDB::getTicketTypeByType('A');
        array_push($ret->ticket_types, $type);
        $type = new FlipsideTicketType();
        $type->typeCode = 'T';
        $type->description = 'Teen (14 - 17)';
        $type->cost = 95.00;
        $type->max_per_request = 2;
        array_push($ret->ticket_types, $type);
        $type = new FlipsideTicketType();
        $type->typeCode = 'K';
        $type->description = 'Kid (7 - 13)';
        $type->cost = 35.00;
        $type->max_per_request = 2;
        array_push($ret->ticket_types, $type);
        $type = new FlipsideTicketType();
        $type->typeCode = 'C';
        $type->description = 'Child (0 - 6)';
        $type->cost = 0.00;
        $type->max_per_request = 2;
        array_push($ret->ticket_types, $type);
        return $ret; 
    }

    function getFlipsideDonationTypes()
    {
        //TODO - Get this from the SQL database
        $ret = array();
        $type = new FlipsideDonationType("Tinderbox");
        $type->entityName = "Tinderbox";
        $type->thirdParty = 1;
        $type->url = 'http://tinderbox.es';
        array_push($ret, $type);
        return $ret;
    }

    static function getTicketTypeByType($type)
    {
        $ret = new FlipsideTicketType();
        switch($type)
        {
            case 'A':
                $ret->typeCode = 'A';
                $ret->description = 'Adult';
                $ret->cost = 95.00;
                $ret->max_per_request = 2;
                break;
            case 'T':
                $ret->typeCode = 'A';
                $ret->description = 'Teen (14 - 17)';
                $ret->cost = 95.00;
                $ret->max_per_request = 2;
                break;
            case 'K':
                $ret->typeCode = 'A';
                $ret->description = 'Kid (7 - 13)';
                $ret->cost = 35.00;
                $ret->max_per_request = 2;
                break;
            case 'C':
                $ret->typeCode = 'A';
                $ret->description = 'Child (0 - 6)';
                $ret->cost = 0.00;
                $ret->max_per_request = 2;
                break;
        }
        return $ret;
    }
}
?>
