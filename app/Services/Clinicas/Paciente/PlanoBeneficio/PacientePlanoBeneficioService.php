<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\Clinicas\Paciente\PlanoBeneficio;

use App\Services\BaseService;
use App\Repositories\Clinicas\Paciente\PacienteRepository;
use App\Repositories\Clinicas\PacientesAsaasPagamentosRepository;
use App\Helpers\Functions;
use App\Repositories\Clinicas\PlanoBeneficioRepository;
use App\Services\Clinicas\Financeiro\GatewayPagamentos\Asaas\AssinaturaAsaasService;
use App\Repositories\Gerenciamento\DominioRepository;
use App\Services\Clinicas\Financeiro\GatewayPagamentos\Asaas\PacientesAsaasPagamentosService;
use App\Services\Clinicas\Financeiro\GatewayPagamentos\Asaas\CobrancaAsaasService;
use App\Services\AsaasAPI\Api\AsaasApiCobrancas;
use App\Services\AsaasAPI\Api\AsaasApiCobrancasItem;
use App\Services\Clinicas\Financeiro\GatewayPagamentos\Asaas\AsaasConfigService;
use App\Services\AsaasAPI\Api\AsaasApiCommons;
use App\Services\AsaasAPI\Api\AsaasApiAssinaturas;

/**
 * Description of Activities
 *
 * @author ander
 */
class PacientePlanoBeneficioService extends BaseService {

    private $statusArray = ['0' => 'Inativo', 1 => 'Ativo'];
    private $formaPagArray = ['1' => 'Boleto/Pix', 2 => 'Cartão de crédito', 3 => 'Cartão de débito', 4 => 'Pix'];
    private $formaPagInputToBdArray = ['cartao_credito' => 2, 'pix' => 4];

    public function statusCobrancaPorId($idStatusPagamento) {

        switch ($idStatusPagamento) {
            case 0: return ['status' => 'Cancelado', 'color' => '#e96b4a'];
                break;
            case 1: return ['status' => 'Aguardando pagamento', 'color' => '#dc8622'];
                break;
            case 2: return ['status' => 'Pago', 'color' => '#3c763d'];
                break;
            case 3: return ['status' => 'Confirmado', 'color' => '#dc8622'];
                break;
            case 4: return ['status' => 'Vencida', 'color' => '#e96b4a'];
                break;
        }
    }

    /**
     * 
     * @param type $validade  MM/YYYY
     */
    private function verificaValidadeCartao($validade) {

        $validade = explode('/', $validade);
        $retorno = ['msg' => '', 'success' => true];
        if (count($validade) == 0) {
            $retorno['msg'] = "Infome o validade do cartão";
            $retorno['success'] = false;
        } else if ($validade[0] < 1 or $validade[0] > 12) {
            $retorno['msg'] = "Validade do cartão inválido";
            $retorno['success'] = false;
        } else if (strlen($validade[1]) < 4) {
            $retorno['msg'] = "Infome o ano do validadedo cartao com 4 dígitos. Ex.: 07/2022";
            $retorno['success'] = false;
        } else
        if (strtotime($validade[1] . '-' . $validade[0] . '-01') < strtotime(date('Y-m-01'))) {
            $retorno['msg'] = "Cartão vencido";
            $retorno['success'] = false;
        }
        return $retorno;
    }

    private function fieldsResponsePLanoBeneficioPagamentos($row) {
        $dados['id'] = $row->id;
//        $dados['pacientesId'] = $row->pacientes_id;
        $dados['planoBeneficioId'] = $row->plano_beneficio_id;
        $dados['nomePlano'] = $row->nomePlano;
        $dados['valor'] = $row->valor;
        $dados['dataVencimento'] = $row->data_vencimento;
        $dados['dataPagamento'] = $row->data_pagamento;
        $dados['alteracaoPlano'] = ($row->cobranca_alteracao == 1) ? true : false;
        $dados['formaPagamento'] = $this->formaPagArray[$row->forma_pag];
        $dados['dataHoraCadastro'] = $row->data_cad;
        $dados['status'] = [
            'id' => $row->status_cobranca,
            'nome' => $this->statusCobrancaPorId($row->status_cobranca)['status']
        ];
        $dados['linkPagamento'] = $row->link_pagamento;
        $dados['linkComprovante'] = $row->link_comprovante;

        return $dados;
    }

