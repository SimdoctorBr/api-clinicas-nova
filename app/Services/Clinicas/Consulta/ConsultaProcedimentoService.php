<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\Clinicas\Consulta;

use App\Services\BaseService;
use DateTime;
use App\Services\Clinicas\CalculosService;
use App\Repositories\Clinicas\Consulta\ConsultaProcedimentoRepository;
use App\Repositories\Clinicas\CarteiraVirtualRepository;
use App\Helpers\Functions;
use App\Repositories\Clinicas\ConvenioRepository;
use App\Repositories\Clinicas\ProcedimentosRepository;
use App\Repositories\Clinicas\EncaminhamentoRepository;
use App\Repositories\Clinicas\DoutorRepository;
use App\Repositories\Gerenciamento\DominioRepository;

/**
 * Description of Activities
 *
 * @author ander
 */
class ConsultaProcedimentoService extends BaseService {

    public function store($idDominio, $dadosInsert) {

        $campos['identificador'] = $idDominio;
        $campos['data_cad'] = date('Y-m-d H:i:s');
        $campos['valor_repasse'] = ( isset($dadosInsert['valor_repasse']) and !empty($dadosInsert['valor_repasse'])) ? $dadosInsert['valor_repasse'] : null;
        $campos['tipo_repasse'] = (isset($dadosInsert['tipo_repasse']) and !empty($dadosInsert['tipo_repasse'])) ? $dadosInsert['tipo_repasse'] : null;
        $campos['origem_repasse'] = (isset($dadosInsert['origem_repasse']) and !empty($dadosInsert['origem_repasse'])) ? $dadosInsert['origem_repasse'] : null;
        $campos['executante_doutores_id'] = (isset($dadosInsert['executante_doutores_id']) and !empty($dadosInsert['executante_doutores_id'])) ? $dadosInsert['executante_doutores_id'] : null;
        $campos['retorno_proc'] = (isset($dadosInsert['retorno_proc']) and !empty($dadosInsert['retorno_proc'])) ? $dadosInsert['retorno_proc'] : null;
        $campos['dicom'] = (!empty($dadosInsert['dicom'])) ? $dadosInsert['dicom'] : null;
        $campos['dicom_code'] = (!empty($dadosInsert['dicom_code'])) ? $dadosInsert['dicom_code'] : null;
        $campos['dicom_modality_id'] = (!empty($dadosInsert['dicom_modality_id'])) ? $dadosInsert['dicom_modality_id'] : null;
        $campos['carteira_virtual_id_lanc'] = (!empty($dadosInsert['carteira_virtual_id_lanc'])) ? $dadosInsert['carteira_virtual_id_lanc'] : null;
        $campos['lancado_doutor_id'] = (!empty($dadosInsert['lancado_doutor_id'])) ? $dadosInsert['lancado_doutor_id'] : null;

        if (isset($dadosInsert['tipo_repasse']) and $dadosInsert['tipo_repasse'] == 1) {
            $campos['percentual_proc_repasse'] = $dadosInsert['percentual_proc_repasse'];
        }


        if (isset($dadosInsert['pag_parcial']) and $dadosInsert['pag_parcial'] == 1) {
            $campos['pag_parcial'] = ($dadosInsert['pag_parcial'] == 1) ? $dadosInsert['pag_parcial'] : null;
            $campos['financeiro_recebimentos_id'] = (count($dadosInsert['financeiro_recebimentos_id']) == 0) ? null : implode(',', $dadosInsert['financeiro_recebimentos_id']);
        }

        $campos['id_proc_pacote'] = (!empty($dadosInsert['id_proc_pacote'])) ? $dadosInsert['id_proc_pacote'] : null;
        $campos['id_proc_pacote_item'] = (!empty($dadosInsert['id_proc_pacote_item'])) ? $dadosInsert['id_proc_pacote_item'] : null;

        if (isset($dadosInsert['id_proc_pacote_item']) and !empty($dadosInsert['id_proc_pacote_item'])) {
            $campos['base_calc_pacote_item'] = $dadosInsert['valor_proc'];
            $campos['valor_proc'] = null;
        }

        if (isset($dadosInsert['duracao']) and !empty($dadosInsert['duracao'])) {
            $campos['duracao'] = $dadosInsert['duracao'];
        } else {
            $campos['duracao'] = null;
        }

        $campos['executante_nome_cript'] = (!empty($dadosInsert['executante_nome_cript'])) ? $dadosInsert['executante_nome_cript'] : null;

        if (isset($dadosInsert['idCarteriaVirtualItem']) and !empty($dadosInsert['idCarteriaVirtualItem'])) {
            $CarteiraVirtualRepository = new CarteiraVirtualRepository();
            $CarteiraVirtualRepository->vinculaCarteiraItemConsulta($idDominio, $dadosInsert['idCarteriaVirtualItem'], $dadosInsert['consultas_id']);
        }

        $ConsultaProcedimentoRepository = new ConsultaProcedimentoRepository;
        if (isset($dadosInsert['id']) and !empty($dadosInsert['id'])) {
            $ConsultaProcedimentoRepository->update($idDominio, $dadosInsert['id'], $dadosInsert);
            return $dadosInsert['id'];
        } else {

            return $ConsultaProcedimentoRepository->store($idDominio, $dadosInsert);
        }
    }

