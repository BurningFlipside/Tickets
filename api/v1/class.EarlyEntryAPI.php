<?php
class EarlyEntryAPI extends \Flipside\Http\Rest\DataTableAPI
{
    public function __construct()
    {
        parent::__construct('tickets', 'EarlyEntryMap', 'earlyEntrySetting');
    }

    public function setup($app)
    {
        $app->get('/passes', array($this, 'readPasses'));
        $app->post('/passes[/]', array($this, 'createPass'));
        $app->get('/passes/{id}', array($this, 'readPass'));
        $app->patch('/passes/{id}', array($this, 'updatePass'));
        $app->get('/passes/{id}/pdf[/]', array($this, 'getPassPdf'));
        $app->get('/passes/{id}/Actions/Reassign', array($this, 'reassignPass'));
        $app->post('/passes/Actions/BulkAssign', array($this, 'bulkAssign'));
        $app->post('/Actions/CheckEESpreadSheet', array($this, 'checkEESpreadSheet'));
        parent::setup($app);
    }

    protected function canRead($request)
    {
        $this->validateLoggedIn($request);
        return $this->user->isInGroupNamed('TicketAdmins');
    }
    protected function canCreate($request)
    {
        $this->validateLoggedIn($request);
        return $this->user->isInGroupNamed('TicketAdmins');
    }

    protected function canUpdate($request, $entity)
    {
        $this->validateLoggedIn($request);
        return $this->user->isInGroupNamed('TicketAdmins');
    }

    protected function canDelete($request, $entity)
    {
        $this->validateLoggedIn($request);
        return $this->user->isInGroupNamed('TicketAdmins');
    }

    public function readPasses($request, $response, $args)
    {
        $this->validateLoggedIn($request);
        $odata = $request->getAttribute('odata', new \Flipside\ODataParams(array()));
        $settings = \Tickets\DB\TicketSystemSettings::getInstance();
        $filter = false;
        if($this->user->isInGroupNamed('TicketAdmins') && $odata->filter !== false)
        {
            $filter = $odata->filter;
            if($filter->contains('year eq current'))
            {
                $clause = $filter->getClause('year');
                $clause->var2 = $settings['year'];
            }
        }
        else
        {
            $filter = new \Flipside\Data\Filter('year eq '.$settings['year'].' and assignedTo eq \''.$this->user->mail.'\'');
        }
        $dataTable = \Flipside\DataSetFactory::getDataTableByNames('tickets', 'EarlyEntryPasses');
        $passes = $dataTable->read($filter);
        return $response->withJson($passes);
    }

    public function readPass($request, $response, $args)
    {
        $this->validateLoggedIn($request);
        $dataTable = \Flipside\DataSetFactory::getDataTableByNames('tickets', 'EarlyEntryPasses');
        $filter = new \Flipside\Data\Filter("id eq '".$args['id']."'");
        $pass = $dataTable->read($filter);
        if($pass === false)
        {
            if(strlen($args['id']) === 16 && $this->user->isInGroupNamed('TicketAdmins'))
            {
                // This is a partial ID probably from a barcode. See if we can find it...
                $settings = \Tickets\DB\TicketSystemSettings::getInstance();
                $start = substr($args['id'], 0, 8);
                $end = substr($args['id'], 8, 8);
                $pass = $dataTable->raw_query('SELECT * FROM tblEarlyEntryPasses WHERE id LIKE "'.$start.'%" AND id LIKE "%'.$end.'" AND year='.$settings['year']);
                if($pass === false)
                {
                    return $response->withStatus(404);   
                }
                return $response->withJson($pass[0]);        
            }
            return $response->withStatus(404);
        }
        return $response->withJson($pass[0]);
    }

