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
class ZIAD_Campaing {

    private $title;
    private $message;
    private $sendDate;
    private $sendTime;

    function getTitle() {
        return $this->title;
    }

    function getMessage() {
        return $this->message;
    }

    function getSendDate() {
        return $this->sendDate;
    }

    function getSendTime() {
        return $this->sendTime;
    }

    function setTitle($title) {
        $this->title = $title;
    }

    function setMessage($message) {
        $this->message = $message;
    }

    function setSendDate($sendDate) {
        $this->sendDate = $sendDate;
    }

    function setSendTime($sendTime) {
        $this->sendTime = $sendTime;
    }



}
