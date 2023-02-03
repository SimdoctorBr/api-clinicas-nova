<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\Clinicas\Financeiro\GatewayPagamentos\Asaas;

use App\Services\BaseService;
use DateTime;
use App\Helpers\Functions;
use App\Services\Clinicas\Financeiro\GatewayPagamentos\Asaas\AsaasConfigService;
use App\Services\Clinicas\ConsultaService;
use App\Services\AsaasAPI\Api\AsaasApiClientes;
use App\Repositories\Clinicas\PacientesAsaasPagamentosRepository;
use App\Repositories\Clinicas\Asaas\AsaasConfigRepository;
use App\Services\Clinicas\Financeiro\GatewayPagamentos\Asaas\PacientesAsaasPagamentosService;
use App\Repositories\Clinicas\PacienteRepository;
use App\Services\AsaasAPI\Api\AsaasApiCobrancas;
use App\Services\AsaasAPI\Api\AsaasApiCobrancasItem;
use App\Services\AsaasAPI\Api\AsaasApiCommons;
use App\Services\Clinicas\Paciente\PlanoBeneficio\PlanoBeneficioConsOrcService;
use App\Repositories\Clinicas\StatusRefreshRepository;
use App\Services\AsaasAPI\Api\AsaasApiAssinaturas;
use App\Services\AsaasAPI\Api\AsaasApiAssinaturaItem;

/**
 * Description of Activities
 *
 *  Gera os links de pagamento pelo Pagseguro
 * @author ander
 */
class AssinaturaAsaasService extends BaseService {

    private $idDominio;
    private $nomeCompleto;
    private $idPaciente;
    private $email;
    private $cpf;
    private $celular;
    private $cep;
    private $endereco;
    private $numero;
    private $idCustomer;
    private $tipoPagamento;
    private $valor;
    private $descricao;
    private $cartaoNome;
    private $cartaoNumero;
    private $cartaoValidadeMes;
    private $cartaoValidadeAno;
    private $cartaoCodSeguranca;
    private $ambienteAsaas;
    private $planoBeneficioId;
    private $arrayFormaPagTabela = ['cartao_credito' => 2];
    private $idAssinaturaAsaas;

    public function setIdAssinaturaAsaas($idAssinaturaAsaas) {
        $this->idAssinaturaAsaas = $idAssinaturaAsaas;
    }

    public function setDescricao($descricao) {
        $this->descricao = $descricao;
    }

    public function setIdDominio($idDominio) {
        $this->idDominio = $idDominio;
    }

    public function setNomeCompleto($nomeCompleto) {
        $this->nomeCompleto = $nomeCompleto;
    }

    public function setIdPaciente($idPaciente) {
        $this->idPaciente = $idPaciente;
    }

    public function setEmail($email) {
        $this->email = $email;
    }

    public function setCpf($cpf) {
        $this->cpf = $cpf;
    }

    public function setCelular($celular) {
        $this->celular = $celular;
    }

    public function setCep($cep) {
        $this->cep = $cep;
    }

    public function setEndereco($endereco) {
        $this->endereco = $endereco;
    }

    public function setNumero($numero) {
        $this->numero = $numero;
    }

    public function setIdCustomer($idCustomer) {
        $this->idCustomer = $idCustomer;
    }

    /**
     * 
     * @param type $tipoPagamento   cartao_credito
     */
    public function setTipoPagamento($tipoPagamento) {
        $this->tipoPagamento = $tipoPagamento;
    }

    public function setValor($valor) {
        $this->valor = $valor;
    }

    public function setCartaoNome($cartaoNome) {
        $this->cartaoNome = $cartaoNome;
    }

    public function setCartaoNumero($cartaoNumero) {
        $this->cartaoNumero = $cartaoNumero;
    }

    public function setCartaoValidadeMes($cartaoValidadeMes) {
        $this->cartaoValidadeMes = $cartaoValidadeMes;
    }

    public function setCartaoValidadeAno($cartaoValidadeAno) {
        $this->cartaoValidadeAno = $cartaoValidadeAno;
    }

    public function setCartaoCodSeguranca($cartaoCodSeguranca) {
        $this->cartaoCodSeguranca = $cartaoCodSeguranca;
    }

    public function setAmbienteAsaas($ambienteAsaas) {
        $this->ambienteAsaas = $ambienteAsaas;
    }

    public function setPlanoBeneficioId($planoBeneficioId) {
        $this->planoBeneficioId = $planoBeneficioId;
    }