    public function updatePass($request, $response, $args)
    {
        $this->validateLoggedIn($request);
        if(!$this->user->isInGroupNamed('TicketAdmins'))
        {
            return $response->withStatus(401);
        }
        $dataTable = \Flipside\DataSetFactory::getDataTableByNames('tickets', 'EarlyEntryPasses');
        $obj = $request->getParsedBody();
        $filter = new \Flipside\Data\Filter("id eq '".$args['id']."'");
        $pass = $dataTable->read($filter);
        if($pass === false)
        {
            return $response->withStatus(404);
        }
        $pass = $pass[0];
        if(isset($obj['used']) && ($obj['used'] === 1 || $obj['used'] === true))
        {
            $obj['usedDT'] = date('Y-m-d H:i:s');
        }
        $res = $dataTable->update($filter, $obj);
        if($res === false)
        {
            return $response->withStatus(500);
        }
        return $response->withStatus(200);
    }

    public function getPassPdf($request, $response, $args)
    {
        $this->validateLoggedIn($request);
        $dataTable = \Flipside\DataSetFactory::getDataTableByNames('tickets', 'EarlyEntryPasses');
        $filter = new \Flipside\Data\Filter("id eq '".$args['id']."'");
        $pass = $dataTable->read($filter);
        if($pass === false)
        {
            return $response->withStatus(404);
        }
        $pdf = new \Tickets\EarlyEntryPDF($pass[0]);
        $response = $response->withHeader('Content-Type', 'application/pdf');
        $response->getBody()->write($pdf->toPDFBuffer());
        return $response;
    }

    public function createPass($request, $response, $args)
    {
        $this->validateLoggedIn($request);
        if($this->user->isInGroupNamed('TicketAdmins') === false)
        {
            return $response->withStatus(401);
        }
        $settings = \Tickets\DB\TicketSystemSettings::getInstance();
        $year = $settings['year'];
        $obj = $request->getParsedBody();
        $count = 1;
        if(isset($obj['count'])) 
        {
            $count = $obj['count'];
        }
        $dataTable = \Flipside\DataSetFactory::getDataTableByNames('tickets', 'EarlyEntryPasses');
        $type = $obj['type'];
        $origOwner = $obj['owner'];
        for($i = 0; $i < $count; $i++)
        {
            $dbObject = array('type' => $type, 'year' => $year, 'assignedTo' => $origOwner, 'originalOwner' => $origOwner);
            // 128-bit random id for each pass
            $bytes = random_bytes(16);
            $dbObject['id'] = bin2hex($bytes);
            if($dataTable->create($dbObject) === false)
            {
                return $response->withStatus(500);
            }
        }
        return $response->withStatus(200);
    }

    public function reassignPass($request, $response, $args)
    {
        $this->validateLoggedIn($request);
        $dataTable = \Flipside\DataSetFactory::getDataTableByNames('tickets', 'EarlyEntryPasses');
        $filter = new \Flipside\Data\Filter("id eq '".$args['id']."'");
        $pass = $dataTable->read($filter);
        if($pass === false)
        {
            return $response->withStatus(404);
        }
        $pass = $pass[0];
        if($pass['assignedTo'] === $this->user->mail && !$this->user->isInGroupNamed('TicketAdmins') === false)
        {
            return $response->withStatus(401);
        }
        $obj = $request->getParsedBody();
        $pass['assignedTo'] = $obj['assignedTo'];
        unset($pass['used']);
        $res = $dataTable->update($filter, $pass);
        if($res === false)
        {
            return $response->withStatus(500);
        }
        // TODO send email to new owner
        return $response->withStatus(200);
    }

