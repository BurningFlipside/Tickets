<?php
namespace Tickets\Flipside;

class FlipsideRequestDefaultFilter extends \Data\Filter
{
    function __construct($request_id, $year=false)
    {
        $this->children[] = new \Data\FilterClause();
        $this->children[0]->var1 = 'request_id';
        $this->children[0]->var2 = "'$request_id'";
        $this->children[0]->op = '=';
        if($year !== false)
        {
            $this->children[] = 'and';
            $this->children[] = new \Data\FilterClause();
            $this->children[2]->var1 = 'year';
            $this->children[2]->var2 = $year;
            $this->children[2]->op = '=';
        }
    }
}
?>
