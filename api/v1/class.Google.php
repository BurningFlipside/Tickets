<?php
class GoogleAPI extends \Flipside\Http\Rest\RestAPI
{
    public function __construct()
    {
        $this->client = new \Google_Client();
        $this->client->setAuthConfigFile('/var/www/secure_settings/ticket-system.json');
        $this->client->setScopes([\Google_Service_Sheets::SPREADSHEETS, \Google_Service_Sheets::DRIVE]);
    }

    public function setup($app)
    {
        $app->get('/spreadsheets', array($this, 'listPossibleSpreadSheets'));
        $app->get('/spreadsheets/{id}/isProblematicPersonsFormat', array($this, 'isProblematicPersonsFormat'));
        $app->post('/spreadsheets/{id}/Actions.MakeProblematicPersonSpreadsheet', array($this, 'makeProblematicPersonsFormat'));
        $app->get('/problematicActors', array($this, 'getProblematicActors'));
        $app->patch('/problematicActors', array($this, 'updateProblematicActors'));
        $app->post('/problematicActors/Actions/Test', array($this, 'checkProblematicActors'));
    }

    public function getProblematicActors($request, $response)
    {
        $this->validateLoggedIn($request);
        if(!$this->user->isInGroupNamed('TicketAdmins')) {
            return $response->withStatus(403);
        }
        $settings = \Tickets\DB\TicketSystemSettings::getInstance();
        $id = $settings['problematicSpreadsheetID'];
        $enabled = $id != null;
        $ret = array('Enabled' => $enabled, 'SpreadsheetID' => $id);
        return $response->withJson($ret);
    }

    public function listPossibleSpreadSheets($request, $response)
    {
        $this->validateLoggedIn($request);
        if(!$this->user->isInGroupNamed('TicketAdmins')) {
            return $response->withStatus(403);
        }
        $service = new \Google_Service_Drive($this->client);
        $files = $service->files->listFiles(array('q' => 'mimeType=\'application/vnd.google-apps.spreadsheet\' and trashed=false', 'fields' => 'files(id, name)', 'includeItemsFromAllDrives' => true, 'supportsAllDrives'=>true));
        $myFiles = array();
        foreach($files->files as $file)
        {
            $myFiles[] = array('id' => $file->getId(), 'name' => $file->getName());
        }
        return $response->withJson($myFiles);
    }

    public function isProblematicPersonsFormat($request, $response, $args)
    {
        $this->validateLoggedIn($request);
        if(!$this->user->isInGroupNamed('TicketAdmins')) {
            return $response->withStatus(403);
        }
        $service = new \Google_Service_Sheets($this->client);
        $spreadsheetId = $args['id'];
        $sheet = $service->spreadsheets->get($spreadsheetId);
        if($sheet == null)
        {
            return $response->withStatus(404);
        }
        $sheets = $sheet->getSheets();
        if(count($sheets) != 1)
        {
            return $response->withStatus(400)->withJson(array('error' => 'Spreadsheet must have exactly one sheet'));
        }
        $requiredColumnNames = array('First Name', 'Last Name', 'Email Address', 'Added Year');
        $range = '!A1:Z1';
        $valueResponse = $service->spreadsheets_values->get($spreadsheetId, $range);
        $values = $valueResponse->getValues();
        if($values == null || count($values) != 1)
        {
            return $response->withStatus(400)->withJson(array('error' => 'Spreadsheet must have exactly at least one row!'));
        }
        $row = $values[0];
        $valuesCount = count($row);
        for($i = 0; $i < $valuesCount; $i++)
        {
            $index = array_search($row[$i], $requiredColumnNames);
            if($index !== false)
            {
                unset($requiredColumnNames[$index]);
            }
        }
        if(count($requiredColumnNames) == 0)
        {
            return $response->withJson(array('success' => 'Spreadsheet is in the correct format'));
        }
        return $response->withStatus(400)->withJson(array('error' => 'Spreadsheet is missing the following columns', 'missing' => $requiredColumnNames));
    }

