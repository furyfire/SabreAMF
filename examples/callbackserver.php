<?php

    /* $Id: server.php 1218 2006-03-07 23:07:44Z evert $ */

    // Include the server class
    include 'vendor/autoload.php';


    function myCallBack($service,$method,$data) {
        
        return 'hello world';

    }


    // Init server 
    $server = new \SabreAMF\CallbackServer();

    $server->onInvokeService = 'myCallBack';

    $server->exec();


