<?php

namespace App\Services\AsaasAPI\Api;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\Clinicas;

use App\Services\BaseService;
use DateTime;
use App\Helpers\Functions;
use App\Repositories\Clinicas\Asaas\AssasSubcontasSplitClinicasRepository;

/**
 * Description of Activities
 *
 * @author ander
 */
class AssasSubcontasSplitClinicasService extends BaseService {

    private $id;
    private $identificador;
    private $id_assas_subconta;
    private $tipo_cliente;
    private $id_tipo;
    private $apiKey;
    private $walletId;
    private $accountNumber;
    private $accountAgency;
    private $accountDigit;
    private $tipo_valor_split;
    private $valor_split;
    private $status_conta;

    public function setId($id) {
        $this->id = $id;
    }

    public function setIdentificador($identificador) {
        $this->identificador = $identificador;
    }

    public function setId_assas_subconta($id_assas_subconta) {
        $this->id_assas_subconta = $id_assas_subconta;
    }

    public function setTipo_cliente($tipo_cliente) {
        $this->tipo_cliente = $tipo_cliente;
    }

    public function setId_tipo($id_tipo) {
        $this->id_tipo = $id_tipo;
    }

    public function setApiKey($apiKey) {
        $this->apiKey = $apiKey;
    }

    public function setWalletId($walletId) {
        $this->walletId = $walletId;
    }

    public function setAccountNumber($accountNumber) {
        $this->accountNumber = $accountNumber;
    }

    public function setAccountAgency($accountAgency) {
        $this->accountAgency = $accountAgency;
    }

    public function setAccountDigit($accountDigit) {
        $this->accountDigit = $accountDigit;
    }

    public function setTipo_valor_split($tipo_valor_split) {
        $this->tipo_valor_split = $tipo_valor_split;
    }

    public function setValor_split($valor_split) {
        $this->valor_split = $valor_split;
    }

    public function setStatus_conta($status_conta) {
        $this->status_conta = $status_conta;
    }

    public function getIdStatusPorNome($nome) {

        switch ($nome) {
            case 'NOT_SENT': return 1;
                break;
            case 'PENDING': return 2;
                break;
            case 'AWAITING_APPROVAL': return 3;
                break;
            case 'APPROVED': return 4;
                break;
            case 'REJECTED': return 5;
                break;
        }
    }

    public function getNomeStatusConta($id) {

        switch ($id) {
            case 1: return [
                    'nome' => 'Não enviado',
                    'spanHtml' => '<span style="font-weight:bold:color:#000;">Não enviado</span>'
                ];
                break;
            case 2: return [
                    'nome' => 'Pendente',
                    'spanHtml' => '<span  style="font-weight:bold;color:#FF9800;">Pendente</span>'
                ];
                break;
            case 3: return [
                    'nome' => 'Aguardando aprovação',
                    'spanHtml' => '<span tyle="font-weight:bold;color:#FF9800;">Aguardando aprovação</span>'
                ];
                break;
            case 4: return [
                    'nome' => 'Aprovado',
                    'spanHtml' => '<span tyle="font-weight:bold;color:#4CAF50;">Aprovado</span>'
                ];
                break;
            case 5: return [
                    'nome' => 'Rejeitado',
                    'spanHtml' => '<span tyle="font-weight:bold;color:#ff5722;">Rejeitado</span>'
                ];
                break;
        }
    }

    public function storeUpdate() {

        $AssasSubcontasSplitClinicasRepository = new AssasSubcontasSplitClinicasRepository;
        $campos['tipo_valor_split'] = $this->tipo_valor_split;
        $campos['valor_split'] = $this->valor_split;

        if (!empty($this->id)) {

            $AssasSubcontasSplitClinicasRepository->update($this->identificador, $this->id, $campos);
            return $this->id;
        } else {

            $campos['identificador'] = $this->identificador;
            $campos['accountNumber'] = $this->accountNumber;
            $campos['accountAgency'] = $this->accountAgency;
            $campos['accountDigit'] = $this->accountDigit;
            $campos['id_assas_subconta'] = $this->id_assas_subconta;
            $campos['tipo_cliente'] = $this->tipo_cliente;
            $campos['id_tipo'] = $this->id_tipo;
            $campos['status_conta'] = $this->status_conta;
            $campos['data_cad'] = date('Y-m-d H:i:s');
            $campos['administrador_id_cad'] = $_SESSION['id_LOGADO'];
            $campos['apiKey']['encrypt'] = 1;
            $campos['apiKey']['valor'] = $this->apiKey;
            $campos['walletId']['encrypt'] = 1;
            $campos['walletId']['valor'] = $this->walletId;
            return $qr = $AssasSubcontasSplitClinicasRepository->store($this->identificador, $campos);
        }
    }

    public function getByDoutorId($idDominio, $doutorId) {

        $AssasSubcontasSplitClinicasRepository = new AssasSubcontasSplitClinicasRepository;
        return $AssasSubcontasSplitClinicasRepository->getByDoutorId($idDominio, $doutorId);
    }
}
