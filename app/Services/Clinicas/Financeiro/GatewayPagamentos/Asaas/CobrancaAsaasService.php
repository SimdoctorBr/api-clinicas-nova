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
use App\Repositories\Clinicas\Asaas\AssasSubcontasSplitClinicasRepository;

/**
 * Description of Activities
 *
 *  Gera os links de pagamento pelo Pagseguro
 * @author ander
 */
class CobrancaAsaasService extends BaseService {

    private $pacienteId;
    private $pacienteNome;
    private $pacienteEmail;
    private $pacienteCelular;
    private $pacienteCpf;
    private $formaPagamento;
    private $ambienteAsaas;
    private $valor;
    private $idCustomer;
    private $descricao;
    private $arrayFormapag = ['pix', 'cartao'];
    private $dataVencimento;
    private $contasSplit = [];

    /**
     * 
     * @param type $tipoCliente 1- Doutores
     * @param type $tipoId 
     * @param type $tipovalorSplit 1- Percentual, 2 - Valor fixo
     * @param type $valorSplit
     */
    public function setSplitValores($tipoCliente, $tipoId, $tipovalorSplit, $valorSplit) {
        $this->contasSplit[] = [
            'tipoCliente' => $tipoCliente,
            'tipoId' => $tipoId,
            'tipoValorSplit' => $tipovalorSplit,
            'valorSplit' => $valorSplit,
        ];
    }

    public function setDataVencimento($dataVencimento) {
        $this->dataVencimento = $dataVencimento;
    }

    public function setDescricao($descricao) {
        $this->descricao = $descricao;
    }

    public function setValor($valor) {
        $this->valor = $valor;
    }

    public function setPacienteId($pacienteId) {
        $this->pacienteId = $pacienteId;
    }

    public function setPacienteNome($pacienteNome) {
        $this->pacienteNome = $pacienteNome;
    }

    public function setPacienteEmail($pacienteEmail) {
        $this->pacienteEmail = $pacienteEmail;
    }

    public function setPacienteCelular($pacienteCelular) {
        $this->pacienteCelular = $pacienteCelular;
    }

    public function setPacienteCpf($pacienteCpf) {
        $this->pacienteCpf = $pacienteCpf;
    }

    /**
     * 
     * @param type $formaPagamento ['pix', 'cartao']
     */
    public function setFormaPagamento($formaPagamento) {
        $this->formaPagamento = $formaPagamento;
    }

    public function setAmbienteAsaas($ambienteAsaas) {
        $this->ambienteAsaas = $ambienteAsaas;
    }

    public function setIdCustomer($idCustomer) {
        $this->idCustomer = $idCustomer;
    }

