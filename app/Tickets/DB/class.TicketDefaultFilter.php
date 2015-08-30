<?php
namespace Tickets\DB;

class TicketDefaultFilter extends \Data\Filter
{
    function __construct($email, $discretionary=false)
    {
        $this->children[] = new \Data\FilterClause();
        $this->children[0]->var1 = 'email';
        $this->children[0]->var2 = "'$email'";
        $this->children[0]->op = '=';
        $this->children[] = new \Data\FilterClause();
        $this->children[1]->var1 = 'discretionary';
        if($discretionary === false)
        {
            $this->children[1]->var2 = '0';
        }
        else
        {
            $this->children[1]->var2 = '1';
        }
        $this->children[1]->op = '=';
        $this->children[] = new \Data\FilterClause();
        $this->children[2]->var1 = 'used';
        $this->children[2]->var2 = '0';
        $this->children[2]->op = '=';
        $this->children[] = new \Data\FilterClause();
        $this->children[2]->var1 = 'year';
        $this->children[2]->var2 = TicketSystemSettings::getInstance()['year'];
        $this->children[2]->op = '=';
    }
}
?>
