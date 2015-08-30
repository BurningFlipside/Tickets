<?php
namespace Tickets\DB;

class TicketHashFilter extends \Data\Filter
{
    function __construct($hash)
    {
        $this->children[] = new \Data\FilterClause();
        $this->children[0]->var1 = 'hash';
        $this->children[0]->var2 = "'$hash'";
        $this->children[0]->op = '=';
    }
}
?>
