<?php

namespace App\Services\AsaasAPI\Api;

use App\Services\AsaasAPI\Api\AsaasApi;
use App\Services\AsaasAPI\Api\AsaasApiCommons;

class AsaasApiCobrancas extends AsaasApi {

    private $baseUrl = 'payments';

    public function __construct($ambiente, $apiKey) {
        $this->setAmbiente($ambiente);
        $this->setApiKey($apiKey);
    }

    /**
     * Listar pacientes
     * Filtros: customer=&billingType=&status=&subscription=&installment=&externalReference=&paymentDate=
     * &pixQrCodeId=&anticipated=&paymentDate%5Bge%5D=&paymentDate%5Ble%5D=&dueDate%5Bge%5D=
     * &dueDate%5Ble%5D=&user=&offset=&limit=
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

    /**
     */
    public function insertUpdateCobranca(AsaasApiCobrancasItem $dadosCobranca, $idCobranca = null) {


        $dados = [
            "customer" => $dadosCobranca->getCustomerId(),
            "billingType" => $dadosCobranca->getTipoPagamento(),
            "dueDate" => $dadosCobranca->getDtVencimento(),
            "value" => $dadosCobranca->getValor(),
            "cycle" => AsaasApiCommons::sistemaToTipoPeriodoApiAsaas($dadosCobranca->getPeriodo()),
            "description" => $dadosCobranca->getDescricao(),
            "externalReference" => $dadosCobranca->getExternalReference(),
        ];
        if (!empty($dadosCobranca->getDataFinalAssinatura())) {
            $dados['endDate'] = $dadosCobranca->getDataFinalAssinatura();
        }
        if (!empty($dadosCobranca->getDesconto()) and count($dadosCobranca->getDesconto()) > 0) {
            $dadosDesconto = $dadosCobranca->getDesconto();
            $dados['discount'] = [
                "type" => AsaasApiCommons::sistemaToTipoPagamentoAsaas($dadosDesconto['tipo']),
                "value" => $dadosDesconto['valor'],
                "dueDateLimitDays" => (isset($dadosDesconto['diaLimite'])) ? $dadosDesconto['diaLimite'] : 0
            ];
        }
        if (!empty($dadosCobranca->getPercentualMulta())) {
            $dados['fine'] = [
                "value" => $dadosCobranca->getPercentualMulta(),
            ];
        }
        if (!empty($dadosCobranca->getPercentualJurosAoMes())) {
            $dados['interest'] = [
                "value" => $dadosCobranca->getPercentualJurosAoMes(),
            ];
        }
        if (!empty($dadosCobranca->getMaxMensalidades())) {
            $dados['maxPayments'] = $dadosCobranca->getMaxMensalidades();
        }

//        if ($tipoAssinatura == 'CREDIT_CARD') {
//            $dados['creditCard'] = $dadosCobranca->getCreditCard();
//            $dados['creditCardHolderInfo'] = $dadosCobranca->getCreditCardHolderInfo();
//            $dados['creditCardToken'] = $dadosCobranca->getCreditCardHolderInfo();
//            $dados['creditCardToken'] = $dadosCobranca->getCreditCardToken();
//            $dados['remoteIp'] = $dadosCobranca->getRemoteIp();
//        }

        if (!empty($dadosCobranca->getContasSplit())) {
            $dados['split'] = $dadosCobranca->getContasSplit();
        }

       
        if (!empty($idCobranca)) {
            $response = $this->connect($this->baseUrl . '/' . $idCobranca, 'POST', $dados);
        } else {
            $response = $this->connect($this->baseUrl, 'POST', $dados);
        }


        return $response;
    }

    public function getById($idCobranca) {

        $response = $this->connect("$this->baseUrl/$idCobranca");
        return $response;
    }

    /*     * Atualiza somente alguns campos
     * 
     */

    public function updateFields($idCobranca, Array $dadosUpdate) {
        $response = $this->connect("$this->baseUrl/$idCobranca", 'POST', $dadosUpdate);
        return $response;
    }

    public function delete($idCobranca) {
        $response = $this->connect("$this->baseUrl/$idCobranca", 'DELETE');
        return $response;
    }

    public function estornar($idCobranca) {
        $response = $this->connect("$this->baseUrl/$idCobranca/refund", 'POST');
        return $response;
    }

    public function getPixQrCode($idCobranca) {
        $response = $this->connect("$this->baseUrl/$idCobranca/pixQrCode", 'GET');
        return $response;
    }

}
