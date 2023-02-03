<?php

namespace App\Services\AsaasAPI\Api;

class AsaasApiAssinaturaItem {

    private $baseUrl = 'subscriptions';
    private $customerId;
    private $tipoPagamento;
    private $dtProxVencimento;
    private $valor;
    private $periodo;
    private $descricao;
    private $percentualMulta;
    private $percentualJurosAoMes;
    private $maxMensalidades;
    private $externalReference;
    private $dataFinalAssinatura;
    private $status;
    private $split;
    private $creditCard;
    private $creditCardHolderInfo;
    private $desconto;
    private $creditCardToken;
    private $remoteIp;
    private $updatePendingPayments = false;

    public function setUpdatePendingPayments($updatePendingPayments) {
        $this->updatePendingPayments = $updatePendingPayments;
    }

    public function getUpdatePendingPayments() {
        return $this->updatePendingPayments;
    }

    public function setTipoPagamento($tipoPagamento) {
        $this->tipoPagamento = $tipoPagamento;
    }

    public function setCustomerId($customerId) {
        $this->customerId = $customerId;
    }

    public function setDtProxVencimento($dtProxVencimento) {
        $this->dtProxVencimento = $dtProxVencimento;
    }

    public function setValor($valor) {
        $this->valor = $valor;
    }

    /**
     * 
     * @param type $periodo -  'semanal, mensal, quinzenal, semestral e anual
     */
    public function setPeriodo($periodo) {
        $this->periodo = $periodo;
    }

    public function setDescricao($descricao) {
        $this->descricao = $descricao;
    }

    public function setPercentualMulta($percentualMulta) {
        $this->percentualMulta = $percentualMulta;
    }

    public function setPercentualJurosAoMes($percentualJurosAoMes) {
        $this->percentualJurosAoMes = $percentualJurosAoMes;
    }

    public function setMaxMensalidades($maxMensalidades) {
        $this->maxMensalidades = $maxMensalidades;
    }

    public function setExternalReference($externalReference) {
        $this->externalReference = $externalReference;
    }

    public function setDataFinalAssinatura($dataFinalAssinatura) {
        $this->dataFinalAssinatura = $dataFinalAssinatura;
    }

    public function getCustomerId() {
        return $this->customerId;
    }

    public function getTipoPagamento() {
        return $this->tipoPagamento;
    }

    public function getDtProxVencimento() {
        return $this->dtProxVencimento;
    }

    public function getValor() {
        return $this->valor;
    }

    public function getPeriodo() {
        return $this->periodo;
    }

    public function getDescricao() {
        return $this->descricao;
    }

    public function getPercentualMulta() {
        return $this->percentualMulta;
    }

    public function getPercentualJurosAoMes() {
        return $this->percentualJurosAoMes;
    }

    public function getMaxMensalidades() {
        return $this->maxMensalidades;
    }

    public function getExternalReference() {
        return $this->externalReference;
    }

    public function getDataFinalAssinatura() {
        return $this->dataFinalAssinatura;
    }

    public function getStatus() {
        return $this->status;
    }

    public function getSplit() {
        return $this->split;
    }

    public function getCreditCard() {
        return $this->creditCard;
    }

    public function getCreditCardHolderInfo() {
        return $this->creditCardHolderInfo;
    }

    public function getCreditCardToken() {
        return $this->creditCardToken;
    }

    public function getRemoteIp() {
        return $this->remoteIp;
    }

    public function getDesconto() {
        return $this->desconto;
    }

    /**
     * 
     * @param type $tipo
     * @param type $valor
     * @param type $diaLimite
     */
    public function setDesconto($tipo, $valor, $diaLimite) {

        $this->desconto = [
            'tipo' => $tipo,
            'valor' => $valor,
            'diaLimite' => $diaLimite,
        ];
    }

    /**
     * 
     * @param type $nome
     * @param type $numeroCartao
     * @param type $mesExpiracao
     * @param type $anoExpiracao
     * @param type $ccv
     * @return type
     */
    public function setDadosCartaoCredito($nome, $numeroCartao, $mesExpiracao, $anoExpiracao, $ccv, $remoteIp, $creditCardToken = null) {
        $this->creditCard = [
            'holderName' => $nome,
            'number' => $numeroCartao,
            'expiryMonth' => $mesExpiracao,
            'expiryYear' => $anoExpiracao,
            'ccv' => $ccv,
        ];

        $this->creditCardToken = $creditCardToken;
        $this->remoteIp = $remoteIp;

    }

    public function setDadosCartaoCreditoTitular($nome, $email, $cpfCnpj, $cep, $phone, $enderecoNumero, $enderecoComplemento = null, $celular = null) {
        $this->creditCardHolderInfo = [
            'name' => $nome,
            'email' => $email,
            'cpfCnpj' => $cpfCnpj,
            'postalCode' => $cep,
            'addressNumber' => $enderecoNumero,
            'addressComplement' => $enderecoComplemento,
            'phone' => $phone,
            'mobilePhone' => $celular,
        ];

    }

    /**
     * 
     * @param type $walletId
     * @param type $tipoValor fixo, percentual
     * @param type $valor
     * @return type
     */
    public function setSplitPagamento($walletId, $tipoValor = 'fixo', $valor) {
        $this->split = [
            'walletId' => $walletId,
        ];
        if ($tipoValor == 'percentual') {
            $this->split['percentualValue'] = $valor;
        } else {
            $this->split['fixedValue'] = $valor;
        }
    }

}
