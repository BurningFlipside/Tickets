<?php
namespace Tickets\DB;

class TicketHashFilter extends \Flipside\Data\Filter
{
    function __construct($hash)
    {
        $this->children[] = new \Flipside\Data\FilterClause();
        $this->children[0]->var1 = 'hash';
        $this->children[0]->var2 = "'$hash'";
        $this->children[0]->op = '=';
    }
}
?>
