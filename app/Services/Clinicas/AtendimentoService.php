<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\Clinicas;

use App\Services\BaseService;
use DateTime;
use App\Helpers\Functions;
use App\Services\Clinicas\CalculosService;
use App\Repositories\Clinicas\AgendaRepository;
use App\Repositories\Clinicas\AgendaFilaEsperaRepository;
use App\Repositories\Clinicas\DefinicaoMarcacaoGlobalRepository;
use App\Repositories\Clinicas\DefinicaoHorarioRepository;
use App\Repositories\Clinicas\ConsultaRepository;
use App\Repositories\Clinicas\ConvenioRepository;
use App\Repositories\Clinicas\ConsultaStatusRepository;
use App\Repositories\Clinicas\StatusRefreshRepository;
use App\Repositories\Clinicas\Consulta\ConsultaProntuarioRepository;
use App\Repositories\Clinicas\ProcedimentosRepository;
use App\Repositories\Clinicas\Consulta\ConsultaProcedimentoRepository;
use App\Repositories\Clinicas\EncaminhamentoRepository;
use App\Repositories\Clinicas\DoutorRepository;
use App\Services\Clinicas\Paciente\ProntuarioService;
use App\Repositories\Gerenciamento\DominioRepository;
use App\Services\Clinicas\Consulta\ConsultaProcedimentoService;
use App\Services\Clinicas\WebsocketSistemaService;
use App\Repositories\Clinicas\Consulta\ConsultaAtendAbertosRepository;

/**
 * Description of Activities
 *
 * @author ander
 */
class AtendimentoService extends BaseService {

    public function iniciarAtendimento($idDominio, $consultaId) {

        $StatusRefreshRepository = new StatusRefreshRepository();
        $ConsultaRepository = new ConsultaRepository;
        $ConsultaStatusRepository = new ConsultaStatusRepository;
        $rowConsulta = $ConsultaRepository->getById($idDominio, $consultaId);

        if ($rowConsulta) {

            if ($rowConsulta->statusConsulta != 'jaFoiAtendido' and $rowConsulta->statusConsulta != 'estaSendoAtendido') {


                if (empty($rowConsulta->pausada)) {
                    $dados['consulta_id'] = $consultaId;
                    $dados['identificador'] = $idDominio;
                    $dados['status'] = 'estaSendoAtendido';
                    $dados['hora'] = time();
//                                $dados['administrador_id'] = auth('clinicas')->user()->id;
//                                $dados['nome_administrador'] = auth('clinicas')->user()->nome;
                    $idStatus = $ConsultaStatusRepository->alteraStatus($idDominio, $consultaId, $dados);
                    $rowStatus = $ConsultaStatusRepository->getById($idDominio, $idStatus);

                    $StatusRefreshRepository->insertAgenda($idDominio, $rowConsulta->doutores_id);
                } else {
                    $dadosUpdate['pausada'] = null;
                    $ConsultaRepository->updateConsulta($idDominio, $consultaId, $dadosUpdate);
                }


                return $this->returnSuccess(null, 'Status alterado com sucesso');
            } else {
                return $this->returnError(null, array('A consulta está finalizada'));
            }
        } else {
            return $this->returnError(null, array('Consulta não encontrada'));
        }
    }

