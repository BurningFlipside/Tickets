<?php
class SecondaryAPI extends Http\Rest\RestAPI
{
    protected $expectedAnswerCount = 5;

    public function setup($app)
    {
        $app->get('/questions',  array($this, 'getQuestions'));
        $app->post('/questions/answers',  array($this, 'answerQuestions'));
        $app->get('/requests',  array($this, 'getSecondaryRequests'));
        $app->post('/requests',  array($this, 'makeNewSecondaryRequest'));
        $app->patch('/requests/{request_id}/{year}',  array($this, 'updateSecondaryRequest'));
        $app->post('/requests/{request_id}/{year}/Actions/Ticket',  array($this, 'ticketSecondaryRequest'));
        $app->get('/{request_id}/{year}/pdf',  array($this, 'getSecondaryPdf'));
    }

    protected function addAnswerSpace($body, $format, $id)
    {
        if($format === 'bool')
        {
            $body->write('<select id="answer['.$id.']" name="answer['.$id.']" class="form-control"><option value=""></option>><option value="true">True</option><option value="false">False</option></select>');
        }
        else
        {
            $body->write('<input class="form-control" id="answer['.$id.']" name="answer['.$id.']" type="'.$format.'">');
        }
    }

    public function getQuestions($request, $response, $args)
    {
        $this->validateLoggedIn($request);
        $questionDataTable = DataSetFactory::getDataTableByNames('tickets', 'Questions');
        $count = $questionDataTable->count(false);
        if($count < $expectedAnswerCount)
        {
            $expectedAnswerCount = $count;
        }
        $ids = array();
        for($i = 0; $i < $expectedAnswerCount; $i++)
        {
            $value = rand(0, $count-1);
            if(in_array($value, $ids))
            {
                $i--;
                continue;
            }
            $ids[] = $value;
        }
        $questions = $questionDataTable->raw_query('SELECT * from tblQuestions WHERE id IN ('.implode(',', $ids).');');
        $count = count($questions);
        for($i = 0; $i < $count; $i++)
        {
            unset($questions[$i]['answers']);
            $questions[$i]['answerFormat'] = json_decode($questions[$i]['answerFormat']);
        }
        \FlipSession::setVar('questionIDs', $ids);
        $format = $request->getAttribute('format');
        if($format === 'html')
        {
            $response = $response->withHeader('Content-Type', 'text/html');
            $body = $response->getBody();
            for($i = 0; $i < $count; $i++)
            {
                $body->write('<div class="clearfix visible-sm visible-md visible-lg"></div><div class="form-group">');
                $body->write('    <label class="col-sm-12 control-label">'.$questions[$i]['displayString'].'</label>');
                $body->write('    <div class="col-sm-10">');
                if(count($questions[$i]['answerFormat']) === 1)
                {
                    $this->addAnswerSpace($body, $questions[$i]['answerFormat'][0], $i);
                }
                else
                {
                    for($j = 0; $j < count($questions[$i]['answerFormat']); $j++)
                    {
                        $this->addAnswerSpace($body, $questions[$i]['answerFormat'][$j], $i.']['.$j);
                    }
                }
                $body->write('    </div>');
                $body->write('</div>');
            } 
        }
        else
        {
            $response = $response->withJson($questions);
        }
        return $response;
    }