    private function fieldsResponsePLanoBeneficio($row) {

        $dados['id'] = $row->id;
        $dados['planoBeneficioId'] = $row->plano_beneficio_id;
        $dados['nomePlano'] = $row->nomePlano;
        $dados['valor'] = $row->valor;
        $dados['status'] = [
            'id' => $row->status,
            'nome' => $this->statusArray[$row->status],
        ];
        $dados['pendencia'] = (isset($row->totalPendencias) and $row->totalPendencias > 0) ? true : false;
        $dados['dataHoraCadastro'] = $row->data_cad;
        $dados['dataCancelamento'] = $row->data_cancelamento;
        return $dados;
    }

    /**
     * 
     * @param type $formaPagamento  cartao_credito
     * @param type $dadosInput
     * @return type
     */
    private function validaFormaPagamento($formaPagamento, $dadosInput) {

        if ($formaPagamento == 'cartao_credito') {
            $validate = validator($dadosInput, [
                'cartaoNome' => 'required',
                'cartaoNumero' => 'required',
                'cartaoValidade' => 'required',
                'cartaoCodSeguranca' => 'required|numeric',
                'cep' => 'required|numeric',
                'endereco' => 'required',
                'numero' => 'required',
                    ], [
                'cartaoNome.required' => 'Informe o nome no cartão',
                'cartaoNumero.required' => 'Informe o número do cartão',
                'cartaoValidade.required' => 'Informe a validade do cartão',
                'cartaoCodSeguranca.required' => 'Informe o cód. de segurança do cartão',
                'cep.required' => 'Informe o CEP do paciente',
                'endereco.required' => 'Informe o endereço do paciente',
                'numero.required' => 'Informe o número do paciente',
            ]);
            if ($validate->fails()) {
                return $this->returnError(null, $validate->errors()->all()[0]);
            }
            $verificaCartao = $this->verificaValidadeCartao($dadosInput['cartaoValidade']);
            if (!$verificaCartao['success']) {
                return $this->returnError(null, $verificaCartao['msg']);
            }
        }

        return $this->returnSuccess(null);
    }

    public function getPlanoAtivo($idDominio, $pacienteId) {
        $PacienteRepository = new PacienteRepository;
        $rowPaciente = $PacienteRepository->getById($idDominio, $pacienteId, true);
        if (count($rowPaciente) == 0) {
            return $this->returnError(null, 'Paciente não encontrado');
        }

        $PacienteAssasPagamentosRepository = new PacientesAsaasPagamentosRepository;
        $rowPlanoAtivo = $PacienteAssasPagamentosRepository->verificaAssinaturaAtivaPacienteId($idDominio, $pacienteId);

        if ($rowPlanoAtivo) {
            return $this->returnSuccess($this->fieldsResponsePLanoBeneficio($rowPlanoAtivo));
        } else {
            return $this->returnError('', 'O Paciente não possui plano de beneficio ativo');
        }
    }

    public function getAllPlanosBeneficios($idDominio, $pacienteId, $dadosFiltro = null) {

        $PacienteRepository = new PacienteRepository;
        $rowPaciente = $PacienteRepository->getById($idDominio, $pacienteId, true);
        if (count($rowPaciente) == 0) {
            return $this->returnError(null, 'Paciente não encontrado');
        }

        $PacienteAssasPagamentosRepository = new PacientesAsaasPagamentosRepository;
        $qrPlanos = $PacienteAssasPagamentosRepository->verificaHistoricoAssinatura($idDominio, $pacienteId);
        if (count($qrPlanos) > 0) {
            $retorno = null;
            foreach ($qrPlanos as $row) {
                $retorno[] = $this->fieldsResponsePLanoBeneficio($row);
            }
            return $this->returnSuccess($retorno);
        } else {
            return $this->returnSuccess('', 'Sem registros de planos de benefícios para este paciente.');
        }
    }

