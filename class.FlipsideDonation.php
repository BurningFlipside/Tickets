<?php
require_once('class.FlipsideDonationType.php');
class FlipsideDonation
{
    public $donation_id;
    public $request_id;
    public $year;
    public $type;
    public $amount;
    public $disclose;

    function __construct($type, $data)
    {
        $this->type       = new FlipsideDonationType($type);
        $this->request_id = $data['request_id'];
        $this->year       = $data['year'];
        $this->amount     = $data['amount'];
        if(isset($data['disclose']))
        {
            if($data['disclose'] == 'on')
            {
                $this->disclose = TRUE;
            }
            else
            {
                $this->disclose = $data['disclose'];
            }
        }
        else
        {
            $this->disclose = FALSE;
        }
    }
}
?>
