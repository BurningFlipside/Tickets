<?php
require_once('Autoload.php');
require_once('app/TicketAutoload.php');

if(!class_exists('Httpful\Request'))
{
    require '/var/www/common/libs/httpful/bootstrap.php';
}

$expectedAnswerCount = 5;

function secondary_api_group()
{
    global $app;
    $app->get('/questions', 'getQuestions');
    $app->post('/questions/answers', 'answerQuestions');
    $app->get('/requests', 'getSecondaryRequests');
    $app->post('/requests', 'makeNewSecondaryRequest');
    $app->patch('/requests/:request_id/:year', 'updateSecondaryRequest');
    $app->post('/requests/:request_id/:year/Actions/Ticket', 'ticketSecondaryRequest');
    $app->get('/:request_id/:year/pdf', 'getSecondaryPdf');
}

function addAnswerSpace($format, $id)
{
    if($format === 'bool')
    {
        echo '<select id="answer['.$id.']" name="answer['.$id.']" class="form-control"><option value=""></option>><option value="true">True</option><option value="false">False</option></select>';
    }
    else
    {
        echo '<input class="form-control" id="answer['.$id.']" name="answer['.$id.']" type="'.$format.'">';
    }
}

function getQuestions()
{
    global $app;
    global $expectedAnswerCount;
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
    if($app->fmt === 'html')
    {
        $app->fmt = 'passthru';
        for($i = 0; $i < $count; $i++)
        {
            echo '<div class="clearfix visible-sm visible-md visible-lg"></div><div class="form-group">';
            echo '    <label class="col-sm-12 control-label">'.$questions[$i]['displayString'].'</label>';
            echo '    <div class="col-sm-10">';
            if(count($questions[$i]['answerFormat']) === 1)
            {
                addAnswerSpace($questions[$i]['answerFormat'][0], $i);
            }
            else
            {
                for($j = 0; $j < count($questions[$i]['answerFormat']); $j++)
                {
                    addAnswerSpace($questions[$i]['answerFormat'][$j], $i.']['.$j);
                }
            }
            echo '    </div>';
            echo '</div>';
        } 
    }
    else
    {
        echo json_encode($questions);
    }
}

function answerQuestions()
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $data = $app->getJsonBody(true);
    if(!isset($data['g-recaptcha-response']))
    {
        echo json_encode(array('err'=>'Missing CAPTCHA!'));
        return;
    }
    $settings = \Settings::getInstance();
    $key = $settings->getGlobalSetting('captcha_secret');
    $resp = \Httpful\Request::post('https://www.google.com/recaptcha/api/siteverify?secret='.$key.'&response='.$data['g-recaptcha-response'])->send();
    if($resp->body->success != true)
    {
        echo json_encode(array('err'=>'Sorry Google thinks you are a robot!'));
        return;
    }
    unset($data['g-recaptcha-response']);
    $ids = \FlipSession::getVar('questionIDs');
    $questionDataTable = DataSetFactory::getDataTableByNames('tickets', 'Questions');
    $wrongDataTable = DataSetFactory::getDataTableByNames('tickets', 'WrongAnswers');
    $questions = $questionDataTable->raw_query('SELECT * from tblQuestions WHERE id IN ('.implode(',', $ids).');');
    $count = count($questions);

    if(!isset($data['answer']))
    {
        echo json_encode(array('err'=>'Missing answers!'));
        return;
    }
    $answers = $data['answer'];
    if($count != count($answers))
    {
        echo json_encode(array('err'=>'Missing some answers!'));
        return;
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
                if(verifyBoolAnswer($question['answers'][0], $answer) === false)
                {
                    $wrongDataTable->create(array('uid'=>$app->user->uid, 'IDs'=>implode(',', $ids), 'Answers'=>json_encode($answers)));
                    echo json_encode(array('err'=>'Invalid answer!', 'wrong'=>$i));
                    return;
                }
            }
            else
            {
                if(verifyTextAnswer($question['answers'], $answer) === false)
                {
                    $wrongDataTable->create(array('uid'=>$app->user->uid, 'IDs'=>implode(',', $ids), 'Answers'=>json_encode($answers)));
                    echo json_encode(array('err'=>'Invalid answer! If you repeatedly see this error and are sure your answers are correct, please email tickets@burningflipside.com with a screenshot.', 'wrong'=>$i));
                    return;
                }
            }
        }
        else
        {
            //Multiple Answers
            $countAnswers = count($question['answerFormat']);
            if($countAnswers !== count($answer))
            {
                echo json_encode(array('err'=>'Missing parts of a question!'));
                return;
            }
            //Currently assume all answers are text...
            for($j = 0; $j < $countAnswers; $j++)
            {
                if(verifyTextAnswer($question['answers'], $answer[$j]) === false)
                {
                    $wrongDataTable->create(array('uid'=>$app->user->uid, 'IDs'=>implode(',', $ids), 'Answers'=>json_encode($answers)));
                    echo json_encode(array('err'=>'Invalid answer!', 'wrong'=>$i));
                    return;
                }
            }
        }
    }
    $key = bin2hex(openssl_random_pseudo_bytes(32));
    \FlipSession::setVar('secondaryRequestID', $key);
    echo json_encode(array('success'=>true, 'access_key'=>$key));
}

