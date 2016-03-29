<?php

namespace SabreAMF\AMF0;

/**
 * Deserializer 
 * 
 * @package SabreAMF
 * @subpackage AMF0
 * @version $Id$
 * @copyright Copyright (C) 2006-2009 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @licence http://www.freebsd.org/copyright/license.html  BSD License (4 Clause) 
 * @uses \SabreAMF\Constants
 * @uses \SabreAMF\AMF0\Constants
 * @uses \SabreAMF\AMF3\Deserializer
 * @uses \SabreAMF\AMF3\Wrapper
 * @uses \SabreAMF\TypedObject
 */
class Deserializer extends \SabreAMF\Deserializer
{

    /**
     * refList 
     * 
     * @var array 
     */
    private $refList = array();

    /**
     * amf3Deserializer 
     * 
     * @var \SabreAMF\AMF3\Deserializer 
     */
    private $amf3Deserializer = null;

    /**
     * readAMFData 
     * 
     * @param int $settype 
     * @param bool $newscope
     * @return mixed 
     */
    public function readAMFData($settype = null, $newscope = false)
    {

        if ($newscope)
            $this->refList = array();

        if (is_null($settype)) {
            $settype = $this->stream->readByte();
        }

        switch ($settype) {

            case Constants::DT_NUMBER : return $this->stream->readDouble();
            case Constants::DT_BOOL : return $this->stream->readByte() == true;
            case Constants::DT_STRING : return $this->readString();
            case Constants::DT_OBJECT : return $this->readObject();
            case Constants::DT_NULL : return null;
            case Constants::DT_UNDEFINED : return null;
            case Constants::DT_REFERENCE : return $this->readReference();
            case Constants::DT_MIXEDARRAY : return $this->readMixedArray();
            case Constants::DT_ARRAY : return $this->readArray();
            case Constants::DT_DATE : return $this->readDate();
            case Constants::DT_LONGSTRING : return $this->readLongString();
            case Constants::DT_UNSUPPORTED : return null;
            case Constants::DT_XML : return $this->readLongString();
            case Constants::DT_TYPEDOBJECT : return $this->readTypedObject();
            case Constants::DT_AMF3 : return $this->readAMF3Data();
            default : throw new Exception('Unsupported type: 0x' . strtoupper(str_pad(dechex($settype), 2, 0, STR_PAD_LEFT)));
                return false;
        }
    }

    /**
     * readObject 
     * 
     * @return object 
     */
    public function readObject()
    {

        $object = array();
        $this->refList[] = & $object;
        while (true) {
            $key = $this->readString();
            $vartype = $this->stream->readByte();
            if ($vartype == Constants::DT_OBJECTTERM)
                break;
            $object[$key] = $this->readAmfData($vartype);
        }
        if (defined('SABREAMF_OBJECT_AS_ARRAY')) {
            $object = (object) $object;
        }
        return $object;
    }

    /**
     * readReference 
     * 
     * @return object 
     */
    public function readReference()
    {

        $refId = $this->stream->readInt();
        if (isset($this->refList[$refId])) {
            return $this->refList[$refId];
        } else {
            throw new Exception('Invalid reference offset: ' . $refId);
            return false;
        }
    }

    /**
     * readArray 
     * 
     * @return array 
     */
    public function readArray()
    {

        $length = $this->stream->readLong();
        $arr = array();
        $this->refList[]&=$arr;
        while ($length--)
            $arr[] = $this->readAMFData();
        return $arr;
    }

    /**
     * readMixedArray 
     * 
     * @return array 
     */
    public function readMixedArray()
    {

        $highestIndex = $this->stream->readLong();
        return $this->readObject();
    }

    /**
     * readString 
     * 
     * @return string 
     */
    public function readString()
    {

        $strLen = $this->stream->readInt();
        return $this->stream->readBuffer($strLen);
    }

    /**
     * readLongString 
     * 
     * @return string 
     */
    public function readLongString()
    {

        $strLen = $this->stream->readLong();
        return $this->stream->readBuffer($strLen);
    }

    /**
     *  
     * readDate 
     * 
     * @return int 
     */
    public function readDate()
    {

        // Unix timestamp in seconds. We strip the millisecond part
        $timestamp = floor($this->stream->readDouble() / 1000);

        // we are ignoring the timezone
        $timezoneOffset = $this->stream->readInt();
        //if ($timezoneOffset > 720) $timezoneOffset = ((65536 - $timezoneOffset));
        //$timezoneOffset=($timezoneOffset * 60) - date('Z');

        $dateTime = new \DateTime('@' . $timestamp);

        return $dateTime;
    }

    /**
     * readTypedObject 
     * 
     * @return object
     */
    public function readTypedObject()
    {

        $classname = $this->readString();

        $isMapped = false;

        if ($localClassname = $this->getLocalClassName($classname)) {
            $rObject = new $localClassname();
            $isMapped = true;
        } else {
            $rObject = new \SabreAMF\TypedObject($classname, null);
        }
        $this->refList[] = & $rObject;

        $props = array();
        while (true) {
            $key = $this->readString();
            $vartype = $this->stream->readByte();
            if ($vartype == Constants::DT_OBJECTTERM)
                break;
            $props[$key] = $this->readAmfData($vartype);
        }

        if ($isMapped) {
            foreach ($props as $k => $v)
                $rObject->$k = $v;
        } else {
            $rObject->setAMFData($props);
        }

        return $rObject;
    }

    /**
     * readAMF3Data 
     * 
     * @return \SabreAMF\AMF3\Wrapper 
     */
    public function readAMF3Data()
    {

        $amf3Deserializer = new \SabreAMF\AMF3\Deserializer($this->stream);
        return new \SabreAMF\AMF3\Wrapper($amf3Deserializer->readAMFData());
    }

}
