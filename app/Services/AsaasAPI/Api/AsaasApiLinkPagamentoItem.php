<?php
namespace App\Services\AsaasAPI\Api;

class AsaasApiLinkPagamentoItem {

    private $nome;
    private $descricao;
    private $dtFim;
    private $valor;
    private $active;
    private $tipoPagamento;
    private $tipoCobranca;
    private $diasLimitePagamento;
    private $periodo;
    private $maxParcelas;
    private $notificationEnabled;

    public function setNome($nome) {
        $this->nome = $nome;
    }

    public function setDescricao($descricao) {
        $this->descricao = $descricao;
    }

    public function setDtFim($dtFim) {
        $this->dtFim = $dtFim;
    }

    public function setValor($valor) {
        $this->valor = $valor;
    }

    public function setActive($active) {
        $this->active = $active;
    }

    /*
     * BOLETO, CREDIT_CARD, PIX, UNDEFINED
     */

    public function setTipoPagamento($tipoPagamento) {
        $this->tipoPagamento = $tipoPagamento;
    }

    /**
     * 
     * @param type $tipoCobranca DETACHED,RECURRENT,INSTALLMENT
     */
    public function setTipoCobranca($tipoCobranca) {
        $this->tipoCobranca = $tipoCobranca;
    }

    public function setDiasLimitePagamento($diasLimitePagamento) {
        $this->diasLimitePagamento = $diasLimitePagamento;
    }

    /**
     * 
     * @param type $periodo WEEKLY,BIWEEKLY,QUARTERLY,SEMIANNUALLY,YEARLY
     */
    public function setPeriodo($periodo) {
        $this->periodo = $periodo;
    }

    public function setMaxParcelas($maxParcelas) {
        $this->maxParcelas = $maxParcelas;
    }

    public function setNotificationEnabled($notificationEnabled) {
        $this->notificationEnabled = $notificationEnabled;
    }

    public function getNome() {
        return $this->nome;
    }

    public function getDescricao() {
        return $this->descricao;
    }

    public function getDtFim() {
        return $this->dtFim;
    }

    public function getValor() {
        return $this->valor;
    }

    public function getActive() {
        return $this->active;
    }

    public function getTipoPagamento() {
        return $this->tipoPagamento;
    }

    public function getTipoCobranca() {
        return $this->tipoCobranca;
    }

    public function getDiasLimitePagamento() {
        return $this->diasLimitePagamento;
    }

    public function getPeriodo() {
        return $this->periodo;
    }

    public function getMaxParcelas() {
        return $this->maxParcelas;
    }

    public function getNotificationEnabled() {
        return $this->notificationEnabled;
    }

}
