<?php

class AllTests
{

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('SabreAMF');

        $suite->addTestSuite('AMF3_Tests');

        return $suite;
    }
}
