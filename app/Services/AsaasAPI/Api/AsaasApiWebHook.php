<?php

namespace App\Services\AsaasAPI\Api;

use App\Services\AsaasAPI\Api\AsaasApi;

/**
 * Description of AssasApi
 *
 * @author ander
 */
class AsaasApiWebHook extends AssasApi {

    private $baseUrl = 'webhook';

    public function __construct($ambiente, $apiKey) {
        $this->setAmbiente($ambiente);
        $this->setApiKey($apiKey);
    }

    public function criaAlteraWebhookCobranca($urlWebhook, $email, $habilitar = 1) {

        $dadosPost['url'] = $urlWebhook;
        $dadosPost['email'] = $email;
        $dadosPost['apiVersion'] = 3;
        $dadosPost['enabled'] = ($habilitar == 1) ? true : false;
        $dadosPost['interrupted'] = false;
        $dadosPost['authToken'] = md5(base64_encode('simdoctor'));

        $response = $this->connect("$this->baseUrl", 'POST', $dadosPost);
        return $response;
    }

    public function getInfoWebhookCobranca() {
        $this->connect("$this->baseUrl", 'GET');
    }

    public function criaAlteraWebhookNotasFiscais() {
        $this->connect("$this->baseUrl/invoice", 'POST', $dadosPost);
    }

    public function criaAlteraWebhookTransferencia() {
        $this->connect("$this->baseUrl/transfer", 'POST', $dadosPost);
    }

}
