<?php
require_once("class.FlipsideTicketDB.php");
require_once("class.FlipsideTicketRequest.php");
require_once("class.FlipJax.php");
class RequestAjax extends FlipJaxSecure
{
    function get_count()
    {
        if(!$this->user_in_group("TicketAdmins") && !$this->user_in_group("TicketTeam"))
        {
            return array('err_code' => self::ACCESS_DENIED, 'reason' => "User must be a member of TicketAdmins or TicketTeam!");
        }
        $db = new FlipsideTicketDB();
        $count = $db->getRequestCount();
        if($count === FALSE)
        {
            return array('err_code' => self::INTERNAL_ERROR, 'reason' => "Failed to obtain request count!");
        }
        else
        {
            return array('count' => $count);
        }
    }

    function validate_user_can_read_id($id)
    {
        if($this->user_in_group("TicketAdmins") || $this->user_in_group("TicketTeam"))
        {
            return self::SUCCESS;
        }
        $my_id = FlipsideTicketRequest::getRequestId($this->user);
        if($my_id == FALSE)
        {
            return array('err_code' => self::INTERNAL_ERROR, 'reason' => "Failed to obtain request ID for user!");
        }
        if($my_id != $id)
        {
            return array('err_code' => self::ACCESS_DENIED, 'reason' => "User must be a member of TicketAdmins or TicketTeam to access someone else's request!");
        }
        return self::SUCCESS;
    }

    function get_specific_request($id, $year)
    {
        $res = $this->validate_user_can_read_id($id);
        if($res != self::SUCCESS)
        {
            return $res;
        }
        $db = new FlipsideTicketDB();
        $requests = FlipsideTicketRequest::select_from_db($db, 'request_id', $id);
        if($requests == FALSE)
        {
            //Still a success, they just have no requests
            return self::SUCCESS;
        }
        else
        {
            if(!is_array($requests))
            {
                $requests = array($requests);
            }
            if($year != null)
            {
                for($i = 0; $i < count($requests); $i++)
                {
                    if($requests[$i]->year == $year)
                    {
                        return array('requests' => array($requests[$i]));
                    }
                }
                //Still a success, they just have no requests for this year
                return self::SUCCESS;
            }
            else
            {
                return array('requests' => $requests);
            }
        }
    }

    function get_request_id()
    {
        $id = FlipsideTicketRequest::getRequestId($user);
        if($id == FALSE)
        {
            return array('err_code' => self::INTERNAL_ERROR, 'reason' => "Failed to obtain request ID for user!");
        }
        return array('id' => $id);
    }

    function get($params)
    {
        if(!$this->is_logged_in())
        {
            return array('err_code' => self::ACCESS_DENIED, 'reason' => "Not Logged In!");
        }
        if(isset($params['count']))
        {
            return $this->get_count();
        }
        else if(isset($params['request_id']))
        {
            if(!isset($params['year']))
            {
                $params['year'] = null;
            }
            return $this->get_specific_request($params['request_id'], $params['year']);
        }
        else if(isset($params['full']))
        {
            if($this->user == null)
            {
                $this->user = FlipSession::get_user(TRUE);
            }
            $id = FlipsideTicketRequest::getRequestId($this->user);
            if($id == FALSE)
            {
                return array('err_code' => self::INTERNAL_ERROR, 'reason' => "Failed to obtain request ID for user!");
            }
            return $this->get_specific_request($id, null);
        }
        else
        {
            return $this->get_request_id();
        }
    }

    function post_pdf($id, $year)
    {
        $res = $this->validate_user_can_read_id($id);
        if($res != self::SUCCESS)
        {
            return $res;
        }
        $request = new FlipsideTicketRequest($id, FALSE, $year);
        if($request == FALSE)
        {
            return array('err_code' => self::INTERNAL_ERROR, 'reason' => "Failed to obtain request!");
        }
        else
        {
            $file_name = $request->generatePDF();
            if($file_name == FALSE)
            {
                return array('err_code' => self::INTERNAL_ERROR, 'reason' => "Failed to generate PDF!");
            }
            else
            {
                $file_name = substr($file_name, 1);
                return array('pdf' => $file_name);
            }
        }
    }

    function post($params)
    {
        if(!$this->is_logged_in())
        {
            return array('err_code' => self::ACCESS_DENIED, 'reason' => "Not Logged In!");
        }
        if(isset($params['pdf']))
        {
            $res = $this->validate_params($params, array('request_id'=>'string', 'year'=>'int'));
            if($res == self::SUCCESS)
            {
                $res = $this->post_pdf($params['request_id'], $params['year']);
            }
            return $res;
        }
        else
        {
            $res = $this->validate_params($params, array('request_id'=>'string', 'givenName'=>'string', 'sn'=>'string', 'mail'=>'string', 'mobile'=>'string',
                                                         'c'=>'string', 'street'=>'string', 'zip'=>'string', 'l'=>'string', 'st'=>'string'));
            if($res == self::SUCCESS)
            {
                $db = new FlipsideTicketDB();
                $request = new FlipsideTicketRequest($params['request_id'], TRUE);
                $request->populateFromPOSTData($params);
                if(!isset($_POST['minor_confirm']) && $request->hasMinors())
                {
                    $res = array('need_minor_confirm' => '1');
                }
                else
                {
                    $request->modifiedBy = $user->uid[0];
                    $request->modifiedByIP = $_SERVER['REMOTE_ADDR'];
                    $request->replace_in_db($db);
                    $res = self::SUCCESS;
                }
            }
            return $res;
        }
    }
}

$ajax = new RequestAjax();
$ajax->run();
/* vim: set tabstop=4 shiftwidth=4 expandtab: */
?>