    public function answerQuestions($request, $response, $args)
    {
        $this->validateLoggedIn($request);
        $data = (array)$request->getParsedBody();
        if(!isset($data['g-recaptcha-response']))
        {
            return $response->withJson(array('err'=>'Missing CAPTCHA!'));
        }
        $settings = \Settings::getInstance();
        $key = $settings->getGlobalSetting('captcha_secret');
        $resp = \Httpful\Request::post('https://www.google.com/recaptcha/api/siteverify?secret='.$key.'&response='.$data['g-recaptcha-response'])->send();
        if($resp->body->success != true)
        {
            return $response->withJson('err'=>'Sorry Google thinks you are a robot!'));
        }
        unset($data['g-recaptcha-response']);
        $ids = \FlipSession::getVar('questionIDs');
        $questionDataTable = DataSetFactory::getDataTableByNames('tickets', 'Questions');
        $wrongDataTable = DataSetFactory::getDataTableByNames('tickets', 'WrongAnswers');
        $questions = $questionDataTable->raw_query('SELECT * from tblQuestions WHERE id IN ('.implode(',', $ids).');');
        $count = count($questions);
        if(!isset($data['answer']))
        {
            return $response->withJson(array('err'=>'Missing answers!'));
        }
        $answers = $data['answer'];
        if($count != count($answers))
        {
            return $response->withJson(array('err'=>'Missing some answers!'));
        }
        for($i = 0; $i < $count; $i++)
        {
            $question = $questions[$i];
            $answer = $answers[$i];
            $question['answerFormat'] = json_decode($question['answerFormat']);
            $question['answers'] = json_decode($question['answers']);
            if(count($question['answerFormat']) === 1)
            {
                //Single Answer
                if($question['answerFormat'][0] === 'bool')
                {
                    if($this->verifyBoolAnswer($question['answers'][0], $answer) === false)
                    {
                        $wrongDataTable->create(array('uid'=>$app->user->uid, 'IDs'=>implode(',', $ids), 'Answers'=>json_encode($answers)));
                        return $response->withJson(array('err'=>'Invalid answer!', 'wrong'=>$i));
                    }
                }
                else
                {
                    if($this->verifyTextAnswer($question['answers'], $answer) === false)
                    {
                        $wrongDataTable->create(array('uid'=>$app->user->uid, 'IDs'=>implode(',', $ids), 'Answers'=>json_encode($answers)));
                        return $response->withJson(array('err'=>'Invalid answer! If you repeatedly see this error and are sure your answers are correct, please email tickets@burningflipside.com with a screenshot.', 'wrong'=>$i));
                    }
                }
            }
            else
            {
                //Multiple Answers
                $countAnswers = count($question['answerFormat']);
                if($countAnswers !== count($answer))
                {
                    return $response->withJson(array('err'=>'Missing parts of a question!'));
                }
                //Currently assume all answers are text...
                for($j = 0; $j < $countAnswers; $j++)
                {
                    if($this->verifyTextAnswer($question['answers'], $answer[$j]) === false)
                    {
                        $wrongDataTable->create(array('uid'=>$app->user->uid, 'IDs'=>implode(',', $ids), 'Answers'=>json_encode($answers)));
                        return $response->withJson(array('err'=>'Invalid answer!', 'wrong'=>$i));
                    }
                }
            }
        }
        $key = bin2hex(openssl_random_pseudo_bytes(32));
        \FlipSession::setVar('secondaryRequestID', $key);
        return $response->withJson(array('success'=>true, 'access_key'=>$key));
    }

    protected function verifyBoolAnswer($correctAnswer, $answer)
    {
        $boolAnswer = ($answer === 'true');
        return $correctAnswer === $boolAnswer;
    }

    protected function verifyTextAnswer(&$correctAnswers, $answer)
    {
        $count = count($correctAnswers);
        for($i = 0; $i < $count; $i++)
        {
            if(strcasecmp($correctAnswers[$i], trim($answer)) === 0)
            {
                array_splice($correctAnswers, $i, 1);
                return true;
            }
        }
        return false;
    }

    public function getSecondaryRequests($request, $response, $args)
    {
        $this->validateLoggedIn($request);
        $requestDataTable = DataSetFactory::getDataTableByNames('tickets', 'SecondaryRequests');
        $filter = false;
        $odata = $request->getAttribute('odata', new \ODataParams(array()));
        if($app->user->isInGroupNamed('TicketAdmins') && $odata->filter !== false)
        {
            $filter = $odata->filter;
            if($filter->contains('year eq current'))
            {
                $settings = \Tickets\DB\TicketSystemSettings::getInstance();
                $clause = $filter->getClause('year'); 
                $clause->var2 = $settings['year'];
            }
        }
        else
        {
            $filter = new \Data\Filter('mail eq \''.$app->user->mail.'\'');
        }
        $search = $request->getQueryParam('$search');
        if($search !== null && $app->user->isInGroupNamed('TicketAdmins'))
        {
            $filter->addToSQLString(" AND (mail LIKE '%$search%' OR sn LIKE '%$search%' OR givenName LIKE '%$search%')");
        }
        $requests = $requestDataTable->read($filter, $odata->select, $odata->top, $odata->skip, $odata->orderby);
        if($requests === false)
        {
            $requests = array();
        }
        else if(!is_array($requests))
        {
            $requests = array($requests);
        }
        if($odata->count)
        {
            $requests = array('@odata.count'=>count($requests), 'value'=>$requests);
        }
        return $response->withJson($requests);
    }

    public function makeNewSecondaryRequest($request, $response, $args)
    {
        $this->validateLoggedIn($request);
        $data = (array)$request->getParsedBody();
        $data['total_due'] = 0;
        $data['valid_tickets'] = array();
        $key = \FlipSession::getVar('secondaryRequestID', false);
        if($key === false)
        {
            return $response->withJson(array('err'=>'You skipped the question section!'));
        }
        $settings = \Tickets\DB\TicketSystemSettings::getInstance();
        $ticketDataTable = \Tickets\DB\TicketsDataTable::getInstance();
        $ticketTypeDataTable = \DataSetFactory::getDataTableByNames('tickets', 'TicketTypes');
        $secondaryTable = \DataSetFactory::getDataTableByNames('tickets', 'SecondaryRequests');
        $maxTotalTickets = $settings['max_tickets_per_request'];
        $currentTickets = $ticketDataTable->read(new \Tickets\DB\TicketDefaultFilter($app->user->mail));
        $numberOfCurrentTickets = count($currentTickets);
        if($currentTickets === false)
        {
            $numberOfCurrentTickets = 0;
        }
        if($numberOfCurrentTickets >= $maxTotalTickets)
        {
            return $response->withJson(array('err'=>'You already have too many tickets for a secondary request!'));
        }
        $ticketTypes = $ticketTypeDataTable->read();
        $ticketTypeCount = count($ticketTypes);
        for($i = 0; $i < $ticketTypeCount; $i++)
        {
            $type = $ticketTypes[$i];
            $type['count'] = 0;
            $ticketTypes[$type['typeCode']] = $type;
            unset($ticketTypes[$i]);
        }
        if($numberOfCurrentTickets != 0)
        {
            for($i = 0; $i < $numberOfCurrentTickets; $i++)
            {
                $ticket = $currentTickets[$i];
                $ticketTypes[$ticket['type']]['count']++;
                if($ticketTypes[$ticket['type']]['count'] >= $ticketTypes[$ticket['type']]['max_per_request'])
                {
                    if(isset($data['ticket_first_'.$ticket['type'].'_1']) || isset($data['ticket_first_'.$ticket['type'].'_2']))
                    {
                        return $response->withJson(array('err'=>'You already have requested a ticket type which you already have the maximum number of.'));
                    }
                }
                if($ticketTypes[$ticket['type']]['count'] >= ($ticketTypes[$ticket['type']]['max_per_request'] - 1))
                {
                    if(isset($data['ticket_first_'.$ticket['type'].'_2']))
                    {
                        return $response->withJson(array('err'=>'You already have requested two many of a ticket type which you already have some of.'));
                    }
                }
            }
        }
        $requests = $secondaryTable->read(new \Data\Filter('mail eq "'.$app->user->mail.'"'));
        if(!empty($requests))
        {
            return $response->withJson(array('err'=>'You already have a secondary request!'));
        }
        $data['year'] = $settings['year'];
        $data['request_id'] = $key;
        foreach($data as $prop=>$value)
        {
            if(strncmp($prop, 'ticket_last', 11) === 0)
            {
                $parts = explode('_', $prop);
                $type = $ticketTypes[$parts[2]];
                $data['total_due'] += $type['cost'];
                array_push($data['valid_tickets'], $parts[2].'_'.$parts[3]);
            }
        }
        $data['valid_tickets'] = json_encode($data['valid_tickets']);
        $res = $secondaryTable->create($data);
        if($res === false)
        {
            return $response->withJson(array('err'=>'Internal error! Try again later.'));
        }
        return $response->withJson(array('uri'=>'api/v1/secondary/'.$key.'/'.$settings['year'].'/pdf'));
    }

    public function updateSecondaryRequest($request, $response, $args)
    {
        $this->validateLoggedIn($request);
        $request_id = $args['request_id'];
        $year = $args['year'];
        $settings = \Tickets\DB\TicketSystemSettings::getInstance();
        $secondaryTable = \DataSetFactory::getDataTableByNames('tickets', 'SecondaryRequests');
        if($year === 'current')
        {
            $year = $settings['year'];
        }
        $filter = new \Data\Filter('request_id eq "'.$request_id.'" and year eq '.$year);
        if($request_id === 'me')
        {
            $filter = new \Data\Filter('mail eq "'.$app->user->mail.'" and year eq '.$year);
        }
        $request = $secondaryTable->read($filter);
        if(empty($request))
        {
            return $response->withStatus(404);
        }
        $request = $request[0];
        $obj = $request->getParsedBody();
        $ret = $secondaryTable->update($filter, $obj);
        return $response->withJson($ret);
    }

    public function ticketSecondaryRequest($request, $response, $args)
    {
        $this->validateLoggedIn($request);
        $request_id = $args['request_id'];
        $year = $args['year'];
        if($this->user->isInGroupNamed('TicketAdmins') === false)
        {
            return $response->withStatus(401);
        }
        $settings = \Tickets\DB\TicketSystemSettings::getInstance();
        $secondaryTable = \DataSetFactory::getDataTableByNames('tickets', 'SecondaryRequests');
        if($year === 'current')
        {
            $year = $settings['year'];
        }
        $filter = new \Data\Filter('request_id eq "'.$request_id.'" and year eq '.$year);
        if($request_id === 'me')
        {
            $filter = new \Data\Filter('mail eq "'.$app->user->mail.'" and year eq '.$year);
        }
        $request = $secondaryTable->read($filter);
        if(empty($request))
        {
            return $response->withStatus(404);
        }
        $request = $request[0];
        $valid_tickets = json_decode($request['valid_tickets']);
        $count = count($valid_tickets);
        $email = $request['mail'];
        $fails = array();
        for($i = 0; $i < $count; $i++)
        {
            $first = $request['ticket_first_'.$valid_tickets[$i]];
            $last = $request['ticket_last_'.$valid_tickets[$i]];
            $type = $valid_tickets[$i][0];
            $res = \Tickets\Ticket::do_sale($app->user, $email, array($type=>1), false, $first, $last, 1);
            if($res === false)
            {
                array_push($fails, array('first'=>$first, 'last'=>$last, 'type'=>$type, 'email'=>$email));
            }
        }
        if(empty($fails))
        {
            $request['ticketed'] = 1;
            $secondaryTable->update($filter, $request);
            return $response->withJson(true);
        }
        return $response->withJson(array('fails'=>$fails));
    }

    public function getSecondaryPdf($request, $response, $args)
    {
        $this->validateLoggedIn($request);
        $request_id = $args['request_id'];
        $year = $args['year'];
        $settings = \Tickets\DB\TicketSystemSettings::getInstance();
        $secondaryTable = \DataSetFactory::getDataTableByNames('tickets', 'SecondaryRequests');
        if($year === 'current')
        {
            $year = $settings['year'];
        }
        $filter = new \Data\Filter('request_id eq "'.$request_id.'" and year eq '.$year);
        if($request_id === 'me')
        {
            $filter = new \Data\Filter('mail eq "'.$app->user->mail.'" and year eq '.$year);
        }
        $request = $secondaryTable->read($filter);
        if(empty($request))
        {
            return $response->withStatus(404);
        }
        $request = $request[0];
        $pdf = new \PDF\PDF();
        $html = '<style type="text/css">body {
            font-family: helvetica, arial, sans-serif;
            font-size: 12px;
        }
        .h3 {
            font-size: 1em;
margin: 1em 0 .25em 0;
        font-style: italic;
        }
        .h3+* {
            margin-top: 0;
        }
        .sidehead {
width: 16%; 
       font-weight: bold; 
       text-align: right; 
       vertical-align: top;
padding: 0 1em 0 0;
         font-weight: bold; 
         font-size: 1em;
        }
        .contactlabel {
width: 16%; 
       text-align: right; 
       vertical-align: top;
padding: 0 1em 0 0;
        }
        .contactinfo {
            text-align: left; 
            vertical-align: top;
        }
        </style>
            <h1 style="text-align:center">'.$year.' Burning Flipside Secondary Request</h1>
            <hr />
            <p style="text-align:center">
            <barcode code="'.$request['request_id'].'" type="QR" class="barcode" size="1" error="M" />
            </p>
            <p style="text-align: center;"><span style="text-align:center; font-size:18px; font-weight: bold;">'.$request['request_id'].'</span></p>
            <hr />
            <table border="0" cellpadding="0" cellspacing="0" style="width:100%">
            <tbody>
            <tr>
            <td class="sidehead">
            <p>Instructions</p>
            </td>
            <td>
            <ol>
            <li><strong>Purchase</strong>&nbsp;a money order,&nbsp;cashier&#39;s check or teller&#39;s check for $'.$request['total_due'].' made out to Austin Artistic Reconstruction.
            <ul type="a">
            <li><strong>Keep&nbsp;</strong>your payment receipt, you will need it for lost mail or returns.</li>
            <li><strong>Sign</strong>&nbsp;your money order if a signature is required</li>
            </ul>
            </li>
            <li><strong>Print</strong>&nbsp;this form on a sheet of 8.5x11 paper (standard letter-size paper).</li>
            <li><strong>Mail</strong>&nbsp;this form (the whole page) with your payment in a stamped envelope with your return address on it. The envelope should be addressed to:</li>
            </ol>
            <table>
            <tbody>
            <tr>
            <td class="contactlabel">&nbsp;</td>
            <td>Austin Artistic Reconstruction, Ticket Request<br />
            P.O. Box 9987<br />
            Austin, TX 78766</td>
            </tr>
            </tbody>
            </table>
            <p>NOTE: Tickets are limited. Requests will be filled in the order they are received. Any unfilled requests will be returned.</p>
            </td>
            </tr>
            </tbody>
            </table>
            <hr />
            <p style="text-align:center">
            <table>
            <tbody>'.$this->addRequestedTickets($request).'</tbody>
            </table>
            </p>
            ';
        $pdf->setPDFFromHTML($html);
        $response = $response->withHeader('Content-Type', 'application/pdf');
        $response->getBody()->write($pdf->toPDFBuffer());
        return $response;
    }

    protected function addRequestedTickets($request)
    {
        $ret = '';
        $validTickets = json_decode($request['valid_tickets']);
        for($i = 0; $i < count($validTickets); $i++)
        {
            $ret.='<tr><td>Ticket '.$i+1.'</td><td>'.$request['ticket_first_'.$validTickets[$i]].'</td><td>'.$request['ticket_last_'.$validTickets[$i]].'</td></tr>';
        }
        return $ret;
    }
}
/* vim: set tabstop=4 shiftwidth=4 expandtab: */