    /**
     * Inicia, pausa e finaliza o atendimento
     * @param type $idDominio
     * @param type $consultaId
     * @param type $status
     */
    public function alteraAtendimentoStatus($idDominio, $request, $consultaId) {

        $validate = validator($request->input(), [
            'status' => 'required'
                ], [
            'status.required' => 'Informe o status da consulta'
        ]);

        if ($validate->fails()) {
            return $this->returnError($validate->errors(), $validate->errors()->all());
        } else {
            if (!in_array($request->input('status'), array(1, 2, 3))) {
                return $this->returnError(null, array('Status inválido'));
            }

            $StatusRefreshRepository = new StatusRefreshRepository();
            $ConsultaRepository = new ConsultaRepository;
            $ConsultaStatusRepository = new ConsultaStatusRepository;

            $rowConsulta = $ConsultaRepository->getById($idDominio, $consultaId);
//            dd($rowConsulta);
            if ($rowConsulta->statusConsulta != 'jaFoiAtendido') {
                switch ($request->input('status')) {
                    case 1:
                        if ($rowConsulta->statusConsulta != 'estaSendoAtendido') {
                            if (empty($rowConsulta->pausada)) {
                                $dados['consulta_id'] = $consultaId;
                                $dados['identificador'] = $idDominio;
                                $dados['status'] = 'estaSendoAtendido';
                                $dados['hora'] = time();
//                                $dados['administrador_id'] = auth('clinicas')->user()->id;
//                                $dados['nome_administrador'] = auth('clinicas')->user()->nome;
                                $ConsultaStatusRepository->alteraStatus($idDominio, $consultaId, $dados);
                                $StatusRefreshRepository->insertAgenda($idDominio, $rowConsulta->doutores_id);
                            }
                        } else {
                            $dadosUpdate['pausada'] = null;
                            $ConsultaRepository->updateConsulta($idDominio, $consultaId, $dadosUpdate);
                        }
                        break;
                    case 2:if ($rowConsulta->statusConsulta == 'estaSendoAtendido') {
                            $dadosUpdate['pausada'] = 1;
//                            $dadosUpdate['pausa_tempo'] = $duracaoAtual;
                            $ConsultaRepository->updateConsulta($idDominio, $consultaId, $dadosUpdate);
                        }
                        break;
                    case 3:
                        if ($rowConsulta->statusConsulta != 'estaSendoAtendido' and $rowConsulta->statusConsulta != 'faltou'
                                and $rowConsulta->statusConsulta != 'desmarcado') {
                            $dados['consulta_id'] = $consultaId;
                            $dados['identificador'] = $idDominio;
                            $dados['status'] = 'jaFoiAtendido';
                            $dados['hora'] = time();
//                                $dados['administrador_id'] = auth('clinicas')->user()->id;
//                                $dados['nome_administrador'] = auth('clinicas')->user()->nome;
                            $ConsultaStatusRepository->alteraStatus($idDominio, $consultaId, $dados);
                            $ConsultaRepository->updateConsulta($idDominio, $consultaId, $dadosUpdate);
                        }
                        break;
                }
            } else {
                return $this->returnError(null, array('A consulta está finalizada'));
            }
        }
    }

    public function getAgendamentos($idDominio, $request) {
        $validate = validator($request->query(), []);
    }

