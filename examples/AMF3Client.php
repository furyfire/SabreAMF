<?php

    /* $Id$ */
    include("vendor/autoload.php");
    
    $client = new \SabreAMF\Client('http://localhost/server.php'); // Set up the client object
  
    $result = $client->sendRequest('myService.myMethod',new \SabreAMF\AMF3\Wrapper(array('myParameter'))); //Send a request to myService.myMethod and send as only parameter 'myParameter'
   
    var_dump($result); //Dump the results