    public function getPlBeneficioHistoricoPagamento($idDominio, $pacienteId, $plBeneficioContratadoId, $dadosFiltro = null) {
        $PacienteRepository = new PacienteRepository;
        $rowPaciente = $PacienteRepository->getById($idDominio, $pacienteId, true);
        if (count($rowPaciente) == 0) {
            return $this->returnError(null, 'Paciente não encontrado');
        }

        $page = (isset($dadosFiltro['page']) and!empty($dadosFiltro['page'])) ? $dadosFiltro['page'] : 1;
        $perPage = 100;
        if (isset($dadosFiltro['perPage']) and!empty($dadosFiltro['perPage'])) {
            $perPage = ($dadosFiltro['perPage'] > 100) ? 100 : $dadosFiltro['perPage'];
        }

        $filtro['dataVencimento'] = (isset($dadosFiltro['dataVencimento']) and!empty($dadosFiltro['dataVencimento'])) ? $dadosFiltro['dataVencimento'] : null;
        $filtro['dataVencimentoFim'] = (isset($dadosFiltro['dataVencimentoFim']) and!empty($dadosFiltro['dataVencimentoFim'])) ? $dadosFiltro['dataVencimentoFim'] : null;

        $PacienteAssasPagamentosRepository = new PacientesAsaasPagamentosRepository;
        $qrPlanos = $PacienteAssasPagamentosRepository->verificaHistoricoAssinaturaCobrancas($idDominio, $pacienteId, $plBeneficioContratadoId, null, $filtro, $page, $perPage);
//        dd($qrPlanos);

        if (count($qrPlanos) > 0) {
            $retorno = null;
            foreach ($qrPlanos['results'] as $row) {

                $retorno[] = $this->fieldsResponsePLanoBeneficioPagamentos($row);
            }

            $qrPlanos['results'] = $retorno;
            return $this->returnSuccess($qrPlanos);
        } else {
            return $this->returnSuccess('', 'Sem registros de pagamentos para esta assinatura de plano de beneficio');
        }
    }

    public function cancelarPlano($idDominio, $pacienteId, $idPlanoBeneficioPaciente) {

        $PacienteAssasPagamentosRepository = new PacientesAsaasPagamentosRepository;
        $rowPlano = $PacienteAssasPagamentosRepository->getAssinaturaById($idPlanoBeneficioPaciente, $idDominio);

        if (!$rowPlano) {
            return $this->returnError(null, 'Plano de benefício do paciente não encontrado.');
        }

        $PacienteAssasPagamentosRepository->cancelarPlano($idDominio, $idPlanoBeneficioPaciente);
        return $this->returnSuccess(null, 'Plano de benefício do paciente cancelado com sucesso');
    }

    public function buscaPlanoAtivoPacienteByCpf($idDominio, $cpf) {

        $PacienteRepository = new PacienteRepository;
        $rowPaciente = $PacienteRepository->getAll($idDominio, ['cpf' => Functions::cpfToNumber($cpf)]);
        if (count($rowPaciente) == 0) {
            return $this->returnError(null, 'Paciente não encontrado');
        }
        $rowPaciente = $rowPaciente[0];

        $PacienteAssasPagamentosRepository = new PacientesAsaasPagamentosRepository;
        $rowPlanoAtivo = $PacienteAssasPagamentosRepository->verificaAssinaturaAtivaPacienteId($idDominio, $rowPaciente->id);

        if ($rowPlanoAtivo) {
            return $this->returnSuccess($this->fieldsResponsePLanoBeneficio($rowPlanoAtivo));
        } else {
            return $this->returnError('', 'O Paciente não possui plano de beneficio ativo');
        }
    }

