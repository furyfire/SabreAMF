<?php

namespace SabreAMF;

/**
 * AMF Server
 * 
 * This is the AMF0/AMF3 Server class. Use this class to construct a gateway for clients to connect to 
 *
 * The difference between this server class and the regular server, is that this server is aware of the
 * AMF3 Messaging system, and there is no need to manually construct the AcknowledgeMessage classes.
 * Also, the response to the ping message will be done for you.
 * 
 * @package SabreAMF 
 * @version $Id$
 * @copyright Copyright (C) 2006-2009 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @licence http://www.freebsd.org/copyright/license.html  BSD License (4 Clause)
 * @uses Server
 * @uses Message
 * @uses Constants
 */
class CallbackServer extends Server
{

    /**
     * Assign this callback to handle method-calls 
     *
     * @var callback
     */
    public $onInvokeService;

    /**
     * Assign this callback to handle authentication requests 
     * 
     * @var callback 
     */
    public $onAuthenticate;

    /**
     * handleCommandMessage 
     * 
     * @param AMF3\CommandMessage $request 
     * @return AMF3\AbstractMessage 
     */
    private function handleCommandMessage(AMF3\CommandMessage $request)
    {

        switch ($request->operation) {
            case AMF3\CommandMessage::CLIENT_PING_OPERATION:
                $response = new AMF3\AcknowledgeMessage($request);
                break;
            case AMF3\CommandMessage::LOGIN_OPERATION:
                $authData = base64_decode($request->body);
                if ($authData) {
                    $authData = explode(':', $authData, 2);
                    if (count($authData) == 2) {
                        $this->authenticate($authData[0], $authData[1]);
                    }
                }
                $response = new AMF3\AcknowledgeMessage($request);
                $response->body = true;
                break;
            case AMF3\CommandMessage::DISCONNECT_OPERATION:
                $response = new AMF3\AcknowledgeMessage($request);
                break;
            default:
                throw new Exception('Unsupported CommandMessage operation: ' . $request->operation);
        }
        return $response;
    }

    /**
     * authenticate 
     * 
     * @param string $username 
     * @param string $password 
     * @return void
     */
    protected function authenticate($username, $password)
    {

        if (is_callable($this->onAuthenticate)) {
            call_user_func($this->onAuthenticate, $username, $password);
        }
    }

    /**
     * invokeService 
     * 
     * @param string $service 
     * @param string $method 
     * @param array $data 
     * @return mixed 
     */
    protected function invokeService($service, $method, $data)
    {

        if (is_callable($this->onInvokeService)) {
            return call_user_func_array($this->onInvokeService, array($service, $method, $data));
        } else {
            throw new Exception('onInvokeService is not defined or not callable');
        }
    }

    /**
     * exec
     * 
     * @return void
     */
    public function exec()
    {

        // First we'll be looping through the headers to see if there's anything we reconize

        foreach ($this->getRequestHeaders() as $header) {

            switch ($header['name']) {

                // We found a credentials headers, calling the authenticate method
                case 'Credentials':
                    $this->authenticate($header['data']['userid'], $header['data']['password']);
                    break;
            }
        }

        foreach ($this->getRequests() as $request) {

            // Default AMFVersion
            $AMFVersion = 0;

            $response = null;

            try {

                if (is_array($request['data']) && isset($request['data'][0])
                        && $request['data'][0] instanceof AMF3\AbstractMessage) {
                    $request['data'] = $request['data'][0];
                }

                // See if we are dealing with the AMF3 messaging system
                if (is_object($request['data']) && $request['data'] instanceof AMF3\AbstractMessage) {

                    $AMFVersion = 3;

                    // See if we are dealing with a CommandMessage
                    if ($request['data'] instanceof AMF3\CommandMessage) {

                        // Handle the command message
                        $response = $this->handleCommandMessage($request['data']);
                    }

                    // Is this maybe a RemotingMessage ?
                    if ($request['data'] instanceof AMF3\RemotingMessage) {

                        // Yes
                        $response = new AMF3\AcknowledgeMessage($request['data']);
                        $response->body = $this->invokeService(
                            $request['data']->source,
                            $request['data']->operation,
                            $request['data']->body
                        );
                    }
                } else {

                    // We are dealing with AMF0
                    $service = substr($request['target'], 0, strrpos($request['target'], '.'));
                    $method = substr(strrchr($request['target'], '.'), 1);

                    $response = $this->invokeService($service, $method, $request['data']);
                }

                $status = Constants::R_RESULT;
            } catch (Exception $e) {

                // We got an exception somewhere, ignore anything that has happened and send back
                // exception information

                if ($e instanceof DetailException) {
                    $detail = $e->getDetail();
                } else {
                    $detail = '';
                }

                switch ($AMFVersion) {
                    case Constants::AMF0:
                        $response = array(
                            'description' => $e->getMessage(),
                            'detail' => $detail,
                            'line' => $e->getLine(),
                            'code' => $e->getCode() ? $e->getCode() : get_class($e),
                        );
                        break;
                    case Constants::AMF3:
                        $response = new AMF3\ErrorMessage($request['data']);
                        $response->faultString = $e->getMessage();
                        $response->faultCode = $e->getCode();
                        $response->faultDetail = $detail;
                        break;
                }
                $status = Constants::R_STATUS;
            }

            $this->setResponse($request['response'], $status, $response);
        }
        $this->sendResponse();
    }
}
