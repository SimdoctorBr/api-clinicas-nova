<?php
namespace App\Services\AsaasAPI\Api;


class AsaasApiInfoConta extends AssasApi {

    private $baseUrl = 'myAccount';

    public function __construct($ambiente, $apiKey) {
        $this->setAmbiente($ambiente);
        $this->setApiKey($apiKey);
    }
    
    /**
     * Dados da Empresa
     * @return type
     */
    public function getDadosComerciais() {
        $response = $this->connect("$this->baseUrl/commercialInfo", 'GET');
        return $response;
    }
    /**
     * Tarifas do banco
     * @return type
     */
    public function getTarifas() {
        $response = $this->connect("$this->baseUrl/fees", 'GET');
        return $response;
    }
    /**
     * Documentos para serem enviados na criação de subcontas
     * @return type
     */
    public function getDocumentosParaEnviar() {
        $response = $this->connect("$this->baseUrl/documents", 'GET');
        return $response;
    }
    /**
     * Documentos para serem enviados na criação de subcontas
     * @return type
     */
    public function getSituacaoCadastroConta() {
        $response = $this->connect("$this->baseUrl/status", 'GET');
        return $response;
    }

}