    public function bulkAssign($request, $response, $args)
    {
        $this->validateLoggedIn($request);
        if($this->user->isInGroupNamed('TicketAdmins') === false)
        {
            return $response->withStatus(401);
        }
        $settings = \Tickets\DB\TicketSystemSettings::getInstance();
        $year = $settings['year'];
        $obj = $request->getParsedBody();
        $dataTable = \Flipside\DataSetFactory::getDataTableByNames('tickets', 'EarlyEntryPasses');
        // Get the CP AF and Art AF's emails
        $auth = \Flipside\AuthProvider::getInstance();
        $filter = false;
        if($obj['type'] == 'Theme Camp')
        {
            $filter = new \Flipside\Data\Filter('title eq "CPAF"');
        }
        else if($obj['type'] == 'Art')
        {
            $filter = new \Flipside\Data\Filter('title eq "ArtAF"');
        }
        else
        {
            return $response->withStatus(400);
        }
        $users = $auth->getUsersByFilter($filter);
        $emails = array();
        foreach($users as $user)
        {
            array_push($emails, $user->mail);
        }
        if(count($emails) !== 1)
        {
            return $response->withStatus(500);
        }
        $filter = new \Flipside\Data\Filter('used eq 0 and assignedTo eq "'.$emails[0].'" and year eq '.$year);
        $passes = $dataTable->read($filter, false, intval($obj['count']));
        if($passes === false)
        {
            return $response->withStatus(500);
        }
        foreach($passes as $pass)
        {
            $pass['originalOwner'] = $pass['assignedTo'] = $obj['owner'];
            if(isset($obj['notes']))
            {
                $pass['notes'] = $obj['notes'];
            }
            // I'm not sure why I need to set this, but without it somehow they are all getting set to used
            unset($pass['used']);
            $res = $dataTable->update(new \Flipside\Data\Filter("id eq '".$pass['id']."'"), $pass);
            if($res === false)
            {
                return $response->withStatus(500);
            }
        }
        return $response->withStatus(200);
    }