    public function createCobrancaConsulta($idDominio, $idConsulta, $rowConfigAsaas = null, $rowConsulta = null, $plBeneficioHabilitado = false) {

        $AsaasConfigService = new AsaasConfigService;
        $ConsultaService = new ConsultaService;

//        dd($this->contasSplit);
        if (empty($rowConfigAsaas)) {
            $rowConfigAsaas = $AsaasConfigService->getConfig($idDominio);
        }
        if ($rowConfigAsaas->habilitado == 1) {
            if (empty($rowConsulta)) {
                $rowConsulta = $ConsultaService->getById($idDominio, $idConsulta);
            }

            $emailPaciente = $this->pacienteEmail;
            $celularPaciente = $this->pacienteCelular;
            $cpfPaciente = $this->pacienteCpf;
            $formaPag = $this->formaPagamento;
            $nomePaciente = $this->pacienteNome;
            $idPaciente = $this->pacienteId;
            $idCustomer = $this->idCustomer;
            $idReferencia = 'consv_' . $idConsulta;

            if (!$rowConfigAsaas) {
                return $this->returnError(null, 'Este perfil não possui o Asaas configurado.');
//            } elseif (empty($formaPag)) {
//                return $this->returnError(null, 'Forma de pagamento não informada');
            } elseif (!empty($formaPag) and $formaPag != 'pix' and $formaPag != 'cartao') {
                return $this->returnError(null, 'Forma de pagamento  inválida.');
            } elseif (!empty($formaPag) and !in_array($formaPag, $this->arrayFormapag)) {
                return $this->returnError(null, 'Forma de pagamento  inválida.');
            } elseif (empty($emailPaciente)) {
                return $this->returnError(null, 'Infome o e-mail do paciente');
            } elseif (!filter_var($emailPaciente, FILTER_VALIDATE_EMAIL)) {
                return $this->returnError(null, 'E-mail do paciente inválido');
            } elseif (empty($cpfPaciente)) {
                return $this->returnError(null, 'Informe o CPF do paciente');
            } elseif (!Functions::validateCPF($cpfPaciente)) {
                return $this->returnError(null, 'CPF do paciente inválido.');
            }

            $descricaoItemPagSeg = [];
            $valorTotalProc = 0;
            $valorDesconto = 0;
            $valorAcrescimo = 0;
            $valorBruto = $this->valor;
            $valorLiquido = $this->valor;

            //VErifica cliente assas
            //verifica e adicionando paciente no Asaas
            if (empty($idCustomer)) {
                $insertCustomer = $AsaasConfigService->insertCustomerAssas($idDominio, $this->ambienteAsaas, $rowConfigAsaas->apiKey,
                        $idPaciente, $nomePaciente, $cpfPaciente, $emailPaciente);
                if ($insertCustomer['success']) {
                    $idCustomer = $insertCustomer['data']['idCustomer'];
                }
            }

            //
//                    else {
//                        $cliAsaas = $PacientesAsaasPagamentosCliente->updatePacienteAsaasCliente($identificador, $rowConsulta->idClienteAsaas, [
//                            'email' => $emailPaciente,
//                                ], $rowConfigAsaas);
//                    }
            //fim CLiente assas 
//FAZER
            $qrPlBenPendencia = false;
            if ($plBeneficioHabilitado) {
                $PacientesAsaasPagamentosRepository = new PacientesAsaasPagamentosRepository();
                $rowAssinaturaPag = $PacientesAsaasPagamentosRepository->verificaAssinaturaAtivaPacienteId($identificador, $rowConsulta->pacientes_id);

                $qrPlBenPendencia = $PacientesAsaasPagamentosRepository->verificaAssinaturaPendencias($identificador, $rowAssinaturaPag->id);

                if (!$rowAssinaturaPag and !$qrPlBenPendencia) {
                    $valorLiquido = Functions::calcularValorDescontoPlBeneficio($valorBruto, $rowAssinaturaPag->desconto_tipo, $rowAssinaturaPag->desconto_valor);
                }
            }


            $PacientesAsaasPagamentosService = new PacientesAsaasPagamentosService();

            //lançar cobrança

            $dataVenc = $AsaasConfigService->calculaDiasProxVencimento($rowConfigAsaas->dias_venc_cobranca);

            $AsaasApiCobranca = new AsaasApiCobrancas($this->ambienteAsaas, $rowConfigAsaas->apiKey);
            $AsaasApiCobrancasItem = new AsaasApiCobrancasItem();
            $AsaasApiCobrancasItem->setCustomerId($idCustomer);
            $AsaasApiCobrancasItem->setNome($this->descricao);
            $AsaasApiCobrancasItem->setDescricao($this->descricao);
            $AsaasApiCobrancasItem->setDtVencimento($dataVenc);
            $AsaasApiCobrancasItem->setValor($valorLiquido);
            $AsaasApiCobrancasItem->setTipoPagamento(AsaasApiCommons::sistemaToTipoPagamentoAsaas($this->formaPagamento));
            $AsaasApiCobrancasItem->setPeriodo('');

            ///SPLIT
            $dadosSplitHistorico = [];
            $AssasSubcontasSplitClinicasRepository = new AssasSubcontasSplitClinicasRepository;
            if (count($this->contasSplit) > 0) {
                foreach ($this->contasSplit as $dadosSplit) {
                    $rowDoutSPlit = $AssasSubcontasSplitClinicasRepository->getByTipoClienteId($idDominio, $dadosSplit['tipoCliente'], $dadosSplit['tipoId']);
                    if ($rowDoutSPlit) {
                        $AsaasApiCobrancasItem->setSplitPagamento($rowDoutSPlit->walletId, $dadosSplit['tipoValorSplit'], $dadosSplit['valorSplit']);
                        $dadosSplitHistorico[$rowDoutSPlit->walletId] = $dadosSplit;
                    }
                }
            }



            $linkPagAsaas = $AsaasApiCobranca->insertUpdateCobranca($AsaasApiCobrancasItem);

            if (isset($linkPagAsaas->errors)) {
                return $this->returnError($linkPagAsaas->errors[0]->description, 'Ocorreu um erro ao gerar o link de pagamento');
            }


            $linkPagamentoAsaas = $linkPagAsaas->invoiceUrl;

            $PacientesAsaasPagamentosService = new PacientesAsaasPagamentosService();


            //Adicionando o split
            if (count($dadosSplitHistorico) > 0 && isset($linkPagAsaas->split) && count($linkPagAsaas->split) > 0) {
                foreach ($linkPagAsaas->split as $rowS) {
                    $PacientesAsaasPagamentosService->setPagSplit($dadosSplitHistorico[$rowS->walletId]['tipoCliente'],
                            $dadosSplitHistorico[$rowS->walletId]['tipoId'],
                            $dadosSplitHistorico[$rowS->walletId]['tipoValorSplit'],
                            $dadosSplitHistorico[$rowS->walletId]['valorSplit'],
                            $rowS->totalValue, $rowS->id);
                }
            }





            $PacientesAsaasPagamentosService->setTipo_registro(2);
            $PacientesAsaasPagamentosService->setId_registro($linkPagAsaas->id);
            $PacientesAsaasPagamentosService->setIdDominio($idDominio);
            $PacientesAsaasPagamentosService->setConsultas_id($idConsulta);
            $PacientesAsaasPagamentosService->setPacientes_id($idPaciente);
            $PacientesAsaasPagamentosService->setData_vencimento($dataVenc);
            $PacientesAsaasPagamentosService->setLink_pagamento($linkPagAsaas->invoiceUrl);
            $PacientesAsaasPagamentosService->setLink_boleto_pdf($linkPagAsaas->bankSlipUrl);
            $PacientesAsaasPagamentosService->setValor_bruto($valorBruto);
            $PacientesAsaasPagamentosService->setValor($valorLiquido);
            $PacientesAsaasPagamentosService->setCategoria_cobranca(2);
//  

            if ($plBeneficioHabilitado) {
                $PlanoBeneficioConsOrcService = new PlanoBeneficioConsOrcService();
                if (!$qrPlBenPendencia) {
                    $PacientesAsaasPagamentosService->setPlano_beneficio_id($rowAssinaturaPag->idPlano);
                    if ($rowAssinaturaPag->desconto_tipo == 1) {
                        $PacientesAsaasPagamentosService->setPlano_percent_desconto($rowAssinaturaPag->desconto_valor);
                        $dadosUpdateAsaas['percentual_desconto'] = $rowAssinaturaPag->desconto_valor;
                    }//                 
                    $Consultas->atualizarConsulta($identificador, $idConsulta, $dadosUpdateAsaas);
                } else {
                    $PlanoBeneficioConsOrcService->setPossui_pendencia(1);
                }
//
                $PlanoBeneficioConsOrcService->setTipo(1);
                $PlanoBeneficioConsOrcService->setId_tipo($idConsulta);
                $PlanoBeneficioConsOrcService->setPlanos_beneficios_id($rowAssinaturaPag->idPlano);
                $PlanoBeneficioConsOrcService->setPl_nome($rowAssinaturaPag->nomePlano);
                $PlanoBeneficioConsOrcService->setPl_percentual($rowAssinaturaPag->desconto_valor);
                $PlanoBeneficioConsOrcService->setIdentificador($identificador);
                $PlanoBeneficioConsOrcService->insertUpdate();
            }

            $PacientesAsaasPagamentosService->insertUpdate();

            return $this->returnSuccess([
                        'linkPagamento' => $linkPagamentoAsaas
            ]);
        }
    }

    public function getById($idDominio, $idPacienteAss) {
        
    }

    public function delete($idDominio, $id) {
        
    }

    public function refunds($idDominio, $id) {
        
    }

    public function update($idDominio, $id, array $dados) {
        
    }
}
