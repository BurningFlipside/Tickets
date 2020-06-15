<?php
namespace Tickets\DB;

class TicketDefaultFilter extends \Flipside\Data\Filter
{
    function __construct($email, $discretionary=false)
    {
        $this->children[] = new \Flipside\Data\FilterClause();
        $this->children[0]->var1 = 'email';
        $this->children[0]->var2 = "'$email'";
        $this->children[0]->op = '=';
        $this->children[] = 'and';
        $clause = new \Flipside\Data\FilterClause();
        if($discretionary === false)
        {
            $clause->var1 = 'sold';
            $clause->var2 = '1';
            $clause->op   = '=';
        }
        else
        {
            $clause->var1 = 'discretionary';
            $clause->var2 = '1';
            $clause->op   = '=';
            $this->children[] = $clause;
            $this->children[] = 'and';
            $clause = new \Flipside\Data\FilterClause();
            $clause->var1 = 'sold';
            $clause->var2 = '0';
            $clause->op   = '=';
        }
        $this->children[] = $clause;
        $this->children[] = 'and';
        $clause = new \Flipside\Data\FilterClause();
        $clause->var1 = 'used';
        $clause->var2 = '0';
        $clause->op   = '=';
        $this->children[] = $clause;
        $this->children[] = 'and';
        $clause = new \Flipside\Data\FilterClause();
        $clause->var1 = 'year';
        $clause->var2 = TicketSystemSettings::getInstance()['year'];
        $clause->op   = '=';
        $this->children[] = $clause;
    }
}
?>
