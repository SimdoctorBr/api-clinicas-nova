<?php

namespace App\Services\AsaasAPI\Api;

class AsaasApiCobrancasItem {

    private $customerId;
    private $tipoPagamento;
    private $dtVencimento;
    private $valor;
    private $periodo;
    private $descricao;
    private $percentualMulta;
    private $percentualJurosAoMes;
    private $maxMensalidades;
    private $externalReference;
    private $numeroParcela;
    private $valorParcela;
    private $dataFinalAssinatura;
    private $status;
    private $split;
    private $creditCard;
    private $creditCardHolderInfo;
    private $desconto;
    private $creditCardToken;
    private $remoteIp;
    private $nome;
    private $contasSplit = [];

    /**
     * 
     * @param type $walletId
     * @param type $tipoValor 1 -percentual, 2- fixo, 3 - fixoParcelamento
     * @param type $valor
     */
    public function setSplitPagamento($walletId, $tipoValor, $valor) {

        $split['walletId'] = $walletId;
        switch ($tipoValor) {
            case 1: $split['percentualValue'] = $valor;
                break;
            case 2: $split['fixedValue'] = $valor;
                break;
            case 3:$split['totalFixedValue'] = $valor;
                break;
        }
        $this->contasSplit[] = $split;
    }

    public function getContasSplit() {
        return $this->contasSplit;
    }

    public function getDtVencimento() {
        return $this->dtVencimento;
    }

    public function getNome() {
        return $this->nome;
    }

    public function setValorParcela($valorParcela) {
        $this->valorParcela = $valorParcela;
    }

    public function setNome($nome) {
        $this->nome = $nome;
    }

    /*
     * BOLETO, CREDIT_CARD, PIX, UNDEFINED
     */

    public function setTipoPagamento($tipoPagamento) {
        $this->tipoPagamento = $tipoPagamento;
    }

    public function setCustomerId($customerId) {
        $this->customerId = $customerId;
    }

    public function setDtVencimento($dtVencimento) {
        $this->dtVencimento = $dtVencimento;
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

    public function setNumeroParcela($numeroParcela) {
        $this->numeroParcela = $numeroParcela;
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
}