    public function contrataPlano($idDominio, $pacienteId, $dadosInput) {

        $Dominio = new DominioRepository;
        $rowDominio = $Dominio->getById($idDominio);
        if ($rowDominio->habilita_assas != 1) {
            return $this->returnError(null, 'O Assas não está habilitado neste perfil');
        }

        $PacienteRepository = new PacienteRepository;
        $rowPaciente = $PacienteRepository->getAll($idDominio, ['id' => $pacienteId]);

        if (count($rowPaciente) == 0) {
            return $this->returnError(null, 'Paciente não encontrado');
        }
        $PlanoBeneficioRepository = new PlanoBeneficioRepository;
        $rowPlanoBen = $PlanoBeneficioRepository->getById($idDominio, $dadosInput['planoBeneficioId']);
        if (!$rowPlanoBen) {
            return $this->returnError(null, 'Plano de benefício não encontrado');
        }

        $qrVerificaPlanoContratado = $this->getPlanoAtivo($idDominio, $pacienteId);

        if ($qrVerificaPlanoContratado['success']) {
            return $this->returnError(null, 'O Paciente já possui um plano de benefício.');
        }

        if (!Functions::validateCPF($dadosInput['cpf'])) {
            return $this->returnError(null, 'Cpf inválido');
        }


        $AssinaturaAsaasService = new AssinaturaAsaasService();
        $AssinaturaAsaasService->setAmbienteAsaas($rowDominio->ambiente_assas);
        $AssinaturaAsaasService->setIdPaciente($pacienteId);
        $AssinaturaAsaasService->setIdCustomer($rowPaciente[0]->idCustomerAsaas);
        $AssinaturaAsaasService->setNomeCompleto($dadosInput['nomeCompleto']);
        $AssinaturaAsaasService->setCpf($dadosInput['cpf']);
        $AssinaturaAsaasService->setCelular($dadosInput['celular']);
        $AssinaturaAsaasService->setEmail($dadosInput['email']);
        $AssinaturaAsaasService->setPlanoBeneficioId($dadosInput['planoBeneficioId']);
        $AssinaturaAsaasService->setValor($rowPlanoBen->valor);
        $AssinaturaAsaasService->setDescricao($rowPlanoBen->nome);
        $AssinaturaAsaasService->setTipoPagamento($dadosInput['formaPagamento']);

        $validaFormaPagamento = $this->validaFormaPagamento($dadosInput['formaPagamento'], $dadosInput);

        if (!$validaFormaPagamento['success']) {
            return $validaFormaPagamento;
        }

        if ($dadosInput['formaPagamento'] == 'cartao_credito') {
            $cartaoValidade = explode('/', $dadosInput['cartaoValidade']);
            $AssinaturaAsaasService->setCartaoNome($dadosInput['cartaoNome']);
            $AssinaturaAsaasService->setCartaoNumero($dadosInput['cartaoNumero']);
            $AssinaturaAsaasService->setCartaoValidadeMes($cartaoValidade[0]);
            $AssinaturaAsaasService->setCartaoValidadeAno($cartaoValidade[1]);
            $AssinaturaAsaasService->setCartaoCodSeguranca($dadosInput['cartaoCodSeguranca']);
            $AssinaturaAsaasService->setCep($dadosInput['cep']);
            $AssinaturaAsaasService->setEndereco($dadosInput['endereco']);
            $AssinaturaAsaasService->setNumero($dadosInput['numero']);
        }


        $resultAssinatura = $AssinaturaAsaasService->createAssinatura($idDominio);
        if (!$resultAssinatura['success']) {
            return $resultAssinatura;
        } else {
            $qrPlanoAtivo = $this->getPlanoAtivo($idDominio, $pacienteId);

//            $idCobranca = $resultAssinatura['data']['idPacienteAsaasPag'];
////            $dadosCobranca

            return $this->returnSuccess($qrPlanoAtivo['data'], 'Plano contratado com sucesso.');
        }
    }

