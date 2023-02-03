<?php

namespace App\Services\AsaasAPI\Api;
/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

/**
 * Description of AssasApi
 *
 * @author ander
 */
class AsaasApi {

    private $ambiente;
    private $urlAmbiente = [
        'producao' => 'https://www.asaas.com/api/v3/',
        'sandbox' => 'https://sandbox.asaas.com/api/v3/',
    ];
    private $ApiKey;

    public function setAmbiente($ambiente) {
        $this->ambiente = $ambiente;
    }

    public function setUrlAmbiente($urlAmbiente) {
        $this->urlAmbiente = $urlAmbiente;
    }

    public function setApiKey($ApiKey) {
        $this->ApiKey = $ApiKey;
    }

    public function connect($url, $method = 'GET', $dadosPost = null) {

        $arrayMethods = ['post', 'POST', 'PUT', 'put', 'delete', 'DELETE'];

        $curl = curl_init($this->urlAmbiente[$this->ambiente] . $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        if (in_array($method, $arrayMethods)) {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);

            if ($method == 'POST') {
                curl_setopt($curl, CURLOPT_POST, 1);
            }

//            var_dump(json_encode($dadosPost));
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($dadosPost));
        }

        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type:application/json',
            "access_token: $this->ApiKey"
        ]);

        $response = curl_exec($curl);
        curl_close($curl);
//        var_dump($response);

        return json_decode($response);
    }

}