    public function salvarAtendimento($idDominio, $consultaId, $dadosInput) {



        $StatusRefreshRepository = new StatusRefreshRepository;
        $ConsultaProcedimentoService = new ConsultaProcedimentoService;
        $EncaminhamentoRepository = new EncaminhamentoRepository();
        $ConsultaProntuarioRepository = new ConsultaProntuarioRepository;
        $ProcedimentosRepository = new ProcedimentosRepository;
        $DominioRepository = new DominioRepository;
        $rowDominio = $DominioRepository->getById($idDominio);

        $ConsultaRepository = new ConsultaRepository;
        $rowConsulta = $ConsultaRepository->getById($idDominio, $consultaId);

        if (!$rowConsulta) {
            return $this->returnError(null, 'Consulta não encontrada');
        }

        if (!empty($rowConsulta->statusConsulta)) {
            $statusConsulta = explode('_', $rowConsulta->statusConsulta);
            if ($statusConsulta[0] == 'jaFoiAtendido') {
                return $this->returnError(null, 'Esta consulta já foi finalizada');
            }
        }

//        if (auth('clinicas')->user()->id == 4055) {
//            $WebsocketSistemaService  =new WebsocketSistemaService();
//            $WebsocketSistemaService->sendUpdateAgenda($idDominio, 'doutor', $rowConsulta->doutores_id, $rowConsulta->data_consulta);
//        } 


        $dadosConsulta = null;

        //Oportunidades
        if (isset($dadosInput['oportunidades']) and!empty($dadosInput['oportunidades'])) {
            $dadosConsulta['oportunidades'] = utf8_encode($dadosInput['oportunidades']);
        }

        //só altera os procedimentos se
        if (empty($rowConsulta->consAtendAbertoId)) {


            if (isset($dadosInput['valorTipoDesconto']) and!empty($dadosInput['valorTipoDesconto'])
                    and
                    isset($dadosInput['valorTipoAcrescimo']) and!empty($dadosInput['valorTipoAcrescimo'])
            ) {

                return $this->returnError(null, 'É permitido somente o desconto ou o acréscimo.');
            }

            //Exclusao de procedimentos
            if (isset($dadosInput['idConsultaProcedimentoExcluido']) and count(array_filter($dadosInput['idConsultaProcedimentoExcluido'])) > 0) {
                foreach ($dadosInput['idConsultaProcedimentoExcluido'] as $idProcDelete) {
                    $ConsultaProcedimentoService->excluir($idDominio, $idProcDelete, $consultaId);
                }
            }

            ///Desconto
//        if ($rowDominio->alteracao_docbizz != 1) { //regar Normal de descontos
            if (isset($dadosInput['valorTipoDesconto']) and!empty($dadosInput['valorTipoDesconto'])) {

                if (!isset($dadosInput['tipoDesconto']) or empty($dadosInput['tipoDesconto'])) {
                    return $this->returnError(null, "Informe o tipo de desconto");
                }

                $tipoDesconto = $dadosInput['tipoDesconto'];
                $valorTipoDesconto = $dadosInput['valorTipoDesconto'];
//            $desconto = $dadosInput['desconto'];

                $dadosConsulta['tipo_desconto'] = $tipoDesconto;
                if ($tipoDesconto == 2) {
                    $dadosConsulta['desconto_reais'] = $valorTipoDesconto;
                    $dadosConsulta['percentual_desconto'] = null;
                } else {
                    $dadosConsulta['desconto_reais'] = null;
                    $dadosConsulta['percentual_desconto'] = $valorTipoDesconto;
                }
            } else {
                $dadosConsulta['desconto_reais'] = null;
                $dadosConsulta['percentual_desconto'] = null;
            }



            ///Acrescimo
            if (isset($dadosInput['valorTipoAcrescimo']) and!empty($dadosInput['valorTipoAcrescimo'])) {

                if (!isset($dadosInput['tipoAcrescimo']) or empty($dadosInput['tipoAcrescimo'])) {
                    return $this->returnError(null, "Informe o tipo de acréscimo");
                }

                $tipoAcrescimo = $dadosInput['tipoAcrescimo'];
                $valorTipoAcrescimo = $dadosInput['valorTipoAcrescimo'];
//            $acrescimo = $dadosInput['acrescimo'];

                $dadosConsulta['acrescimo_tipo'] = $tipoAcrescimo;

                if ($tipoAcrescimo == 2) {
                    $dadosConsulta['acrescimo_valor'] = $valorTipoAcrescimo;
                    $dadosConsulta['acrescimo_percentual'] = null;
                } else {
                    $dadosConsulta['acrescimo_valor'] = null;
                    $dadosConsulta['acrescimo_percentual'] = $valorTipoAcrescimo;
                }
            } else {
                $dadosConsulta['acrescimo_valor'] = null;
                $dadosConsulta['acrescimo_percentual'] = null;
            }
//        } else {
//            $dadosConsulta['tipo_desconto'] = null;
//            $dadosConsulta['desconto_reais'] = null;
//            $dadosConsulta['percentual_desconto'] = null;
//            $dadosConsulta['acrescimo_tipo'] = null;
//            $dadosConsulta['acrescimo_valor'] = null;
//            $dadosConsulta['acrescimo_percentual'] = null;
//        }





            $DoutorRepository = new DoutorRepository;
            $rowDoutor = $DoutorRepository->getById($idDominio, $rowConsulta->doutores_id);

            if (isset($dadosInput['procedimentos']) and count($dadosInput['procedimentos']) > 0) {
                $ConvenioRepository = new ConvenioRepository;
                $qrConvenios = $ConvenioRepository->getAll($idDominio);
                $DADOS_CONVENIO = null;
                if (count($qrConvenios) > 0) {
                    foreach ($qrConvenios as $rowConv) {
                        $DADOS_CONVENIO[$rowConv->id]['id'] = $rowConv->id;
                        $DADOS_CONVENIO[$rowConv->id]['nome'] = $rowConv->nome;
                        $DADOS_CONVENIO[$rowConv->id]['imposto'] = $rowConv->imposto;
                    }
                }



                ////////////////////////////////////
                ///////////Procedimentos//////////
                ////////////////////////////////
                $qrProcConsulta = $ProcedimentosRepository->getByConsultaId($idDominio, $consultaId);
                $idsConsultaProcSelecionado = [];

                $qntTotalProc = array_sum(array_map(function ($item) {
                            if ($item['convenioId'] == 41) {
                                return $item['qnt'];
                            }
                            return 0;
                        }, $dadosInput['procedimentos']));

                foreach ($dadosInput['procedimentos'] as $rowInputProc) {


                    if (isset($rowInputProc['idConsultaProcedimento']) and!empty($rowInputProc['idConsultaProcedimento'])) {
                        $idsConsultaProcSelecionado[] = $rowInputProc['idConsultaProcedimento'];
                    }


                    $rowProcedimento = $ProcedimentosRepository->getById($idDominio, $rowInputProc['id']);

                    $duracao = (isset($rowInputProc['duracao'])) ? $rowInputProc['duracao'] : $rowProcedimento->duracao;

                    if ($rowProcedimento->sobrescreve_retorno == 1) {
                        $verificaSobrescritaRetornoProc = true;
                    }

                    ///Pacote
                    ///Encaminhamento
                    if ($rowProcedimento->possui_parceiro == 1) {

                        $dadosEncaminhamento['consultas_id'] = $consultaId;
                        $idEncaminhamento = $EncaminhamentoRepository->store($idDominio, $dadosEncaminhamento);

                        $dadosEncaminhamentoItens['consultas_guia_encaminhamento_id'] = $idEncaminhamento;
                        $dadosEncaminhamentoItens['procedimento_id'] = $rowInputProc['id'];
                        $dadosEncaminhamentoItens['nome_procedimento'] = $rowProcedimento->procedimento_nome;
                        $dadosEncaminhamentoItens['quantidade'] = $rowInputProc['qnt'];
                        $dadosEncaminhamentoItens['valor'] = $rowInputProc['valor'];
                        $dadosEncaminhamentoItens['doutor_parceiro_id'] = $rowProcedimento->doutor_parceiro_id;
                        $dadosEncaminhamentoItens['nome_parceiro'] = $rowProcedimento->nomeDoutorParceiro;
//                    $dadosEncaminhamentoItens['carteira_virtual_id'] = $this->carteira_virtual_id;

                        $id = $EncaminhamentoRepository->storeEncaminhamentoItem($idDominio, $idEncaminhamento, $dadosEncaminhamentoItens);

                        $rowDoutorParceiro = $DoutorRepository->getById($idDominio, $rowProcedimento->doutor_parceiro_id);

                        $ExecutanteRepasseID = $rowProcedimento->doutor_parceiro_id;
                        $ExecutanteRepasseNome = $rowProcedimento->nomeDoutorParceiro;

                        $idDoutorRepasse = $rowProcedimento->doutor_parceiro_id;
                        $possuiRepasseDoutor = $rowDoutorParceiro->possui_repasse;
                        $tipoRepasseDoutor = $rowDoutorParceiro->tipo_repasse;
                        $valorRepasseDoutor = $rowDoutorParceiro->valor_repasse;
                    } else {

                        $ExecutanteRepasseID = $rowDoutor->id;
                        $ExecutanteRepasseNome = $rowDoutor->nome;

                        $idDoutorRepasse = $rowDoutor->id;
                        $possuiRepasseDoutor = $rowDoutor->possui_repasse;
                        $tipoRepasseDoutor = $rowDoutor->tipo_repasse;
                        $valorRepasseDoutor = $rowDoutor->valor_repasse;
                    }




                    $valorRepasse = null;
                    $tipoRepasse = null;
                    $origemRepasse = null;
                    $percentual_proc_repasse = null;
                    $valor_taxa_tipo_pg = null;
                    $impostoRendaConvenio = $DADOS_CONVENIO[$rowInputProc['convenioId']]['imposto'];

                    $totalProc = ($rowInputProc['qnt'] * $rowInputProc['valor']);
                    $procValorBruto = $totalProc;
                    $procValorUnitario = $rowInputProc['valor'];

                    $baseCalcRepasse = $totalProc - Functions::calcularDescontoPercentual($totalProc, 1, $impostoRendaConvenio); //com desconto de IR do convênio
                    //
                    //
                    //
                    //DOcBizz            
                    $valorTaxaTotal = 0; //enquanto não tem pagamentos por aqui



                    $procTipoDesconto = null;
                    $procTipoDescontoValor = null;
                    $procDesconto = null;
                    $procTipoAcrescimo = null;
                    $procTipoAcrescimoValor = null;
                    $procAcrescimo = null;

                    if ($rowDominio->alteracao_docbizz == 1) {





                        if ($rowInputProc['convenioId'] == 41) {



//           alterações desfeitas para docbiz   
//                                          if (isset($dadosInput['tipoDesconto']) and ! empty($dadosInput['tipoDesconto'])) {
//
//                                $procTipoDesconto = $dadosInput['tipoDesconto'];
//                                $procTipoDescontoValor = $dadosInput['valorTipoDesconto'];
//
//                                if ($procTipoDesconto == 1) {
//
//                                    $procDesconto = Functions::calcularDescontoPercentual($totalProc, 1, $dadosInput['valorTipoDesconto']);
//                                } elseif ($procTipoDesconto == 2) {
//                                    $procDesconto =  ($dadosInput['valorTipoDesconto']/$qntTotalProc) *$rowInputProc['qnt'] ;
//                                }
//
//                                $totalProc = $totalProc - $procDesconto;
//                                $procValorUnitario = $totalProc / $rowInputProc['qnt'];
//                            }
//
//
//
//                            if (isset($dadosInput['tipoAcrescimo']) and ! empty($dadosInput['tipoAcrescimo'])) {
//
//                                $procTipoAcrescimo = $dadosInput['tipoAcrescimo'];
//                                $procTipoAcrescimoValor = $dadosInput['valorTipoAcrescimo'];
//
//                                if ($procTipoAcrescimo == 1) {
//
//                                    $procAcrescimo = Functions::calcularDescontoPercentual($totalProc, 1, $dadosInput['valorTipoAcrescimo']);
//                                } elseif ($procTipoAcrescimo == 2) {
//                                    $procAcrescimo = ($dadosInput['valorTipoAcrescimo']/$qntTotalProc) * $rowInputProc['qnt'] ;
//                                }
//
//                                $totalProc = $totalProc + $procAcrescimo;
//                                $procValorUnitario = $totalProc / $rowInputProc['qnt'];
//                            }





                            $baseCalcRepasse = $totalProc - $valorTaxaTotal;
                            $valor_taxa_tipo_pg = $valorTaxaTotal;
                        } else {
                            $baseCalcRepasse = $totalProc - Functions::calcularDescontoPercentual($totalProc, 1, $impostoRendaConvenio);
                        }
                    }


                    if ($possuiRepasseDoutor == 1) {
                        if ($tipoRepasseDoutor == 1) {//Repasse fixo
                            $tipoRepasse = 1;
                            $origemRepasse = 1;
                            $valorRepasse = Functions::calcularDescontoPercentual($baseCalcRepasse, 1, $valorRepasseDoutor);
                            $percentual_proc_repasse = $valorRepasseDoutor;
                        } else { //Repasse por procedimento
                            $origemRepasse = 2;
                            $rowRepasse = $ProcedimentosRepository->getDadosRepasse($idDominio, $idDoutorRepasse, $rowInputProc['id'], $rowInputProc['convenioId']);

                            if ($rowRepasse) {
                                //*************pegar o valor do procedimento  e calcular o repasse
                                $tipoRepasse = $rowRepasse->tipo_repasse;
                                if (!empty($rowRepasse->possui_repasse) and $rowRepasse->possui_repasse == 1) {
                                    if ($rowRepasse->tipo_repasse == 1) {
                                        $percentual_proc_repasse = $rowRepasse->valor_percentual;
                                        $valorRepasse = Functions::calcularDescontoPercentual($baseCalcRepasse, 1, $rowRepasse->valor_percentual);
                                    } else {
                                        $valorRepasse = $rowInputProc['qnt'] * $rowRepasse->valor_real;
                                    }
                                }
                            }
                        }
                    }






//
//                $id_proc_pacote = $id_proc_pacote_item = null;
//                if ($procedimentosTipoPacote[$chave] == 'pacote' or $procedimentosTipoPacote[$chave] == 'item_pacote') {
//                    $id_proc_pacote = $procedimentosPacoteId[$chave];
//                }
//                if ($procedimentosTipoPacote[$chave] == 'item_pacote') {
//                    $id_proc_pacote_item = $procedimentos_id[$chave];
//                }
////      
//                if ($rowProcedimento->retorno == 1) {
//                    $procRetornoUpdate = true;
//                }
                    //Consultas procedimentos





                    $dadosConsultaProcInsert['id'] = (isset($rowInputProc['idConsultaProcedimento']) and!empty($rowInputProc['idConsultaProcedimento'])) ? $rowInputProc['idConsultaProcedimento'] : '';
                    $dadosConsultaProcInsert['identificador'] = $idDominio;
                    $dadosConsultaProcInsert['consultas_id'] = $consultaId;
                    $dadosConsultaProcInsert['convenios_id'] = $DADOS_CONVENIO[$rowInputProc['convenioId']]['id'];
                    $dadosConsultaProcInsert['nome_convenio'] = $DADOS_CONVENIO[$rowInputProc['convenioId']]['nome'];
                    $dadosConsultaProcInsert['procedimentos_id'] = $rowInputProc['id'];
                    $dadosConsultaProcInsert['nome_proc'] = $rowProcedimento->procedimento_nome;
                    $dadosConsultaProcInsert['valor_proc'] = $procValorUnitario; //$rowInputProc['valor'];
                    $dadosConsultaProcInsert['qnt'] = $rowInputProc['qnt'];
                    $dadosConsultaProcInsert['duracao'] = $duracao;
                    $dadosConsultaProcInsert['valor_repasse'] = $valorRepasse;
                    $dadosConsultaProcInsert['tipo_repasse'] = $tipoRepasse;
                    $dadosConsultaProcInsert['origem_repasse'] = $origemRepasse;
                    $dadosConsultaProcInsert['procedimentos_cat_id'] = $rowProcedimento->procedimento_categoria_id;
                    $dadosConsultaProcInsert['procedimentos_cat_nome'] = $rowProcedimento->proc_cat_nome;
//                $dadosConsultaProcInsert['id_proc_pacote'] = $id_proc_pacote;
//                $dadosConsultaProcInsert['id_proc_pacote_item'] = $id_proc_pacote_item;
//                $dadosConsultaProcInsert['pag_parcial'] = $id_proc_pacote_item;
                    $dadosConsultaProcInsert['retorno_proc'] = $rowProcedimento->retorno;
                    $dadosConsultaProcInsert['dicom'] = $rowProcedimento->utiliza_dicom;
                    $dadosConsultaProcInsert['dicom_code'] = $rowProcedimento->dicomCode;
                    $dadosConsultaProcInsert['dicom_modality_id'] = $rowProcedimento->dicom_modality_id;
//                $dadosConsultaProcInsert['carteira_virtual_id_lanc'] = $idCarteiraVirtual;
                    $dadosConsultaProcInsert['valor_liquido_proc'] = $baseCalcRepasse;
                    $dadosConsultaProcInsert['imposto_renda_convenio'] = $impostoRendaConvenio;
                    $dadosConsultaProcInsert['percentual_proc_repasse'] = $percentual_proc_repasse;
//                $dadosConsultaProcInsert['valor_taxa_tipo_pg'] = $valor_taxa_tipo_pg;
                    $dadosConsultaProcInsert['executante_doutores_id'] = $ExecutanteRepasseID;
                    $dadosConsultaProcInsert['executante_nome_cript'] = $ExecutanteRepasseNome;

//                $dadosConsultaProcInsert['proc_valor_bruto'] = $procValorBruto;
//                $dadosConsultaProcInsert['proc_tp_desconto'] = $procTipoDesconto;
//                $dadosConsultaProcInsert['proc_tp_desconto_valor'] = $procTipoDescontoValor;
//                $dadosConsultaProcInsert['proc_desconto'] = $procDesconto;
//                $dadosConsultaProcInsert['proc_tp_acrescimo'] = $procTipoAcrescimo;
//                $dadosConsultaProcInsert['proc_tp_acrescimo_valor'] = $procTipoAcrescimoValor;
//                $dadosConsultaProcInsert['proc_acrescimo'] = $procAcrescimo;


                    $tt = $ConsultaProcedimentoService->store($idDominio, $dadosConsultaProcInsert);

//                
//                if (!empty($idsRecebimentosProc)) {
//                    $ConsultasProcedimentos1->setFinanceiro_recebimentos_id($idsRecebimentosProc);
//                }
//                $idConsultaProcedimento = $ConsultasProcedimentos1->salvar();
//
//
////            $idConsultaProcedimento = $Consultas->salvarConsultaProcedimentos($identificador, $idConsulta, $procedimentos_convenio_id[$chave], $idProcedimento, $procedimentos_nome[$chave], $procedimentos_valor[$chave], $convenios_nome[$chave], $procedimentos_qnt[$chave], $id_consultas_procedimentos[$chave], $valorRepasse, $tipoRepasse, $origemRepasse, $ExecutanteRepasseID, $ExecutanteRepasseNome, $procedimentos_duracao[$chave], $rowProcedimento->proc_cat_nome, $rowProcedimento->procedimento_categoria_id, null, $id_proc_pacote, $id_proc_pacote_item, $proc_pag_parcial[$chave], $idsRecebimentosProc, $procedimentos_retorno[$chave], $rowProcedimento->utiliza_dicom, $rowProcedimento->dicomCode, $rowProcedimento->dicom_modality_id, $idCarteiraVirtual, $baseCalcRepasse, $impostoRendaConvenio, $percentual_proc_repasse, $valor_taxa_tipo_pg
////            );
//
//
//                if ($proc_pag_parcial[$chave] == 1 or $chk_consulta_paga == 1) {
//                    $idsProcParcial[] = $idConsultaProcedimento;
//                }
                }
            }





//  dd($idsConsultaProcSelecionado);
//   dd($qrProcConsulta);
//        if (auth('clinicas')->user()->id == 4055) {
            if (count($qrProcConsulta) > 0) {
                foreach ($qrProcConsulta as $rowProcConsulta) {
                    if (!in_array($rowProcConsulta->id, $idsConsultaProcSelecionado)) {
                        $ConsultaProcedimentoService->excluir($idDominio, $rowProcConsulta->id, $consultaId);
                    }
                }
            }
        }
//        }
        //Prontuario
        if (isset($dadosInput['textoProntuarioSimples']) and!empty($dadosInput['textoProntuarioSimples'])) {

            $ProntuarioService = new ProntuarioService;

            $dadosInsertPront['consultaId'] = $consultaId;
            $dadosInsertPront['textoProntuario'] = $dadosInput['textoProntuarioSimples'];
            $dadosInsertPront['atualizaProntuario'] = 1;

            $prontuario = $ProntuarioService->storeProntuarioSimplesAvulso($idDominio, $rowConsulta->pacientes_id, $dadosInsertPront);

            if (!$prontuario['success']) {
                return $this->returnError(null, $prontuario['message']);
            }
        }

        $AgendaFilaEsperaRepository = new AgendaFilaEsperaRepository;
        $AgendaFilaEsperaRepository->excluirPorConsultaId($idDominio, $consultaId);

        $dadosConsulta['liberado_fila_espera'] = 0;
        $ConsultaRepository->updateConsulta($idDominio, $consultaId, $dadosConsulta);

        $dadosStatus['consulta_id'] = $consultaId;
        $dadosStatus['identificador'] = $idDominio;
        $dadosStatus['status'] = 'jaFoiAtendido';
        $dadosStatus['hora'] = time();
        $dadosStatus['administrador_id'] = auth('clinicas')->user()->id;
        $dadosStatus['nome_administrador'] = auth('clinicas')->user()->nome;

        if ($dadosInput['finalizar']) {
            $ConsultaStatusRepository = new ConsultaStatusRepository;
            $ConsultaStatusRepository->alteraStatus($idDominio, $consultaId, $dadosStatus);

            if ($rowDominio->alteracao_docbizz == 1) {
                $ConsultaAtendAbertosRepository = new ConsultaAtendAbertosRepository();
                $ConsultaAtendAbertosRepository->deleteByConsultaId($idDominio, $consultaId);
            }
        } else {
            if ($rowDominio->alteracao_docbizz == 1) {
                $ConsultaAtendAbertosRepository = new ConsultaAtendAbertosRepository();

                $ConsultaAtendAbertosRepository->insertByConsultaId($idDominio, $consultaId);
            }
        }

        $StatusRefreshRepository->insertAgenda($idDominio, $rowConsulta->doutores_id);

        return $this->returnSuccess(null, "Salvo com successo");
    }

}
