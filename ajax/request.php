<?php
require_once("class.FlipsideTicketDB.php");
require_once("class.FlipsideTicketRequest.php");
require_once("class.FlipsideMailingListInfo.php");
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

    function get_specific_request($id, $year, $gen_bucket = FALSE)
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
            if($gen_bucket)
            {
                $requests[0]->genBucket();
            }
            if($year != null)
            {
                for($i = 0; $i < count($requests); $i++)
                {
                    if($requests[$i]->year == $year)
                    {
                        $requests[$i]->status = $requests[$i]->get_status_info($db);
                        return array('requests' => array($requests[$i]));
                    }
                }
                //Still a success, they just have no requests for this year
                return self::SUCCESS;
            }
            else
            {
                for($i = 0; $i < count($requests); $i++)
                {
                    $requests[$i]->status = $requests[$i]->get_status_info($db);
                }
                return array('requests' => $requests);
            }
        }
    }

    function get_request_id()
    {
        $id = FlipsideTicketRequest::getRequestId(FlipSession::get_user());
        if($id == FALSE)
        {
            return array('err_code' => self::INTERNAL_ERROR, 'reason' => "Failed to obtain request ID for user!");
        }
        return array('id' => $id);
    }

    function get_search_requests($type, $value)
    {
        if($value == '')
        {
            return self::SUCCESS;
        }
        $requests = FlipsideTicketRequest::searchForRequests($type, $value);
        if($requests == FALSE)
        {
            //No results is ok...
            return self::SUCCESS;
        }
        if(!is_array($requests))
        {
            $requests = array($requests);
        }
        return array('requests'=>$requests);
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
            return $this->get_specific_request($params['request_id'], $params['year'], isset($params['genbucket']));
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
        else if(isset($params['all']))
        {
            if(!$this->user_in_group("TicketAdmins") && !$this->user_in_group("TicketTeam"))
            {
                return array('err_code' => self::ACCESS_DENIED, 'reason' => "User must be a member of TicketAdmins or TicketTeam!");
            }
            $data = FlipsideTicketRequest::getAll($params['all']);
            if($data == FALSE)
            {
                return array('err_code' => self::INTERNAL_ERROR, 'reason' => "Failed to obtain requests!");
            }
            return array('data'=>$data);
        }
        else if(isset($params['tickets']))
        {
            if(!$this->user_in_group("TicketAdmins") && !$this->user_in_group("TicketTeam"))
            {
                return array('err_code' => self::ACCESS_DENIED, 'reason' => "User must be a member of TicketAdmins or TicketTeam!");
            }
            $data = FlipsideTicketRequestTicket::getAll($params['tickets']);
            if($data == FALSE)
            {
                return array('err_code' => self::INTERNAL_ERROR, 'reason' => "Failed to obtain requested tickets!");
            }
            return array('data'=>$data);
        }
        else if(isset($params['type']) && isset($params['value']))
        {
            return $this->get_search_requests($params['type'], $params['value']);
        }
        else if(isset($params['meta']))
        {
            if(!$this->user_in_group("TicketAdmins"))
            {
                return array('err_code' => self::ACCESS_DENIED, 'reason' => "User must be a member of TicketAdmins!");
            }
            $data = FlipsideTicketRequest::getMetaData();
            if($data == FALSE)
            {
                return array('err_code' => self::INTERNAL_ERROR, 'reason' => "Failed to obtain requests!");
            }
            return array('data'=>$data);
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
        $request = FlipsideTicketRequest::get_request_by_id_and_year($id, $year);
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
                $file_name = substr($file_name, strpos($file_name, 'tmp/'));
                return array('pdf' => $file_name);
            }
        }
    }

    function post_email($id, $year)
    {
        $res = $this->validate_user_can_read_id($id);
        if($res != self::SUCCESS)
        {
            return $res;
        }
        $request = FlipsideTicketRequest::get_request_by_id_and_year($id, $year);
        if($request == FALSE)
        {
            return array('err_code' => self::INTERNAL_ERROR, 'reason' => "Failed to obtain request!");
        }
        else
        {
            $mail = $request->sendEmail();
            if($mail == FALSE)
            {
                return array('err_code' => self::INTERNAL_ERROR, 'reason' => "Failed to send Email!");
            }
            else
            {
                return array('mail' => $mail);
            }
        }
    }

    function post_set_crit($id)
    {
        if(!$this->user_in_group("TicketAdmins"))
        {
            return array('err_code' => self::ACCESS_DENIED, 'reason' => "User must be a member of TicketAdmins!");
        } 
        $request = FlipsideTicketRequest::get_request_by_id_and_year($id);
        if($request == FALSE)
        {
            return array('err_code' => self::INTERNAL_ERROR, 'reason' => "Failed to obtain request!");
        }
        else
        {
             $db = new FlipsideTicketDB();
             $request->crit_vol = true;
             $request->replace_in_db($db);
        }
    }

    function post_unset_crit($id)
    {
        if(!$this->user_in_group("TicketAdmins"))
        {
            return array('err_code' => self::ACCESS_DENIED, 'reason' => "User must be a member of TicketAdmins!");
        }
        $request = FlipsideTicketRequest::get_request_by_id_and_year($id, FALSE);
        if($request == FALSE)
        {
            return array('err_code' => self::INTERNAL_ERROR, 'reason' => "Failed to obtain request!");
        }
        else
        {
             $db = new FlipsideTicketDB();
             $request->crit_vol = false;
             $request->replace_in_db($db);
        }
    }

    function post_data_entry($params)
    {
        if(!$this->user_in_group("TicketAdmins") && !$this->user_in_group("TicketTeam"))
        {
            return array('err_code' => self::ACCESS_DENIED, 'reason' => "User must be a member of TicketAdmins or TicketTeam!");
        }
        $id = '';
        if(isset($params['id']))
        {
            $id = $params['id'];
        }
        else
        {
            $id = $params['request_id'];
        }
        $request = FlipsideTicketRequest::get_request_by_id_and_year($id);
        if($request == FALSE)
        {
            return array('err_code' => self::INTERNAL_ERROR, 'reason' => "Failed to obtain request!");
        }
        $request->total_received = $params['total_received'];
        $request->private_status = $params['status'];
        $request->comments = $params['comments'];
        $db = new FlipsideTicketDB();
        if($request->replace_in_db($db) === FALSE)
        {
            return array('err_code' => self::INTERNAL_ERROR, 'reason' => "Failed to save request!");
        }
        return self::SUCCESS;
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
        if(isset($params['email']))
        {
            $res = $this->validate_params($params, array('request_id'=>'string', 'year'=>'int'));
            if($res == self::SUCCESS)
            {
                $res = $this->post_email($params['request_id'], $params['year']);
            }
            return $res;
        }
        else if(isset($params['set_crit']))
        {
            return $this->post_set_crit($params['set_crit']);
        }
        else if(isset($params['unset_crit']))
        {
            return $this->post_unset_crit($params['unset_crit']);
        }
        else if(isset($params['dataentry']))
        {
            return $this->post_data_entry($params);
        }
        else
        {
            $res = $this->validate_params($params, array('request_id'=>'string', 'givenName'=>'string', 'sn'=>'string', 'mail'=>'string', 'mobile'=>'string',
                                                         'c'=>'string', 'street'=>'string', 'zip'=>'string', 'l'=>'string', 'st'=>'string'));
            if($res == self::SUCCESS)
            {
                $db = new FlipsideTicketDB();
                $request = new FlipsideTicketRequest();
                $request->populateFromPOSTData($params);
                if(!isset($_POST['minor_confirm']) && $request->hasMinors())
                {
                    $res = array('need_minor_confirm' => '1');
                }
                else
                {
                    $request->modifiedBy = FlipSession::get_user()->uid[0];
                    $request->modifiedByIP = $_SERVER['REMOTE_ADDR'];
                    $request->replace_in_db($db);
                    $request->sendEmail();
                    $res = self::SUCCESS;
                    foreach($params as $key => $value)
                    {
                        $exp_key = explode('_', $key);
                        if($exp_key[0] == 'list')
                        {
                            FlipsideMailingListInfo::SaveToFile($exp_key[1], $params['mail']);
                        }
                    }
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
