<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\PagSeguroApi;

/**
 * Description of PagSeguroItensAPI
 *
 * @author ander
 */
class PagSeguroItensAPI {

    private $itemId;
    private $itemDescription;
    private $itemAmount;
    private $itemQuantity;
    private $itemWeight;

    function getItemId() {
        return $this->itemId;
    }

    function getItemDescription() {
        return $this->itemDescription;
    }

    function getItemAmount() {
        return $this->itemAmount;
    }

    function getItemQuantity() {
        return $this->itemQuantity;
    }

    function getItemWeight() {
        return $this->itemWeight;
    }

    function setItemId($itemId) {
        $this->itemId = $itemId;
    }

    function setItemDescription($itemDescription) {
        $this->itemDescription = $itemDescription;
    }

    function setItemAmount($itemAmount) {
        $this->itemAmount = $itemAmount;
    }

    function setItemQuantity($itemQuantity) {
        $this->itemQuantity = $itemQuantity;
    }

    function setItemWeight($itemWeight) {
        $this->itemWeight = $itemWeight;
    }

}
