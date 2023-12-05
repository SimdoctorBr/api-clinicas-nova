<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\Clinicas\Financeiro\GatewayPagamentos\Asaas;

use App\Services\BaseService;
use App\Repositories\Clinicas\ProcedimentosRepository;
use App\Repositories\Gerenciamento\DominioRepository;
use App\Repositories\Clinicas\Asaas\AsaasConfigRepository;
use App\Services\AsaasAPI\Api\AsaasApiClientes;
use App\Helpers\Functions;
use App\Repositories\Clinicas\Paciente\PacienteRepository;

/**
 * Description of Activities
 *
 * @author ander 
 */
class AsaasConfigService extends BaseService {

    public function getConfig($idDominio) {
        $AsaasConfigRepository = new AsaasConfigRepository;

        $qr = $AsaasConfigRepository->getConfig($idDominio);

        if ($qr) {
            return $this->returnSuccess($qr);
        } else {
            return $this->returnError(null, 'Sem configuração cadastrada');
        }
    }

    public function calculaDiasProxVencimento($assinDiasPrimeiro) {
        $diasVencPrimeiraParcela = 3;
        if (!empty($assinDiasPrimeiro)) {
            $diasVencPrimeiraParcela = $assinDiasPrimeiro;
        }
     
        $dtVencimento = date('Y-m-d', strtotime(date('Y-m-d') . "  +$diasVencPrimeiraParcela day"));

        if (date('w', strtotime($dtVencimento)) == 6) {
            $diasVencPrimeiraParcela = $diasVencPrimeiraParcela + 2;
        } elseif (date('w', strtotime($dtVencimento)) == 0) {
            $diasVencPrimeiraParcela = $diasVencPrimeiraParcela + 1;
        }
        return $dtVencimento = date('Y-m-d', strtotime(date('Y-m-d') . "  +$diasVencPrimeiraParcela day"));
    }

    /**
     * Voncular o paciente do Assas 
     * @param type $idDominio
     * @param type $idPaciente
     * @param type $customerId
     */
    private function vincularPacienteAsaas($idDominio, $idPaciente, $customerId) {
        $AsaasConfigRepository = new AsaasConfigRepository;
        $qr = $AsaasConfigRepository->vincularPacienteAsaas($idDominio, $idPaciente, $customerId);
        if ($qr) {
            return $this->returnSuccess($qr);
        } else {
            return $this->returnError($qr);
        }
    }

    private function insertClienteAssasSimdoctor($idDominio, $idPaciente, $dadosPaciente, $ambienteAsaas, $rowConfigAssas = null) {


        $retorno = null;

        $AsaasConfigRepository = new AsaasConfigRepository;
        if (empty($rowConfigAssas)) {
            $rowConfigAssas = $AsaasConfigRepository->getConfig($idDominio);
        }
        $AssasApiClientes = new AsaasApiClientes($ambienteAsaas, $rowConfigAssas->apiKey);
        $AssasApiClientes->setName($dadosPaciente['nome']);
        $AssasApiClientes->setCpfCnpj(str_replace('/', '', str_replace('-', '', str_replace('.', '', $dadosPaciente['cpf']))));
        $AssasApiClientes->setEmail($dadosPaciente['email']);
        $AssasApiClientes->setExternalReference('sim' . $idPaciente);
        $AssasApiClientes->setNotificationDisabled(false);
        $insertPaciente = $AssasApiClientes->insert();

        if (empty($insertPaciente)) {
            return $this->returnError(null, 'Ocorreu um erro interno, por favor tente mais tarde.<br> Cod. [0]');
        } else {
            if (isset($insertPaciente->id)) {
                $PacienteRepository = new PacienteRepository;
                $PacienteRepository->vincularPacienteAssas($idDominio, $idPaciente, $insertPaciente->id);
            }
        }
        $retorno['response'] = $insertPaciente;
        $retorno['customerId'] = $insertPaciente->id;
        return $this->returnSuccess($retorno);
    }

    /**
     * Verifica se o paciente tem cadastro no Asaas e faz o vincuo com o Simdoctor
     * @param type $idDominio
     * @param type $ambienteAsaas
     * @param type $apiKeyAsaas
     * @param type $idPaciente
     * @param type $nomePaciente
     * @param type $cpfPaciente
     * @param type $emailPaciente
     * @param type $celularPaciente
     */
    public function insertCustomerAssas($idDominio, $ambienteAsaas, $apiKeyAsaas, $idPaciente, $nomePaciente, $cpfPaciente, $emailPaciente, $celularPaciente = null) {
        //VErifica cliente assas
        $AsaasApiClientes = new AsaasApiClientes($ambienteAsaas, $apiKeyAsaas);
        if (!empty($celularPaciente)) {
            $dadosPac['celular'] = $celularPaciente;
        }

        //verifica e adicionando paciente no Asaas 
        $verificaClienteExiste = $AsaasApiClientes->list(['cpfCnpj' => Functions::cpfToNumber($cpfPaciente)]);
        if ($verificaClienteExiste->totalCount > 0) {
            $insertPaciente = ($verificaClienteExiste->data[0]);
            $idCustomer = $insertPaciente->id;
            $this->vincularPacienteAsaas($idDominio, $idPaciente, $insertPaciente->id);
        } else {
            $dadosPac['nome'] = $nomePaciente;
            $dadosPac['cpf'] = $cpfPaciente;
            $dadosPac['email'] = $emailPaciente;
            $cliAsaas = $this->insertClienteAssasSimdoctor($idDominio, $idPaciente, $dadosPac, $ambienteAsaas);
            if (!$cliAsaas['success']) {
                $this->returnError($cliAsaas, 'Ocorreu um erro interno, por favor tente mais tarde.<br> Cod. [1]');
            } else {
                $idCustomer = $cliAsaas['data']['customerId'];
            }
        }

        return $this->returnSuccess(['idCustomer' => $idCustomer]);
    }

}