    public function createAssinatura($idDominio, $rowConfigAsaas = null) {

        $AsaasConfigService = new AsaasConfigService;
        if (empty($rowConfigAsaas)) {
            $rowConfigAsaas = $AsaasConfigService->getConfig($idDominio);
            $rowConfigAsaas = $rowConfigAsaas['data'];
        }



//VErifica cliente assas
//verifica e adicionando paciente no Asaas
        $idCustomer = $this->idCustomer;
        if (empty($this->idCustomer)) {
            $insertCustomer = $AsaasConfigService->insertCustomerAssas($idDominio, $this->ambienteAsaas, $rowConfigAsaas->apiKey,
                    $idPaciente, $nomePaciente, $cpfPaciente, $emailPaciente);
            if ($insertCustomer['success']) {
                $idCustomer = $insertCustomer['data']['idCustomer'];
            } else {
                return $insertCustomer;
            }
        }



        $AsaasApiAssinaturas = new AsaasApiAssinaturas($this->ambienteAsaas, $rowConfigAsaas->apiKey);
        $AsaasApiAssinaturaItem = new AsaasApiAssinaturaItem;
        $AsaasApiAssinaturaItem->setTipoPagamento($this->tipoPagamento);
        $AsaasApiAssinaturaItem->setCustomerId($idCustomer);
        $AsaasApiAssinaturaItem->setExternalReference('sim-assin-' . $idDominio);
        $AsaasApiAssinaturaItem->setValor($this->valor);
        $AsaasApiAssinaturaItem->setPeriodo('mensal');
        $AsaasApiAssinaturaItem->setDescricao($this->descricao);
//    $AsaasApiAssinaturaItem->setDataFinalAssinatura('2022-12-31');
        if (!empty($rowConfigAsaas->cob_juros_mes)) {
            $AsaasApiAssinaturaItem->setPercentualJurosAoMes($rowConfigAsaas->cob_juros_mes);
        }
        if (!empty($rowConfigAsaas->cob_perc_multa)) {
            $AsaasApiAssinaturaItem->setPercentualMulta($rowConfigAsaas->cob_perc_multa);
        }

        $dtVencimentoPrimeira = $AsaasConfigService->calculaDiasProxVencimento($rowConfigAsaas->assin_dias_primeira);
        if ($this->tipoPagamento == 'cartao_credito') {
            $dtVencimentoPrimeira = date('Y-m-d');
            $AsaasApiAssinaturaItem->setDadosCartaoCredito($this->cartaoNome, $this->cartaoNumero, $this->cartaoValidadeMes, $this->cartaoValidadeAno,
                    $this->cartaoCodSeguranca, $_SERVER['REMOTE_ADDR']);
            $AsaasApiAssinaturaItem->setDadosCartaoCreditoTitular($this->nomeCompleto, $this->email, $this->cpf, $this->cep, '', $this->endereco, $this->numero, $this->celular);
        }


        $AsaasApiAssinaturaItem->setDtProxVencimento($dtVencimentoPrimeira);
        $AsaasApiAssinaturaItem->setUpdatePendingPayments(true);
        $assinatura = $AsaasApiAssinaturas->insertUpdateAssinatura($AsaasApiAssinaturaItem);

        if (isset($assinatura->errors)) {
//            dd($assinatura);
            switch ($assinatura->errors[0]->description) {
                case 'Informe o número de contato com DDD do titular do cartão.':
                    return $this->returnError(null, 'Informe o número de celular com DDD do titular do cartão.');
                    break;
                case 'Informe o endereço do titular do cartão.':
                case 'Informe a cidade do titular do cartão.':
                case 'Informe o estado de residência do titular do cartão.':
                    return $this->returnError(null, 'Por favor, verifique se o CEP e endereço estão corretos.');
                    break;

                default: return $this->returnError(null, $assinatura->errors[0]->description);
                    break;
            }
        }

        $dadosCobranca = $AsaasApiAssinaturas->getCobrancasAssinaturaId($assinatura->id);

        //Salvando a assinatura
        $PacientesAsaasPagamentosService = new PacientesAsaasPagamentosService();
        $PacientesAsaasPagamentosService->setIdDominio($idDominio);
        $PacientesAsaasPagamentosService->setPacientes_id($this->idPaciente);
        $PacientesAsaasPagamentosService->setTipo_registro(1);
        $PacientesAsaasPagamentosService->setId_registro($assinatura->id);
        $PacientesAsaasPagamentosService->setLink_pagamento('');
        $PacientesAsaasPagamentosService->setValor($this->valor);
        $PacientesAsaasPagamentosService->setPlano_beneficio_id($this->planoBeneficioId);
        $idPacPagAssinatura = $PacientesAsaasPagamentosService->insertUpdate();

        $PacientesAsaasPagamentosRepository = new PacientesAsaasPagamentosRepository;
        $rowCobranca = $PacientesAsaasPagamentosRepository->getCobrancaByIdRegistroAssas($dadosCobranca->data[0]->id);

        $PacientesAssasPagamentosCobranca = new PacientesAsaasPagamentosService;
        $PacientesAssasPagamentosCobranca->setIdDominio($idDominio);
        $PacientesAssasPagamentosCobranca->setPacientes_id($this->idPaciente);
        $PacientesAssasPagamentosCobranca->setTipo_registro(2);
        $PacientesAssasPagamentosCobranca->setId_registro($dadosCobranca->data[0]->id);
        $PacientesAssasPagamentosCobranca->setLink_pagamento($dadosCobranca->data[0]->invoiceUrl);
        $PacientesAssasPagamentosCobranca->setLink_boleto_pdf($dadosCobranca->data[0]->bankSlipUrl);
        $PacientesAssasPagamentosCobranca->setValor($this->valor);
        $PacientesAssasPagamentosCobranca->setPlano_beneficio_id($this->planoBeneficioId);
        $PacientesAssasPagamentosCobranca->setData_vencimento($dadosCobranca->data[0]->dueDate);
        $PacientesAssasPagamentosCobranca->setData_pagamento($dadosCobranca->data[0]->confirmedDate);
        $PacientesAssasPagamentosCobranca->setForma_pag($this->arrayFormaPagTabela[$this->tipoPagamento]);
        $PacientesAssasPagamentosCobranca->setLink_comprovante($dadosCobranca->data[0]->transactionReceiptUrl);
        $PacientesAssasPagamentosCobranca->setPac_assas_pag_id($idPacPagAssinatura);
        $PacientesAssasPagamentosCobranca->setNumero_fatura($dadosCobranca->data[0]->invoiceNumber);
        $PacientesAssasPagamentosCobranca->setCategoria_cobranca(1);
//
        if (!empty($dadosCobranca->data[0]->id)) {
            if ($rowCobranca) {
                $PacientesAssasPagamentosCobranca->setId($rowCobranca->id);
                $PacientesAsaasPagamentosRepository->atualizarCampos($idDominio, $rowCobranca->id, [
                    'forma_pag' => $forma_pag,
                    'plano_beneficio_id' => $idPlano,
                    'valor' => $rowPlano->valor,
                ]);
                $idPacPagCobranca = $rowCobranca->id;
            } else {
                $idPacPagCobranca = $PacientesAssasPagamentosCobranca->insertUpdate();
            }


//            $JSON['dadosPag']['urlPagamento'] = $dadosCobranca->data[0]->invoiceUrl;
        }


        return $this->returnSuccess(null, [
                    'idPacienteAsaasPag' => $idPacPagCobranca,
                    'link_pagamento' => $dadosCobranca->data[0]->invoiceUrl
        ]);
    }

