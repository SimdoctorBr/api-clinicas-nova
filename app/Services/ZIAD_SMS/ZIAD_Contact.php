<?php

namespace App\Services\ZIAD_SMS;
 /* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Contact
 *
 * @author ander
 */
class ZIAD_Contact {
   
    
    private $phone;
    private $message;
    private $id;
    
    function getPhone() {
        return $this->phone;
    }

    function getMessage() {
        return $this->message;
    }

    function getId() {
        return $this->id;
    }

    function setPhone($phone) {
        $this->phone = $phone;
    }

    function setMessage($message) {
        $this->message = $message;
    }

    function setId($id) {
        $this->id = $id;
    }


    
}
