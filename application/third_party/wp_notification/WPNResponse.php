<?php

class WPNResponse{
    public $message = '';
    public $error = false;
    public $httpCode = '';
    
    function __construct($message, $httpCode, $error = false){
        $this->message = $message;
        $this->httpCode = $httpCode;
        $this->error = $error;
    }
}
