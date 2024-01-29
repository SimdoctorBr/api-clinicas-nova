<?php
namespace App\Services\AsaasAPI\Api;


class AsaasApiCommons extends AssasApi {

    private $baseUrl = 'finance';

    public function __construct($ambiente, $apiKey) {
        $this->setAmbiente($ambiente);
        $this->setApiKey($apiKey);
    }
    
    
    public function getSaldo() {
        $response = $this->connect("$this->baseUrl/balance", 'GET');
        return $response;
    }

}
