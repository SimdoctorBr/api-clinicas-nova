<?php

namespace App\Services\AsaasAPI\Api;

use App\Services\AsaasAPI\Api\AsaasApi;
use App\Services\AsaasAPI\Api\AsaasApiCommons;

class AsaasApiAssinaturas extends AsaasApi {

    private $baseUrl = 'subscriptions';

    public function __construct($ambiente, $apiKey) {
        $this->setAmbiente($ambiente);
        $this->setApiKey($apiKey);
    }

    /**
     * Listar 
     * Filtros: customer,billingType,offset,limit,includeDeleted,includeDeleted
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

    public function insertUpdateAssinatura(AsaasApiAssinaturaItem $DadosAssinatura, $idAssinatura = null) {

        $tipoAssinatura = AsaasApiCommons::sistemaToTipoPagamentoAsaas($DadosAssinatura->getTipoPagamento());
        $dados = [
            "customer" => $DadosAssinatura->getCustomerId(),
            "billingType" => $tipoAssinatura,
            "cycle" => AsaasApiCommons::sistemaToTipoPeriodoApiAsaas($DadosAssinatura->getPeriodo()),
        ];

        if ($DadosAssinatura->getTipoPagamento() !== null) {
            $dados['billingType'] = $tipoAssinatura;
        }
        if ($DadosAssinatura->getDtProxVencimento() !== null) {
            $dados['nextDueDate'] = $DadosAssinatura->getDtProxVencimento();
        }
        if ($DadosAssinatura->getValor() !== null) {
            $dados['value'] = $DadosAssinatura->getValor();
        }
        if ($DadosAssinatura->getDescricao() !== null) {
            $dados['description'] = $DadosAssinatura->getDescricao();
        }
        if ($DadosAssinatura->getExternalReference() !== null) {
            $dados['externalReference'] = $DadosAssinatura->getExternalReference();
        }
        if (!empty($DadosAssinatura->getDataFinalAssinatura())) {
            $dados['endDate'] = $DadosAssinatura->getDataFinalAssinatura();
        }
        if (!empty($DadosAssinatura->getDesconto()) and count($DadosAssinatura->getDesconto()) > 0) {
            $dadosDesconto = $DadosAssinatura->getDesconto();
            $dados['discount'] = [
                "type" => AsaasApiCommons::sistemaToTipoPagamentoAsaas($dadosDesconto['tipo']),
                "value" => $dadosDesconto['valor'],
                "dueDateLimitDays" => (isset($dadosDesconto['diaLimite'])) ? $dadosDesconto['diaLimite'] : 0
            ];
        }
        if (!empty($DadosAssinatura->getPercentualMulta())) {
            $dados['fine'] = [
                "value" => $DadosAssinatura->getPercentualMulta(),
            ];
        }
        if (!empty($DadosAssinatura->getPercentualJurosAoMes())) {
            $dados['interest'] = [
                "value" => $DadosAssinatura->getPercentualJurosAoMes(),
            ];
        }
        if (!empty($DadosAssinatura->getMaxMensalidades())) {
            $dados['maxPayments'] = $DadosAssinatura->getMaxMensalidades();
        }

        if ($tipoAssinatura == 'CREDIT_CARD') {

            $dados['creditCard'] = $DadosAssinatura->getCreditCard();
            $dados['creditCardHolderInfo'] = $DadosAssinatura->getCreditCardHolderInfo();
            $dados['creditCardToken'] = $DadosAssinatura->getCreditCardHolderInfo();
            $dados['creditCardToken'] = $DadosAssinatura->getCreditCardToken();
            $dados['remoteIp'] = $DadosAssinatura->getRemoteIp();
        }

        if (!empty($DadosAssinatura->getSplit())) {
            $dados['split'] = $DadosAssinatura->getSplit();
        }



        if (!empty($idAssinatura)) {
            $dados['updatePendingPayments'] = $DadosAssinatura->getUpdatePendingPayments();
//            var_dump($dados);
            $response = $this->connect($this->baseUrl . '/' . $idAssinatura, 'POST', $dados);
        } else {
            $response = $this->connect($this->baseUrl, 'POST', $dados);
        }

        return $response;
    }

    /**
     * Atauliza somente alguns campos de acorodo com a api
     * @param type $idAssinatura
     * @param type $dados
     * @return type
     */
    public function updateFieldsAssinatura($idAssinatura,$dados) {

        $response = $this->connect($this->baseUrl . '/' . $idAssinatura, 'POST', $dados);
        return $response;
    }

    public function getById($idAssinatura) {

        $response = $this->connect("$this->baseUrl/$idAssinatura");
        return $response;
    }

    public function getCobrancasAssinaturaId($idAssinatura) {

        $response = $this->connect("$this->baseUrl/$idAssinatura/payments");
        return $response;
    }

    public function delete($idAssinatura) {
        $response = $this->connect("$this->baseUrl/$idAssinatura", 'DELETE');
        return $response;
    }

    /**
     * 
     * @param type $idAssinatura
     * @param array $urlFiltros offset,limit,status
     * @return type
     */
    public function listNFsAssinaturaId($idAssinatura, Array $urlFiltros) {

        if (count($urlFiltros) > 0) {
            foreach ($urlFiltros as $campo => $valor) {
                $queryParams[] = "$campo=" . trim($valor);
            }
        }
        $queryParams = (count($queryParams) > 0) ? '?' . implode('&', $queryParams) : '';
        $response = $this->connect("$this->baseUrl/$idAssinatura/invoices" . $queryParams, 'GET');
        return $response;
    }

    public function insertConfigNfAssinaturaId($idAssinatura, AsaasApiAssinaturasInvoceSettings $dadosConfNf) {

        $dadosConfNf['municipalServiceId'] = $dadosConfNf->getMunicipalServiceId();
        $dadosConfNf['municipalServiceCode'] = $dadosConfNf->getMunicipalServiceCode();
        $dadosConfNf['municipalServiceName'] = $dadosConfNf->getMunicipalServiceName();
        $dadosConfNf['deductions'] = $dadosConfNf->getDeductions();
        $dadosConfNf['taxes'] = $dadosConfNf->getTaxes();
        $dadosConfNf['effectiveDatePeriod'] = $dadosConfNf->effectiveDatePeriod();
        $dadosConfNf['daysBeforeDueDate'] = $dadosConfNf->getDaysBeforeDueDate();
        $dadosConfNf['receivedOnly'] = $dadosConfNf->getReceivedOnly();
        $dadosConfNf['observations'] = $dadosConfNf->getObservations();

        $response = $this->connect("$this->baseUrl/$idAssinatura/invoiceSettings", 'POST', $dadosConfNf);
        return $response;
    }

}