    public function makeProblematicPersonsFormat($request, $response, $args)
    {
        $this->validateLoggedIn($request);
        if(!$this->user->isInGroupNamed('TicketAdmins')) {
            return $response->withStatus(403);
        }
        $service = new \Google_Service_Sheets($this->client);
        $spreadsheetId = $args['id'];
        $sheet = $service->spreadsheets->get($spreadsheetId);
        if($sheet == null)
        {
            return $response->withStatus(404);
        }
        $sheets = $sheet->getSheets();
        if(count($sheets) != 1)
        {
            $requestBody = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest();
            $requests = array();
            for($i = 1; $i < count($sheets); $i++)
            {
                $requests[] = array('deleteSheet' => array('sheetId' => $sheets[$i]->properties->sheetId));
            }
            $requestBody->setRequests($requests);
            $service->spreadsheets->batchUpdate($spreadsheetId, $requestBody);
            $sheet = $service->spreadsheets->get($spreadsheetId);
            $sheets = $sheet->getSheets();
            if(count($sheets) != 1)
            {
                return $response->withStatus(400)->withJson(array('error' => 'Spreadsheet must have exactly one sheet and we could not fix it!'));
            }
        }
        // Clear the sheet
        $requestBody = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest();
        $requests = array(array('updateCells' => array('range' => array('sheetId' => $sheets[0]->properties->sheetId), 'fields' => '*')));
        $requestBody->setRequests($requests);
        $service->spreadsheets->batchUpdate($spreadsheetId, $requestBody);
        $range = '!A1:Z1';
        $params = ['valueInputOption' => 'RAW'];
        // Put both the required and optional columns in the first row
        $requiredColumnNames = array('First Name', 'Last Name', 'Email Address', 'Added Year');
        $optionalColumnNames = array('Submitted By', 'Seconded By', 'Notes');
        $headers = array_merge($requiredColumnNames, $optionalColumnNames);
        $rangeVal = new \Google_Service_Sheets_ValueRange();
        $rangeVal->setValues(array($headers));
        $service->spreadsheets_values->update($spreadsheetId, $range, $rangeVal, $params);
        // Now bold the first row...
        $requests = array(array('repeatCell' => array('range' => array('sheetId' => $sheets[0]->properties->sheetId, 'startRowIndex' => 0, 'endRowIndex' => 1), 'cell' => array('userEnteredFormat' => array('textFormat' => array('bold' => true))), 'fields' => 'userEnteredFormat.textFormat.bold')));
        $requests[] = array('updateSheetProperties' => array('properties' => array('sheetId' => $sheets[0]->properties->sheetId, 'gridProperties' => array('frozenRowCount' => 1)), 'fields' => 'gridProperties.frozenRowCount'));
        $requests[] = array('addNamedRange' => array('namedRange' => array('name' => 'RequiredFields', 'range' => array('sheetId' => $sheets[0]->properties->sheetId, 'startRowIndex' => 0, 'endRowIndex' => 1, 'startColumnIndex' => 0, 'endColumnIndex' => count($requiredColumnNames)))));
        $requests[] = array('addProtectedRange' => array('protectedRange' => array('description' => 'Required Fields', 'range' => array('sheetId' => $sheets[0]->properties->sheetId, 'startRowIndex' => 0, 'endRowIndex' => 1, 'startColumnIndex' => count($requiredColumnNames), 'endColumnIndex' => count($headers)))));
        $requestBody->setRequests($requests);
        $service->spreadsheets->batchUpdate($spreadsheetId, $requestBody);
    }

    public function checkProblematicActors($request, $response)
    {
        $this->validateLoggedIn($request);
        if(!$this->user->isInGroupNamed('TicketAdmins')) {
            return $response->withStatus(403);
        }
        $data = $request->getParsedBody();
        if(!isset($data['email']))
        {
            return $response->withStatus(400)->withJson(array('error' => 'Email is required'));
        }
        $settings = \Tickets\DB\TicketSystemSettings::getInstance();
        $spreadsheetId = $settings['problematicSpreadsheetID'];
        if($spreadsheetId == null)
        {
            // No problematic spreadsheet, so no problematic actors
            return $response->withStatus(200);
        }
        $service = new \Google_Service_Sheets($this->client);
        $range = '!A1:Z1000';
        $googleResponse = $service->spreadsheets_values->get($spreadsheetId, $range);
        $values = $googleResponse->getValues();
        $headers = array_shift($values);
        $count = count($values);
        for($i = 0; $i < $count; $i++)
        {
            $row = $values[$i];
            $myHeaders = $headers;
            if(count($row) < count($headers))
            {
                $myHeaders = array_slice($headers, 0, count($row));
            }
            $row = array_combine($myHeaders, $row);
            if(doEmailCompare($row['Email Address'], $data['email']))
            {
                return $response->withStatus(451);
            }
            if(isset($data['first']) && isset($data['last']) && (isset($row['First Name']) && isset($row['Last Name'])))
            {
                if(strcasecmp($row['First Name'], $data['first']) == 0 && strcasecmp($row['Last Name'], $data['last']) == 0)
                {
                    return $response->withStatus(409);
                }
            }
        }
        return $response->withStatus(200);
    }
}

function doEmailCompare($email1, $email2) {
    $e1 = strtolower($email1);
    $e2 = strtolower($email2);
    if(!filter_var($e1, \FILTER_VALIDATE_EMAIL) || !filter_var($e2, \FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    $e1 = str_replace('.', '', $e1);
    $e2 = str_replace('.', '', $e2);
    $e1 = removeEmailSubaddress($e1);
    $e2 = removeEmailSubaddress($e2);
    return $e1 == $e2;
}

function removeEmailSubaddress($email) {
    $pos = strpos($email, '+');
    if($pos === false) {
        return $email;
    }
    $endPos = strpos($email, '@');
    if($endPos === false) {
        return substr($email, 0, $pos);
    }
    return substr($email, 0, $pos).substr($email, $endPos);
}