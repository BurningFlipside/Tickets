<?php
class SquareAPI extends \Flipside\Http\Rest\RestAPI
{
    public function setup($app)
    {
        $app->post('/events', array($this, 'processEvent'));
    }

    protected function validatePayload($signature, $body, $url, $key)
    {
        $hash = hash_hmac("sha256", $url.$body, $key, true);
        $myHash = base64_encode($hash);
        return $myHash == $signature;
    }

    public function processEvent($request, $response)
    {
        $key = \Flipside\Settings::getInstance()->getGlobalSetting('square')['eventHMACKey'];
        $signature = $request->getHeaderLine('HTTP_X_SQUARE_HMACSHA256_SIGNATURE');
        $url = 'https://'.$request->getServerParam('SERVER_NAME').$request->getServerParam('REQUEST_URI');
        $body = $request->getBody()->getContents();
        //TODO move the key to settings...
        if($this->validatePayload($signature, $body, $url, $key) === false)
        {
            //Invalid request, not from square...
            return $response->withStatus(403);
        }
        $event = json_decode($body);
        switch($event->type)
        {
            case 'dispute.created':
                return $this->processDispute($event, $response);
                break;
            default:
                error_log('Unknown event type '.$event->type);
        }

        file_put_contents('/tmp/log_'.date("j.n.Y").'.log', print_r($event, true), FILE_APPEND);
        return $response;
    }

    protected function processDispute($event, $response)
    {
        //TODO figure out which ticket the dispute goes to and void it
        return $response;
    }
}