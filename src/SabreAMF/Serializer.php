<?php

namespace SabreAMF;

/**
 * Serializer 
 * 
 * @package SabreAMF 
 * @version $Id$
 * @copyright Copyright (C) 2006-2009 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @licence http://www.freebsd.org/copyright/license.html  BSD License (4 Clause) 
 */

/**
 * Abstract Serializer
 *
 * This is the abstract serializer class. This is used by the AMF0 and AMF3 serializers as a base class
 */
abstract class Serializer
{

    /**
     * stream 
     * 
     * @var OutputStream 
     */
    protected $stream;

    /**
     * __construct 
     * 
     * @param OutputStream $stream 
     * @return void
     */
    public function __construct(OutputStream $stream)
    {

        $this->stream = $stream;
    }

    /**
     * writeAMFData 
     * 
     * @param mixed $data 
     * @param int $forcetype 
     * @return mixed 
     */
    abstract public function writeAMFData($data, $forcetype = null);

    /**
     * getStream
     *
     * @return OutputStream
     */
    public function getStream()
    {

        return $this->stream;
    }

    /**
     * getRemoteClassName 
     * 
     * @param string $localClass 
     * @return mixed 
     */
    protected function getRemoteClassName($localClass)
    {

        return ClassMapper::getRemoteClass($localClass);
    }

    /**
     * Checks wether the provided array has string keys and if it's not sparse.
     *
     * @param array $arr
     * @return bool
     */
    protected function isPureArray(array $array)
    {
        $i = 0;
        foreach ($array as $k => $v) {
            if ($k !== $i) {
                return false;
            }
            $i++;
        }

        return true;
    }
}