    public function checkEESpreadSheet($request, $response, $args)
    {
        $this->validateLoggedIn($request);
        if($this->user->isInGroupNamed('TicketAdmins') === false)
        {
            return $response->withStatus(401);
        }
        $obj = $request->getParsedBody();
        if(!isset($obj['spreadSheetId']))
        {
            return $response->withStatus(400);
        }
        $ticketDataTable = \Tickets\DB\TicketsDataTable::getInstance();
        $settings = \Tickets\DB\TicketSystemSettings::getInstance();
        $year = $settings['year'];
        $googleClient = new \Google_Client();
        $googleClient->setAuthConfigFile('/var/www/secure_settings/ticket-system.json');
        $googleClient->setScopes([\Google_Service_Sheets::SPREADSHEETS]);
        $service = new \Google_Service_Sheets($googleClient);
        $spreadsheet = $service->spreadsheets->get($obj['spreadSheetId']);
        // First let's make sure the spreadsheet is in a format we can understand...
        $sheets = $spreadsheet->getSheets();
        $sheetDetails = array('Tuesday Infrastructure' => 2, 'Wednesday Infrastructure' => 1, 'Theme Camp and Art Early Entry' => 0);
        $expectedSheetNames = array_keys($sheetDetails);
        $foundNames = array();
        $count = count($sheets);
        for($i = 0; $i < $count; $i++)
        {
            if(in_array($sheets[$i]->getProperties()->getTitle(), $expectedSheetNames))
            {
                array_push($foundNames, $sheets[$i]->getProperties()->getTitle());
            }
        }
        if(count($foundNames) !== count($expectedSheetNames))
        {
            $missing = array_diff($expectedSheetNames, $foundNames);
            $errRet = array('error' => 'Missing '.implode(', ', $missing).' sheets');
            return $response->withJson($errRet)->withStatus(400);
        }
        $errCount = 0;
        for($i = 0; $i < $count; $i++)
        {
            $sheet = $sheets[$i];
            $sheetName = $sheet->getProperties()->getTitle();
            $sheetId = $sheet->getProperties()->getSheetId();
            $range = $sheetName.'!A1:Z';
            $getValResponse = $service->spreadsheets_values->get($spreadsheet->getSpreadsheetId(), $range);
            $values = $getValResponse->getValues();
            if($values === null)
            {
                continue;
            }
            // Check if the first row is the title or the header
            $header = $values[0];
            $valueCount = count($values);
            $valueCount--;
            $start = 1;
            if(count($header) < 5)
            {
                $valueCount--;
                $header = $values[1];
                $start = 2;
            }
            if(!isset($sheetDetails[$sheetName]))
            {
                // Skip sheets we don't care about
                continue;
            }
            $eeType = $sheetDetails[$sheetName];
            $colsNames = range('A', 'Z');
            $gateDBCol = array_search('In Gate DB', $header);
            $gateDBCol = $colsNames[$gateDBCol];
            unset($colsNames);
            // Convert the rest of the rows to associative arrays
            for($j = 0; $j < $valueCount; $j++)
            {
                $row = $values[$j + $start];
                $row = array_combine2($header, $row);
                if($row === false)
                {
                    continue;
                }
                // Check for short code first...
                $ticket = false;
                if(isset($row['Ticket Short Code']))
                {
                    if(strlen($row['Ticket Short Code']) !== 0)
                    {
                        $ticket = $ticketDataTable->raw_query("SELECT * from tblTickets WHERE hash like '".$row['Ticket Short Code']."%' and year =".$year);
                        if($ticket !== false)
                        {
                            $ticket = $ticket[0];
                        }   
                    }
                }
                if($ticket === false)
                {
                    $ticket = $ticketDataTable->read(new \Flipside\Data\Filter("email eq '".$row['E-mail Address']."' and year eq ".$year));
                    if($ticket !== false && count($ticket) > 1)
                    {
                        // Need to also search by name...
                        $found = false;
                        for($k = 0; $k < count($ticket); $k++)
                        {
                            $ticketName = $ticket[$k]['firstName'].' '.$ticket[$k]['lastName'];
                            if(strcasecmp($ticketName, $row['Legal Name']) === 0)
                            {
                                $ticket = $ticket[$k];
                                $found = true;
                                break;
                            }
                        }
                        if($found === false)
                        {
                            $ticket = false;
                        }
                    }
                    else if($ticket !== false && count($ticket) === 1)
                    {
                        $ticket = $ticket[0];
                    }
                }
                if($ticket === false)
                {
                    // Could not find the ticket...
                    // Add to an error response object and flag on spreadsheet
                    $errCount++;
                    writeCellToSpreadsheet($service, $spreadsheet->getSpreadsheetId(), $sheetName, $j + $start + 1, $gateDBCol, 'Could not locate in DB!');
                    continue;
                }
                $ticket['earlyEntryWindow'] = $eeType;
                unset($ticket['hash_words']);
                $res = $ticketDataTable->update(new \Flipside\Data\Filter("hash eq '".$ticket['hash']."'"), $ticket);
                if ($res === false)
                {
                    // Add to an error response object and flag on spreadsheet
                    $errCount++;
                    writeCellToSpreadsheet($service, $spreadsheet->getSpreadsheetId(), $sheetName, $j + $start + 1, $gateDBCol, 'Could not update in DB!');
                    continue;
                }
                writeCellToSpreadsheet($service, $spreadsheet->getSpreadsheetId(), $sheetName, $j + $start + 1, $gateDBCol, 'Yes');
            }
        }
        return $response->withJson(array('errorCount' => $errCount));
    }
}

function writeCellToSpreadsheet($service, $spreadsheetId, $sheetName, $row, $col, $value)
{
    $data = array(
        array($value)
    );
    $valueRange = new \Google_Service_Sheets_ValueRange();
    $valueRange->setValues($data);
    $range = $sheetName.'!'.$col.$row;
    $params = array('valueInputOption' => 'USER_ENTERED');
    $service->spreadsheets_values->update($spreadsheetId, $range, $valueRange, $params);
}

function array_combine2($arr1, $arr2) {
    $count = min(count($arr1), count($arr2));
    return array_combine(array_slice($arr1, 0, $count), array_slice($arr2, 0, $count));
}
/* vim: set tabstop=4 shiftwidth=4 expandtab: */
