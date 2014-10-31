<?php
require_once('class.FlipsideDBObject.php');
require_once('class.FlipsideTicketDB.php');
class Ticket extends FlipsideDBObject
{
    protected $_tbl_name = 'tblTickets';
    protected $_sql_ignore = array('last_updated_dt');

    public $hash;
    public $year;
    public $firstName;
    public $lastName;
    public $email;
    public $request_id;
    public $assigned;
    public $void;
    public $sold;
    public $used;
    public $discretionary;
    public $type;
    public $guardian_first;
    public $guardian_last;
    public $pool_id;
    public $previous_hash;
    public $physical_ticket_id;
    public $comments;
    public $last_updated_by;
    public $last_updated_dt;
    public $last_updated_ip;
    public $used_dt;
    public $rand;
    public $test;

    function __construct()
    {
        $this->assigned = 0;
        $this->void = 0;
        $this->sold = 0;
        $this->used = 0;
        $this->discretionary = 0;
        $this->pool_id = -1;
    }

    function generate_hash($db)
    {
        do
        {
            $this->rand = mt_rand();
            $this->hash = hash('haval128,5', $this->year.$this->firstName.$this->lastName.$this->uid.$this->type.$this->previous_hash.$this->rand);
        } while(self::hash_exists($this->hash, $db));
    }

    function insert_to_db($db)
    {
        if($db->getVariable('test_mode'))
        {
            $this->test = 1;
        }
        if($this->hash === FALSE || $this->hash === null)
        {
            $this->generate_hash($db);
        }
        else
        {
            $this->previous_hash = $this->hash;
            $this->generate_hash($db);
        }
        return parent::insert_to_db($db);
    }

    function replace_in_db($db)
    {
        if($db->getVariable('test_mode'))
        {
            $this->test = 1;
        }
        return parent::replace_in_db($db);
    }

    static function create_new($count, $type='', $db=FALSE, $flush = TRUE)
    {
        if($db == FALSE)
        {
            $db = new FlipsideTicketDB();
        }
        $res = array();
        for($i = 0; $i < $count; $i++)
        {
            $ticket = new Ticket();
            $ticket->year = $db->getVariable('year');
            $ticket->type = $type;
            if($flush)
            {
                $ticket->insert_to_db($db);
            }
            array_push($res, $ticket);
        }
        return $res;
    }

    static function hash_exists($hash, $db)
    {
        $res = $db->select('tblTickets', 'hash', array('hash'=>'=\''.$hash.'\''));
        if($res === FALSE || !isset($res[0]) || !isset($res[0]['hash']))
        {
            return false;
        }
        return true;
    }

    static function get_tickets_for_user($user)
    {
        $db = new FlipsideTicketDB();
        $res = self::select_from_db($db, 'email', $user->mail[0]);
        if($res === FALSE)
        {
            return FALSE;
        }
        else if(!is_array($res))
        {
            $res = array($res);
        }
        return $res;
    }

    static function getAll($year = FALSE)
    {
        $db = new FlipsideTicketDB();
        if($year === FALSE)
        {
            $year = $db->getVariable('year');
        }
        $type = self::select_from_db($db, 'year', $year);
        if($type == FALSE)
        {
            return FALSE;
        }
        if(!is_array($type))
        {
            $type = array($type);
        }
        return $type;
    }

    static function hash_to_words($hash)
    {
        require_once("static.words.php");
        $res = substr($hash, 0, 8);
        $remainder = gmp_init(substr($hash, 8), 16);
        $my_words = '';
        while(gmp_intval($remainder) > 0)
        {
            $pos = gmp_mod($remainder, 4096);
            $my_words = $words[gmp_intval($pos)].' '.$my_words;
            $remainder = gmp_div($remainder,4096);
        }
        return $res.' '.$my_words;
    }
}
?>
