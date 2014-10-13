<?php
class FlipsideMailingListInfo
{
    public $short_name;
    public $name;
    public $description;
    public $request_condition;

    static function GetAllMailingListInfo()
    {
        $ret = array();
        $list = new FlipsideMailingListInfo();
        $list->short_name = 'austin-announce';
        $list->name = 'Austin Announce';
        $list->description = "This is the most important list to be on. It is a low traffic email list (10-20 emails per year) and covers only the most important Flipside announcements. Stuff like when Tickets are going on sale, new important policies, announcements important to you even if you're not going this year, etc.";
        $list->request_condition = '1';
        array_push($ret, $list);

        $list = new FlipsideMailingListInfo();
        $list->short_name = 'flipside-parents';
        $list->name = 'Flipside Parents';
        $list->description = "This is a list for parents of minor children who are attending Flipside. important announcements relavent to parents will be posted to this list. Any parents of minor children attending the event should subscribe to this list.";
        $list->request_condition = 'C > 0 || K > 0 || T > 0';
        array_push($ret, $list);
        return $ret;
    }
}
?>
