<?php

namespace SabreAMF;

/**
 * Deserializer 
 * 
 * @package SabreAMF 
 * @version $Id$
 * @copyright Copyright (C) 2006-2009 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @licence http://www.freebsd.org/copyright/license.html  BSD License (4 Clause) 
 */

/**
 * Deserializer 
 * 
 * This is the abstract Deserializer. The AMF0 and AMF3 classes descent from this class
 */
abstract class Deserializer
{

    /**
     * stream 
     * 
     * @var InputStream
     */
    protected $stream;

    /**
     * __construct 
     *
     * @param InputStream $stream 
     * @return void
     */
    public function __construct(InputStream $stream)
    {

        $this->stream = $stream;
    }

    /**
     * readAMFData 
     * 
     * Starts reading an AMF block from the stream
     * 
     * @param mixed $settype 
     * @return mixed 
     */
    abstract public function readAMFData($settype = null);

    /**
     * getLocalClassName 
     * 
     * @param string $remoteClass 
     * @return mixed 
     */
    protected function getLocalClassName($remoteClass)
    {

        return ClassMapper::getLocalClass($remoteClass);
    }
}
