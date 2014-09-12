<?php
require_once("class.FlipsideDB.php");
require_once('class.FlipsideTicketConstraints.php');
require_once('class.FlipsideDonationType.php');
require_once('class.FlipsideTicketRequest.php');
class FlipsideTicketDB extends FlipsideDB
{
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
        $type = new FlipsideTicketType();
        $type->typeCode = 'A';
        $type->description = 'Adult';
        $type->cost = 95.00;
        $type->max_per_request = 2;
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
        $type = new FlipsideDonationType();
        $type->entityName = "Tinderbox";
        $type->thirdParty = 1;
        $type->url = 'http://tinderbox.es';
        array_push($ret, $type);
        return $ret;
    }
}
?>
