<?php

class BankartHPPResponse {
    
    private $payment_id = null;
    private $payment_url = null;
    private $error_message = null;
    
    function __construct($bankart_response_body) {       
        if (preg_match("/\d+:https/", $bankart_response_body)) {
            list($this->payment_id, $this->payment_url) = explode(":", $bankart_response_body, 2); 
            $this->payment_url .= '&paymentId=' . $this->payment_id;
        } else if (strpos($bankart_response_body, "!ERROR!") !== false) {
            $this->error_message = $bankart_response_body;
        } else {
            $this->error_message = 'Unexpected response from Bankart: ' . $bankart_response_body;
        }
    }
    
    public function is_error() {
        return $this->payment_id === null || $this->payment_url === null;
    }
   
    function get_payment_id() {
        return $this->payment_id;
    }

    function get_payment_url() {
        return $this->payment_url;
    }

    function get_error_message() {
        return $this->error_message;
    }    
}
