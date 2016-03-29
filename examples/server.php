<?php
    include 'vendor/autoload.php';

    // Init server 
    $server = new \SabreAMF\Server();
    
    foreach($server->getRequests() as $request) {  // Loop through requests

        $server->setResponse(  // Send a new response
            $request['response'],  // Connect the request to the response
            \SabreAMF\Constants::R_RESULT, // Either R_RESULT or R_STATUS
            $request['data']  // Any data structure, in this case we are echoing back the original data
        ); 

    }

    $server->sendResponse(); //Send the responses back to the client


