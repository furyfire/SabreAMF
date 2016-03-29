<?php

namespace SabreAMF\AMF0;

/**
 * Serializer 
 * 
 * @package SabreAMF
 * @subpackage AMF0
 * @version $Id$
 * @copyright Copyright (C) 2006-2009 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @licence http://www.freebsd.org/copyright/license.html  BSD License (4 Clause)
 * @uses \SabreAMF\Constants
 * @uses Constants
 * @uses \SabreAMF\AMF3_Serializer
 * @uses \SabreAMF\AMF3_Wrapper
 * @uses \SabreAMF\ITypedObject
 */
class Serializer extends \SabreAMF\Serializer
{

    /**
     * writeAMFData 
     * 
     * @param mixed $data 
     * @param int $forcetype 
     * @return mixed 
     */
    public function writeAMFData($data, $forcetype = null)
    {

        //If theres no type forced we'll try detecting it
        if (is_null($forcetype)) {
            $type = false;

            // NULL type
            if (!$type && is_null($data))
                $type = Constants::DT_NULL;

            // Boolean
            if (!$type && is_bool($data))
                $type = Constants::DT_BOOL;

            // Number
            if (!$type && (is_int($data) || is_float($data)))
                $type = Constants::DT_NUMBER;

            // String (a long one)
            if (!$type && is_string($data) && strlen($data) > 65536)
                $type = Constants::DT_LONGSTRING;

            // Normal string
            if (!$type && is_string($data))
                $type = Constants::DT_STRING;

            // Checking if its an array
            if (!$type && is_array($data)) {
                if ($this->isPureArray($data)) {
                    $type = Constants::DT_ARRAY;
                } else {
                    $type = Constants::DT_MIXEDARRAY;
                }
            }

            // Its an object
            if (!$type && is_object($data)) {

                // If its an AMF3 wrapper.. we treat it as such
                if ($data instanceof \SabreAMF\AMF3\Wrapper)
                    $type = Constants::DT_AMF3;

                else if ($data instanceof DateTime)
                    $type = Constants::DT_DATE;

                // We'll see if its registered in the classmapper
                else if ($this->getRemoteClassName(get_class($data)))
                    $type = Constants::DT_TYPEDOBJECT;

                // Otherwise.. check if it its an TypedObject
                else if ($data instanceof \SabreAMF\ITypedObject)
                    $type = Constants::DT_TYPEDOBJECT;

                // If everything else fails, its a general object
                else
                    $type = Constants::DT_OBJECT;
            }

            // If everything failed, throw an exception
            if ($type === false) {
                throw new Exception('Unhandled data-type: ' . gettype($data));
                return null;
            }
        } else
            $type = $forcetype;

        $this->stream->writeByte($type);

        switch ($type) {

            case Constants::DT_NUMBER : return $this->stream->writeDouble($data);
            case Constants::DT_BOOL : return $this->stream->writeByte($data == true);
            case Constants::DT_STRING : return $this->writeString($data);
            case Constants::DT_OBJECT : return $this->writeObject($data);
            case Constants::DT_NULL : return true;
            case Constants::DT_MIXEDARRAY : return $this->writeMixedArray($data);
            case Constants::DT_ARRAY : return $this->writeArray($data);
            case Constants::DT_DATE : return $this->writeDate($data);
            case Constants::DT_LONGSTRING : return $this->writeLongString($data);
            case Constants::DT_TYPEDOBJECT : return $this->writeTypedObject($data);
            case Constants::DT_AMF3 : return $this->writeAMF3Data($data);
            default : throw new Exception('Unsupported type: ' . gettype($data));
                return false;
        }
    }

    /**
     * writeMixedArray 
     * 
     * @param array $data 
     * @return void
     */
    public function writeMixedArray($data)
    {

        $this->stream->writeLong(0);
        foreach ($data as $key => $value) {
            $this->writeString($key);
            $this->writeAMFData($value);
        }
        $this->writeString('');
        $this->stream->writeByte(Constants::DT_OBJECTTERM);
    }

    /**
     * writeArray 
     * 
     * @param array $data 
     * @return void
     */
    public function writeArray($data)
    {

        if (!count($data)) {
            $this->stream->writeLong(0);
        } else {
            end($data);
            $last = key($data);
            $this->stream->writeLong($last + 1);
            for ($i = 0; $i <= $last; $i++) {
                if (isset($data[$i])) {
                    $this->writeAMFData($data[$i]);
                } else {
                    $this->stream->writeByte(Constants::DT_UNDEFINED);
                }
            }
        }
    }

    /**
     * writeObject 
     * 
     * @param object $data 
     * @return void
     */
    public function writeObject($data)
    {

        foreach ($data as $key => $value) {
            $this->writeString($key);
            $this->writeAmfData($value);
        }
        $this->writeString('');
        $this->stream->writeByte(Constants::DT_OBJECTTERM);
        return true;
    }

    /**
     * writeString 
     * 
     * @param string $string 
     * @return void
     */
    public function writeString($string)
    {

        $this->stream->writeInt(strlen($string));
        $this->stream->writeBuffer($string);
    }

    /**
     * writeLongString 
     * 
     * @param string $string 
     * @return void
     */
    public function writeLongString($string)
    {

        $this->stream->writeLong(strlen($string));
        $this->stream->writeBuffer($string);
    }

    /**
     * writeTypedObject 
     * 
     * @param object $data 
     * @return void
     */
    public function writeTypedObject($data)
    {

        if ($data instanceof \SabreAMF\ITypedObject) {
            $classname = $data->getAMFClassName();
            $data = $data->getAMFData();
        } else
            $classname = $this->getRemoteClassName(get_class($data));

        $this->writeString($classname);
        return $this->writeObject($data);
    }

    /**
     * writeAMF3Data 
     * 
     * @param mixed $data 
     * @return void 
     */
    public function writeAMF3Data(\SabreAMF\AMF3\Wrapper $data)
    {

        $serializer = new \SabreAMF\AMF3\Serializer($this->stream);
        return $serializer->writeAMFData($data->getData());
    }

    /**
     * Writes a date object 
     * 
     * @param DateTime $data 
     * @return void
     */
    public function writeDate(DateTime $data)
    {

        $this->stream->writeDouble($data->format('U') * 1000);

        // empty timezone
        $this->stream->writeInt(0);
    }

}