    public function alterarPlano($idDominio, $pacienteId, $dadosInput) {




        $Dominio = new DominioRepository;
        $rowDominio = $Dominio->getById($idDominio);
        if ($rowDominio->habilita_assas != 1) {
            return $this->returnError(null, 'O Assas não está habilitado neste perfil');
        }
        $PacienteRepository = new PacienteRepository;
        $rowPaciente = $PacienteRepository->getAll($idDominio, ['id' => $pacienteId]);

        if (count($rowPaciente) == 0) {
            return $this->returnError(null, 'Paciente não encontrado');
        }
        $PlanoBeneficioRepository = new PlanoBeneficioRepository;
        $rowPlanoBenInput = $PlanoBeneficioRepository->getById($idDominio, $dadosInput['planoBeneficioId']);
        if (!$rowPlanoBenInput) {
            return $this->returnError(null, 'Plano de benefício não encontrado');
        }

        $PacienteAssasPagamentosRepository = new PacientesAsaasPagamentosRepository;
        $rowPlanoAtivo = $PacienteAssasPagamentosRepository->verificaAssinaturaAtivaPacienteId($idDominio, $pacienteId);

        if (!$rowPlanoAtivo) {
            return $this->returnError(null, 'O não possui um plano de benefício ativo.');
        }

        if ($rowPlanoAtivo->status_cobranca == 0) {
            return $this->returnError(null, 'Não é possivel alterar o plano está inativo');
        }
        if ($rowPlanoAtivo->totalPendencias) {
            return $this->returnError(null, 'Não é possivel alterar o plano pois existem pendências no pagamento');
        }

        $PacientesAsaasPagamentosService = new PacientesAsaasPagamentosService;
        $qrExisteAlteracao = $PacientesAsaasPagamentosService->verificaMudancaPlanoHist($idDominio, $rowPlanoAtivo->id);
        if ($qrExisteAlteracao['success']) {
            return $this->returnError(null, 'Já existe uma alteração de plano lançada.');
        }
        $dtVencimentoPrimeira = '';

        //valor do plano novo maior que o atual
        if ($rowPlanoAtivo->valor < $rowPlanoBenInput->valor) {
            $rowUltCobranca = $PacienteAssasPagamentosRepository->verificaHistoricoAssinaturaCobrancas($idDominio, $pacienteId, $rowPlanoAtivo->id, null, ['anterioresDataAtual' => 1]);
//
            if ($dadosInput['diaAlteracao'] == 1) {


                //Hoje, gerar uma cobrança proporcional
                $dataUltimoPag = (count($rowUltCobranca) == 1) ? $rowUltCobranca[0]->data_pagamento : $rowUltCobranca[0]->data_vencimento;
                $dadosValorProporcional = $PacientesAsaasPagamentosService->calculoProporcionalMudancaPlano($rowPlanoAtivo->valor, $rowPlanoBenInput->valor, date('Y-m-d'), $dataUltimoPag);

//                //lançar a cobrança
                $AsaasConfigService = new AsaasConfigService;
                $rowConfigAsaas = $AsaasConfigService->getConfig($idDominio);

                $rowConfigAsaas = $rowConfigAsaas['data'];

                $AssasApiCobrancas = new AsaasApiCobrancas($rowDominio->ambiente_assas, $rowConfigAsaas->apiKey);
                $AssasApiCobrancasItem = new AsaasApiCobrancasItem();
                $AssasApiCobrancasItem->setCustomerId($rowPaciente[0]->idCustomerAsaas);
                $AssasApiCobrancasItem->setTipoPagamento(AsaasApiCommons::sistemaToTipoPagamentoAsaas($dadosInput['formaPagamento']));
                $AssasApiCobrancasItem->setValor($dadosValorProporcional['valorNovo']);
                $AssasApiCobrancasItem->setDtVencimento(date('Y-m-d'));
                $AssasApiCobrancasItem->setDescricao("Alteração de plano:  " . $rowPlanoAtivo->nomePlano . ' para ' . $rowPlanoBenInput->nome);
                $AssasApiCobrancasItem->setExternalReference('sim-assin-' . $idDominio);

                if ($dadosInput['formaPagamento'] == 'cartao_credito') {
                    $cartaoValidade = explode('/', $dadosInput['cartaoValidade']);
                    $AssasApiCobrancasItem->setDadosCartaoCredito($dadosInput['cartaoNome'], $dadosInput['cartaoNumero'], $cartaoValidade[0], $cartaoValidade[1],
                            $dadosInput['cartaoCodSeguranca'], request()->server('REMOTE_ADDR')); //
                    $AssasApiCobrancasItem->setDadosCartaoCreditoTitular($dadosInput['nomeCompleto'], $dadosInput['email'], $dadosInput['email'], $dadosInput['cep'], '', $dadosInput['endereco'], $dadosInput['numero'], $dadosInput['celular']);
                }
                $dadosCobrancaAlteracao = $AssasApiCobrancas->insertUpdateCobranca($AssasApiCobrancasItem);

                if (isset($dadosCobrancaAlteracao->errors)) {
                    return $this->returnError(null, 'Ocorreu um erro ao altear o plano, por favor tente mais tarde');
                }
//
//                //salvando a conbraçãod e alteração no bd
                $PacientesAsaasPagamentosService = new PacientesAsaasPagamentosService;
                $PacientesAsaasPagamentosService->setIdDominio($idDominio);
                $PacientesAsaasPagamentosService->setPacientes_id($pacienteId);
                $PacientesAsaasPagamentosService->setTipo_registro(2);
                $PacientesAsaasPagamentosService->setId_registro($dadosCobrancaAlteracao->id);
                $PacientesAsaasPagamentosService->setLink_pagamento($dadosCobrancaAlteracao->invoiceUrl);
                $PacientesAsaasPagamentosService->setValor($dadosValorProporcional['valorNovo']);
                $PacientesAsaasPagamentosService->setPlano_beneficio_id($dadosInput['planoBeneficioId']);
                $PacientesAsaasPagamentosService->setData_vencimento(date('Y-m-d'));
                $PacientesAsaasPagamentosService->setCategoria_cobranca(1);
                $PacientesAsaasPagamentosService->setForma_pag($this->formaPagInputToBdArray[$dadosInput['formaPagamento']]);
                $PacientesAsaasPagamentosService->setPac_assas_pag_id($rowPlanoAtivo->id);
                $PacientesAsaasPagamentosService->setCobranca_alteracao(1);
                $idCobrancaALteracao = $PacientesAsaasPagamentosService->insertUpdate();

                $idPacPLanoHistorico = $PacientesAsaasPagamentosService->insertHistoricoAlterPlBeneficio($idDominio, $pacienteId,
                        $rowPlanoAtivo->idPlano, $rowPlanoAtivo->nomePlano, $rowPlanoBenInput->id, $rowPlanoBenInput->nome, $rowPlanoAtivo->id, date('Y-m-d'), 1);

                $PacientesAsaasPagamentosService->vinculaCobrancaMudancaPlanoHist($idDominio, $idPacPLanoHistorico['data']['id'], $idCobrancaALteracao);
                return $this->returnSuccess([
                            'linkPagamento' => $dadosCobrancaAlteracao->invoiceUrl
                                ], 'Alteração realizada com sucesso.');
            } else {
                $atualizarAssinatura = false;
            }
        } else {
            $atualizarAssinatura = false;
        }

//        var_dump($atualizarAssinatura);
        if (!$atualizarAssinatura) {


            $AssinaturaAsaasService = new AssinaturaAsaasService();

            $AssinaturaAsaasService->setValor($rowPlanoBenInput->valor);
            $AssinaturaAsaasService->setDescricao($rowPlanoBenInput->nome);
            $AssinaturaAsaasService->setAmbienteAsaas($rowDominio->ambiente_assas);
            $AssinaturaAsaasService->setIdAssinaturaAsaas($rowPlanoAtivo->id_registro);
            $AssinaturaAsaasService->alterarAssinatura($idDominio, null, $rowDominio->ambiente_assas);

            $atualizaAssinatura = $AssinaturaAsaasService->alterarAssinatura($idDominio);
            if (!$atualizaAssinatura['success']) {
                return $atualizaAssinatura;
            }

            $dataUltimaCobranca = $atualizaAssinatura['data']['dataUltimaCobranca'];

            $PacientesAsaasPagamentosService->insertHistoricoAlterPlBeneficio($idDominio, $pacienteId,
                    $rowPlanoAtivo->idPlano, $rowPlanoAtivo->nomePlano, $rowPlanoBenInput->id, $rowPlanoBenInput->nome, $rowPlanoAtivo->id, $dataUltimaCobranca, 2);

            return $this->returnSuccess('', 'Alteração realizada com sucesso. A alteração será válida a partir da próxima fatura');
        }
    }

