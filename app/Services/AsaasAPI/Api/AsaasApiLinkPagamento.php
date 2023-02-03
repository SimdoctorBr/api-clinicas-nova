<?php

namespace App\Services\AsaasAPI\Api;

use App\Services\AsaasAPI\Api\AsaasApi;


class AsaasApiLinkPagamento extends AssasApi {

    private $baseUrl = 'paymentLinks';

    public function __construct($ambiente, $apiKey) {
        $this->setAmbiente($ambiente);
        $this->setApiKey($apiKey);
    }

    /**
     * Listar 
     * Filtros: active,includeDeleted,name, offset,limit,
     */
    public function list(Array $urlFiltros = []) {

        $queryParams = [];
        if (count($urlFiltros) > 0) {
            foreach ($urlFiltros as $campo => $valor) {
                $queryParams[] = "$campo=" . trim($valor);
            }
        }
        $queryParams = (count($queryParams) > 0) ? '?' . implode('&', $queryParams) : '';
        $response = $this->connect($this->baseUrl . $queryParams, 'GET');
        return $response;
    }

    public function insertUpdateLink(AssasApiLinkPagamentoItem $DadosPag, $idLink = null) {


        $dados = [
            "name" => $DadosPag->getNome(),
            "billingType" => $DadosPag->getTipoPagamento(),
            "description" => $DadosPag->getDescricao(),
            "endDate" => $DadosPag->getDtFim(),
            "value" => $DadosPag->getValor(),
            "chargeType" => $DadosPag->getTipoCobranca(),
            "subscriptionCycle" => $DadosPag->getPeriodo(),
            "maxInstallmentCount" => $DadosPag->getMaxParcelas(),
            "notificationEnabled" => $DadosPag->getNotificationEnabled(),
        ];

        if (!empty($DadosPag->getDiasLimitePagamento())) {
            $dados ["dueDateLimitDays"] = $DadosPag->getDiasLimitePagamento();
        }


        if (!empty($idLink)) {
            $response = $this->connect($this->baseUrl . '/' . $idLink, 'POST', $dados);
        } else {
            $response = $this->connect($this->baseUrl, 'POST', $dados);
        }

        return $response;
    }

    public function updatePartialFields($idLink, Array $dados) {

        $response = $this->connect($this->baseUrl . '/' . $idLink, 'POST', $dados);
        return $response;
    }

    public function getById($idLink) {
        $response = $this->connect("$this->baseUrl/$idLink");
        return $response;
    }

    public function delete($idLink) {
        $response = $this->connect("$this->baseUrl/$idLink", 'DELETE');
        return $response;
    }

    public function restore($idLink) {
        $response = $this->connect("$this->baseUrl/$idLink/restore", 'POST');
        return $response;
    }

}