function verifyBoolAnswer($correctAnswer, $answer)
{
    $boolAnswer = ($answer === 'true');
    return $correctAnswer === $boolAnswer;
}

function verifyTextAnswer(&$correctAnswers, $answer)
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

function getSecondaryRequests()
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $params = $app->request->params();
    $requestDataTable = DataSetFactory::getDataTableByNames('tickets', 'SecondaryRequests');
    $filter = false;
    if($app->user->isInGroupNamed('TicketAdmins') && $app->odata->filter !== false)
    {
        $filter = $app->odata->filter;
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
    $search = $app->request->params('$search');
    if($search !== null && $app->user->isInGroupNamed('TicketAdmins'))
    {
        $filter->addToSQLString(" AND (mail LIKE '%$search%' OR sn LIKE '%$search%' OR givenName LIKE '%$search%')");
    }
    $requests = $requestDataTable->read($filter, $app->odata->select, $app->odata->top, $app->odata->skip, $app->odata->orderby);
    if($requests === false)
    {
        $requests = array();
    }
    else if(!is_array($requests))
    {
        $requests = array($requests);
    }
    if($app->odata->count)
    {
        $requests = array('@odata.count'=>count($requests), 'value'=>$requests);
    }
    echo json_encode($requests);
}

function makeNewSecondaryRequest()
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
    }
    $data = $app->getJsonBody(true);
    $data['total_due'] = 0;
    $data['valid_tickets'] = array();

    $key = \FlipSession::getVar('secondaryRequestID', false);
    if($key === false)
    {
        echo json_encode(array('err'=>'You skipped the question section!'));
        return;
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
        echo json_encode(array('err'=>'You already have too many tickets for a secondary request!'));
        return;
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
                    echo json_encode(array('err'=>'You already have requested a ticket type which you already have the maximum number of.'));
                    return;
                }
            }
            if($ticketTypes[$ticket['type']]['count'] >= ($ticketTypes[$ticket['type']]['max_per_request'] - 1))
            {
                if(isset($data['ticket_first_'.$ticket['type'].'_2']))
                {
                    echo json_encode(array('err'=>'You already have requested two many of a ticket type which you already have some of.'));
                    return;
                }
            }
        }
    }

    $requests = $secondaryTable->read(new \Data\Filter('mail eq "'.$app->user->mail.'"'));
    if(!empty($requests))
    {
        echo json_encode(array('err'=>'You already have a secondary request!'));
        return;
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
        echo json_encode(array('err'=>'Internal error! Try again later.'));
        return;
    }
    echo json_encode(array('uri'=>'api/v1/secondary/'.$key.'/'.$settings['year'].'/pdf'));
}

function updateSecondaryRequest($request_id, $year)
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
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
        $app->notFound();
        return;
    }
    $request = $request[0];
    $obj = $app->getJsonBody(true);
    if($secondaryTable->update($filter, $obj) === false)
    {
        echo 'false';
        return;
    }
    echo 'true';
}

function ticketSecondaryRequest($request_id, $year)
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
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
        $app->notFound();
        return;
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
        echo 'true';
        return;
    }
    echo json_encode(array('fails'=>$fails));
}

function getSecondaryPdf($request_id, $year)
{
    global $app;
    if(!$app->user)
    {
        throw new Exception('Must be logged in', ACCESS_DENIED);
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
        $app->notFound();
        return;
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
    <tbody>'.addRequestedTickets($request).'</tbody>
</table>
</p>
';
    $pdf->setPDFFromHTML($html);
    $app->fmt = 'passthru';
    $app->response->headers->set('Content-Type', 'application/pdf');
    echo $pdf->toPDFBuffer();
}

function addRequestedTickets($request)
{
    $ret = '';
    $validTickets = json_decode($request['valid_tickets']);
    for($i = 0; $i < count($validTickets); $i++)
    {
        $ret.='<tr><td>Ticket '.$i.'</td><td>'.$request['ticket_first_'.$validTickets[$i]].'</td><td>'.$request['ticket_last_'.$validTickets[$i]].'</td></tr>';
    }
    return $ret;
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: */