    public function cancelarAlteracaoPlano($idDominio, $pacienteId) {
        $Dominio = new DominioRepository;
        $rowDominio = $Dominio->getById($idDominio);
        if ($rowDominio->habilita_assas != 1) {
            return $this->returnError(null, 'O Assas não está habilitado neste perfil');
        }
        $PacienteRepository = new PacienteRepository;
        $rowPaciente = $PacienteRepository->getAll($idDominio, ['id' => $pacienteId]);

        if (count($rowPaciente) == 0) {
            return $this->returnError(null, 'Paciente não encontrado');
        }

        $PacienteAssasPagamentosRepository = new PacientesAsaasPagamentosRepository;
        $rowPlanoAtivo = $PacienteAssasPagamentosRepository->verificaAssinaturaAtivaPacienteId($idDominio, $pacienteId);
        if (!$rowPlanoAtivo) {
            return $this->returnError(null, 'O não possui um plano de benefício ativo.');
        }

        $PacientesAsaasPagamentosRepository = new PacientesAsaasPagamentosRepository;
        $PacientesAsaasPagamentosService = new PacientesAsaasPagamentosService;
        $rowExisteAlteracao = $PacientesAsaasPagamentosService->verificaMudancaPlanoHist($idDominio, $rowPlanoAtivo->id);
        if (!$rowExisteAlteracao['success']) {
            return $this->returnError(null, 'Não existe  alteração de plano lançada.');
        }
        $rowPacAssasPagHistAlteracao = $rowExisteAlteracao['data'];

        $AsaasConfigService = new AsaasConfigService;
        $rowConfigAsaas = $AsaasConfigService->getConfig($idDominio);
        $rowConfigAsaas = $rowConfigAsaas['data'];
        $AssasApiCobrancas = new AsaasApiCobrancas($rowDominio->ambiente_assas, $rowConfigAsaas->apiKey);

        if ($rowPacAssasPagHistAlteracao->tipo_alteracao == 1) {

            $rowCobranca = $PacientesAsaasPagamentosRepository->getCobrancaById($idDominio, $rowPacAssasPagHistAlteracao->pac_assas_pag_id_cobranca);

            $response = $AssasApiCobrancas->delete($rowCobranca->id_registro);
            $PacienteAssasPagamentosRepository->excluirMudancaPlanoHist($idDominio, $rowPacAssasPagHistAlteracao->id, $rowPacAssasPagHistAlteracao->pac_assas_pag_id_cobranca);

            return $this->returnSuccess(null, "Alteração cancelada com sucesso!");
        } else {
            //proxima fatura
            if (!empty($rowPacAssasPagHistAlteracao->pac_assas_pag_id_cobranca)) {

                $AssasApiCobrancas = new AssasApiCobrancas($rowDominio->ambiente_assas, $rowConfigAsaas->apiKey);
                $dadosCobranca = $AssasApiCobrancas->getById($rowPacAssasPagHistAlteracao->idCobrancaAssas);
                if (empty($dadosCobranca)) {
                    return $this->returnError(null, "Ocorrreu um erro ao cancelar a alteração");
                }


                $tt = $AssasApiCobrancas->updateFields($dadosCobranca->id, [
                    'customer' => $dadosCobranca->customer,
                    'billingType' => $dadosCobranca->billingType,
                    'value' => $rowPacAssasPagHistAlteracao->valorPlanoDe,
                    'description' => $rowPacAssasPagHistAlteracao->nomePlanoDe,
                ]);

                $PacientesAsaasPagamentosRepository->update($identificador, $rowPacAssasPagHistAlteracao->pac_assas_pag_id_cobranca, [
                    'plano_beneficio_id' => $rowPacAssasPagHistAlteracao->pl_beneficio_id_de,
                    'valor' => $rowPacAssasPagHistAlteracao->valorPlanoDe
                ]);
            }
//
            //voltando para o plano anterior

            $AsaasApiAssinaturas = new AsaasApiAssinaturas($rowDominio->ambiente_assas, $rowConfigAsaas->apiKey);
            $dadosAssinaturaUpdate['updatePendingPayments'] = 'true';
            $dadosAssinaturaUpdate['value'] = $rowPacAssasPagHistAlteracao->valorPlanoDe;
            $dadosAssinaturaUpdate['cycle'] = AsaasApiCommons::sistemaToTipoPeriodoApiAsaas('mensal');
            $dadosAssinaturaUpdate['description'] = $rowPacAssasPagHistAlteracao->nomePlanoDe;
            $updateAssinatura = $AsaasApiAssinaturas->updateFieldsAssinatura($rowPlanoAtivo->id_registro, $dadosAssinaturaUpdate);

            $PacientesAsaasPagamentosRepository->excluirMudancaPlanoHist($idDominio, $rowPacAssasPagHistAlteracao->id);
            return $this->returnSuccess(null, "Alteração cancelada com sucesso!");
        }
    }

}