    public function alterarAssinatura($idDominio, $rowConfigAsaas = null) {

        $AsaasConfigService = new AsaasConfigService;
        if (empty($rowConfigAsaas)) {
            $rowConfigAsaas = $AsaasConfigService->getConfig($idDominio);
            $rowConfigAsaas = $rowConfigAsaas['data'];
        }

        $AssasApiAssinaturas = new AsaasApiAssinaturas($this->ambienteAsaas, $rowConfigAsaas->apiKey);
        $dadosCobranca = $AssasApiAssinaturas->getCobrancasAssinaturaId($this->idAssinaturaAsaas);

//            var_dump($dadosCobranca);

        $dadosAssinaturaUpdate['updatePendingPayments'] = 'true';
        $dadosAssinaturaUpdate['value'] = $this->valor;
        $dadosAssinaturaUpdate['cycle'] = AsaasApiCommons::sistemaToTipoPeriodoApiAsaas('mensal');
        $dadosAssinaturaUpdate['description'] = $this->descricao;

        $updateAssinatura = $AssasApiAssinaturas->updateFieldsAssinatura($this->idAssinaturaAsaas, $dadosAssinaturaUpdate);
        $dataUltimaCobranca = $dadosCobranca->data[0]->dueDate;
//        dd($updateAssinatura);
        if (empty($updateAssinatura)) {
            return $this->returnError('', 'Ocorreu um erro ao alterar o plano.');
        } else
        if (!empty($updateAssinatura) and isset($updateAssinatura->errors)) {
            return $this->returnError($updateAssinatura->errors[0]->description, 'Ocorreu um erro ao alterar o plano.');
        } else {
            return $this->returnSuccess([
                        'dataUltimaCobranca' => $dataUltimaCobranca,
            ]);
        }
    }

}
