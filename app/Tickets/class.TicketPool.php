<?php
namespace Tickets;

use \Flipside\Data\Filter as DataFilter;
use \Tickets\DB\TicketSystemSettings;

class TicketPool
{
    /**
     * Get Tickets for sell by either the pool id or user
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public static function getTicketsByPoolAndUser(int $poolId, \Flipside\Auth\User $user, int $qty, string $typeCode) : array
    {
        $year = TicketSystemSettings::getYear();
        if($poolId === -1)
        {
            return Ticket::getDiscretionaryTicketsForUser($user, new DataFilter("type eq '$typeCode' and transferInProgress eq 0"), $qty);
        }
        $filter = new DataFilter("sold eq 0 and type eq '$typeCode' and pool_id eq ".$poolId."  and transferInProgress eq 0 and year eq ".$year);
        return Ticket::get_tickets($filter, false, $qty);
    }
}