    public function getByConsultaId($idDominio, $consultaId, Array $idsConsultaProcedimentos = null) {
        $ConsultaProcedimentoRepository = new ConsultaProcedimentoRepository;

        $qrProcedimentos = $ConsultaProcedimentoRepository->getByConsultaId($idDominio, $consultaId, $idsConsultaProcedimentos);
        $retorno = null;

        iF (count($qrProcedimentos) > 0) {
            foreach ($qrProcedimentos as $rowProc) {
                $retorno[] = [
                    'id' => $rowProc->id,
                    'procedimentoId' => $rowProc->procedimentos_id,
                    'procConvId' => $rowProc->procedimentos_id . '_' . $rowProc->convenios_id,
                    'nome' => Functions::utf8Fix($rowProc->nome_proc),
                    'duracao' => $rowProc->duracao,
                    'convenioId' => $rowProc->convenios_id,
                    'convenioNome' => Functions::correcaoUTF8Decode($rowProc->nome_convenio),
                    'valor' => $rowProc->valor_proc,
                    'qnt' => $rowProc->qnt,
                    'nomeCategoria' => $rowProc->procedimentos_cat_nome,
                    'pagParcial' => $rowProc->pag_parcial,
                    'idCarteiraItem' => $rowProc->id_carteira_item,
                    'financeiroRecebimentosId' => $rowProc->financeiro_recebimentos_id,
                    'codCarteiraVirtual' => $rowProc->codCarteiraVirtual,
                    'impostoRendaConvenio' => $rowProc->imposto_renda_convenio,
                    'baseCalcPacoteItem' => $rowProc->base_calc_pacote_item,
                    'nomePacote' => $rowProc->nome_pacote,
                    'exibeAppNeofor' => ($rowProc->exibir_app_docbizz == 1) ? true : false
                ];
            }
        }
        return $retorno;
    }

    public function excluir($idDominio, $consultaProcedimentoId, $consultaId = null) {
        $ConsultaProcedimentoRepository = new ConsultaProcedimentoRepository;
        $qrProcedimentos = $ConsultaProcedimentoRepository->excluir($idDominio, $consultaProcedimentoId, $consultaId);

        if ($qrProcedimentos) {
            return $this->returnSuccess('', 'Excluído com sucesso.');
        } else {
            return $this->returnError(null, 'Ocorreu um erro ao excluir o procedimento');
        }
    }

    /**
     * 
     * @param type $idDominio
     * @param type $consultaId
     * @param type $idDoutor
     * @param type $dadosProcedimentos   array('idConsultaProcedimento', 'convenioId','duracao','qnt','valor')
     * @param type $rowDominio
     * @param type $rowDoutor
     */
    public function calculoProcedimentosConsultas($idDominio, $consultaId, $idDoutor, $dadosProcedimentos, $rowDominio = null, $rowDoutor = null) {

        $ProcedimentosRepository = new ProcedimentosRepository();
        $EncaminhamentoRepository = new EncaminhamentoRepository();
        $DoutorRepository = new DoutorRepository();
        $ConvenioRepository = new ConvenioRepository;

        if (empty($rowDominio)) {
            $DominioRepository = new DominioRepository;
            $rowDominio = $DominioRepository->getById($idDominio);
        }
        if (empty($rowDoutor)) {
            $DoutorRepository = new DoutorRepository;
            $rowDoutor = $DoutorRepository->getById($idDominio, $idDoutor);
        }

        $DADOS_RETORNO = [
            'idsProcConsultas' => null,
            'valorTotalProc' => 0,
            'dadosSplit' => null
        ];

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
                }, $dadosProcedimentos));

        foreach ($dadosProcedimentos as $rowInputProc) {


            if (isset($rowInputProc['idConsultaProcedimento']) and !empty($rowInputProc['idConsultaProcedimento'])) {
                $idsConsultaProcSelecionado[] = $rowInputProc['idConsultaProcedimento'];
            }

            $rowProcedimento = $ProcedimentosRepository->getById($idDominio, $rowInputProc['id']);

            $duracao = (isset($rowInputProc['duracao'])) ? $rowInputProc['duracao'] : $rowProcedimento->duracao;
//
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
//
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


                            if ($rowRepasse->split_assas == 1) {

                                if (!isset($DADOS_RETORNO['dadosSplit'][$idDoutorRepasse])) {
                                    $DADOS_RETORNO['dadosSplit'] = [$idDoutorRepasse => [
                                            'valorRepasse' => $valorRepasse
                                    ]];
                                }else{
                                    $DADOS_RETORNO['dadosSplit'][$idDoutorRepasse] ['valorRepasse'] += $valorRepasse;
                                }


                              
                            }
                        }
                    }
                }
            }


//  dd($valorRepasse);
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





            $dadosConsultaProcInsert['id'] = (isset($rowInputProc['idConsultaProcedimento']) and !empty($rowInputProc['idConsultaProcedimento'])) ? $rowInputProc['idConsultaProcedimento'] : '';
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


            $DADOS_RETORNO['idsProcConsultas'][] = $this->store($idDominio, $dadosConsultaProcInsert);

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
            $DADOS_RETORNO['valorTotalProc'] += $totalProc;
        }

        return $DADOS_RETORNO;
    }
}
