<?php

namespace SabreAMF\AMF3;

/**
 * RemotingMessage 
 * 
 * @uses AbstractMessage
 * @package SabreAMF
 * @subpackage AMF3
 * @version $Id$
 * @copyright Copyright (C) 2006-2009 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @licence http://www.freebsd.org/copyright/license.html  BSD License (4 Clause) 
 */

/**
 * Invokes a message on a service
 */
class RemotingMessage extends AbstractMessage
{

    /**
     * operation 
     * 
     * @var string 
     */
    public $operation;

    /**
     * source 
     * 
     * @var string 
     */
    public $source;

    /**
     * Creates the object and generates some values 
     * 
     * @return void
     */
    public function __construct()
    {

        $this->messageId = $this->generateRandomId();
        $this->clientId = $this->generateRandomId();
        $this->destination = null;
        $this->body = null;
        $this->timeToLive = 0;
        $this->timestamp = time() . '00';
        $this->headers = new STDClass();
    }
}
