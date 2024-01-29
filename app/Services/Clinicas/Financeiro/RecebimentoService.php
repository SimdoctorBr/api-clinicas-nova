<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\Clinicas\Financeiro;

use App\Services\BaseService;
use DateTime;
use App\Helpers\Functions;
use App\Repositories\Clinicas\Financeiro\RecebimentoRepository;
use App\Repositories\Clinicas\DefinicaoMarcacaoGlobalRepository;
use App\Repositories\Clinicas\Consulta\ConsultaProcedimentoRepository;
use App\Repositories\Clinicas\Financeiro\FormaPagamentoRepository;
use App\Repositories\Clinicas\ConsultaRepository;
use App\Repositories\Clinicas\Financeiro\Fornecedores\FornecedorRepository;
use App\Repositories\Clinicas\Financeiro\PeriodoRepeticaoRepository;
use App\Repositories\Clinicas\StatusRefreshRepository;
use App\Services\Clinicas\LogAtividadesService;
use Illuminate\Support\Facades\Crypt;

/**
 * Description of Activities
 *
 * @author ander
 */
class RecebimentoService extends BaseService {

    private $recebimentoRepository;

    public function __construct() {
        $this->recebimentoRepository = new RecebimentoRepository;
    }

    /**
     * Verifica o valor recebdio no pagamento
     * @param type $totalvalorRecebido
     * @param type $valorLiquido
     * @param type $arrayTiposPagamento
     * @return type
     */
    private function verificaValorRecebido($totalvalorRecebido, $valorLiquido, $arrayTiposPagamento) {
        $retorno = ['success' => true,
            'valorTroco' => 0,
            'error' => ''
        ];
        if ($totalvalorRecebido < $valorLiquido) {
            $retorno['success'] = false;
            $retorno['error'] = 'Faltam R$ ' . ($valorLiquido - $totalvalorRecebido) . ' a ser pago. Valor total: R$ ' . $valorLiquido;
        } else if ($totalvalorRecebido > $valorLiquido) {


            foreach ($arrayTiposPagamento as $idTpPag) {
                if ($idTpPag == 1) {
                    $retorno['valorTroco'] = $totalvalorRecebido - $valorLiquido;
                } else {
                    $retorno['success'] = false;
                    $retorno['error'] = 'O valor recebido é maior que o valor a ser pago. Valor a ser pago: R$ ' . ($valorLiquido) . '. Valor adicional: R$ ' . ($totalvalorRecebido - $valorLiquido);
                }
            }
        }
        return $retorno;
    }

    /**
     * 
     * @param type $idDominio
     * @param type $tipo   Pode ser consulta, orcamento, cateira_virtual
     * @param type $idTipo
     */
    public function getDadosPagamentosEfetuados($idDominio, $tipo, $idTipo, $exibeProcedimentos = true) {

        $ConsultaProcedimentoRepository = new ConsultaProcedimentoRepository;

        $DefinicaoMarcacaoGlobalRepository = new DefinicaoMarcacaoGlobalRepository;
        $rowDef = $DefinicaoMarcacaoGlobalRepository->getDadosDefinicao($idDominio);

        $qrRecebimentos = $this->recebimentoRepository->getAllEfetuados($idDominio, $tipo, $idTipo);

        $arrayRecibo = null;

        if (count($qrRecebimentos) > 0) {
            $countRecibo = -1;
            $countDadosReceb = 0;
            $codTipoAnt = '';

//            $OrcamentoItens = new OrcamentosItens();
//            $Consulta = new Consultas();

            foreach ($qrRecebimentos as $rowReceb) {

                if ($tipo == 'orcamento') {
//                    $codTipo = 'orc_' . $rowReceb->orcamentos_id . '_' . $rowReceb->idsProcParcial;
//                    if (!empty($rowReceb->idsProcParcial)) {
//                        $procedimentos = $OrcamentoItens->getByIdsItens($identificador, $rowReceb->orcamentos_id, $rowReceb->idsProcParcial);
//                    } else {
//                        $procedimentos = $OrcamentoItens->getAll($identificador, $rowReceb->orcamentos_id);
//                    }
                } else if ($tipo == 'consulta') {
                    $codTipo = 'consul_' . $rowReceb->consulta_id . '_' . $rowReceb->idsProcParcial;
                    if ($exibeProcedimentos) {
                        if (!empty($rowReceb->idsProcParcial)) {

                            $procedimentos = $ConsultaProcedimentoRepository->getByConsultaId($idDominio, $rowReceb->consulta_id, explode(',', $rowReceb->idsProcParcial));
                        } else {
                            $procedimentos = $ConsultaProcedimentoRepository->getByConsultaId($idDominio, $rowReceb->consulta_id);
                        }
                    }
                } else if ($tipo == 'carteira_virtual') {
//                    $CarteiraVirtual = new CarteiraVirtual();
//                    $codTipo = 'cart_virt_' . $rowReceb->carteira_virtual_id . '_' . $rowReceb->idsProcParcial;
//                    $rowCarteira = $CarteiraVirtual->getById($identificador, $rowReceb->carteira_virtual_id);
//                    $procClass = new stdClass();
//                    $procClass->nome_proc = $rowCarteira->nome_pacote;
//                    $procClass->qnt = 1;
//                    $procClass->valorProc = $rowCarteira->valor_proc;
//                    $procedimentos[0] = $procClass;
//                if (!empty($rowReceb->idsProcParcial)) {
//                    $procedimentos = $OrcamentoItens->getByIdsItens($identificador, $rowReceb->orcamentos_id, $rowReceb->idsProcParcial);
//                } else {
//                    $procedimentos = $OrcamentoItens->getAll($identificador, $rowReceb->orcamentos_id);
//                }
                }
//
//


                if (isset($arrayIndice[$codTipo])) {
                    $indice = $arrayIndice[$codTipo];
                } else {
                    $countRecibo++;
                    $countDadosReceb = 0;
                    $indice = $countRecibo;
                    $arrayIndice[$codTipo] = $countRecibo;
                }
                if ($codTipo != $codTipoAnt) {
                    if ($exibeProcedimentos) {
                        $procI = 0;

                        foreach ($procedimentos as $rowProc) {
                            $ProcedimentosA[$procI]['idProcItem'] = $rowProc->id;   //chave primaria das tabelas consulta_procedimentos ou orcamentos_proc_itens
                            $ProcedimentosA[$procI]['nome'] = $rowProc->nome_proc;
                            $ProcedimentosA[$procI]['procedimentoId'] = $rowProc->procedimentos_id;
                            $ProcedimentosA[$procI]['qnt'] = $rowProc->qnt;
                            $ProcedimentosA[$procI]['valorProc'] = $rowProc->valor_proc;
                            $ProcedimentosA[$procI]['nomeConvenio'] = $rowProc->nome_convenio;
                            $ProcedimentosA[$procI]['idConvenio'] = $rowProc->convenios_id;
                            $ProcedimentosA[$procI]['nomeCategoria'] = $rowProc->procedimentos_cat_nome;
                            $ProcedimentosA[$procI]['pagParcial'] = $rowProc->pag_parcial;
                            $ProcedimentosA[$procI]['idCarteiraItem'] = $rowProc->id_carteira_item;
                            $ProcedimentosA[$procI]['financeiroRecebimentosId'] = $rowProc->financeiro_recebimentos_id;
                            $ProcedimentosA[$procI]['codCarteiraVirtual'] = $rowProc->codCarteiraVirtual;
                            $ProcedimentosA[$procI]['nomePacote'] = $rowProc->nome_pacote;
                            $ProcedimentosA[$procI]['tipoRepasse'] = $rowProc->tipo_repasse;
                            $ProcedimentosA[$procI]['percentualRepasse'] = $rowProc->percentual_proc_repasse;
                            $ProcedimentosA[$procI]['valorRepasse'] = $rowProc->valor_repasse;
                            $ProcedimentosA[$procI]['percentImpostoRendaConvenio'] = $rowProc->imposto_renda_convenio;
                            $ProcedimentosA[$procI]['valorLiquidoProc'] = (empty($rowProc->nome_pacote)) ? $rowProc->valor_liquido_proc : 0;
                            $ProcedimentosA[$procI]['aliquotaIss'] = $rowProc->aliquota_iss;
                            $ProcedimentosA[$procI]['aliquotaIssPercentual'] = $rowProc->aliquota_iss_percentual;
                            $ProcedimentosA[$procI]['baseCalcPacoteItem'] = $rowProc->base_calc_pacote_item;
                            $ProcedimentosA[$procI]['exibirAppNeofor'] = $rowProc->exibir_app_docbizz;
                            $procI++;
                        }
                        $arrayRecibo[$indice]['procedimentos'] = $ProcedimentosA;
                    }
                }



                $taxa = null;
                if (!empty($rowReceb->possui_tp_pag_taxa)) {
                    $taxa['removida'] = ($rowReceb->possui_tp_pag_taxa == 2) ? true : false;
                    $taxa['percentual'] = $rowReceb->percentual_pag_taxa;
                    $taxa['valor'] = $rowReceb->valor_tp_pag_taxa;
                }
//
                $dadosReceb['codigoRecebimento'] = Functions::codFinanceiroRecDesp($rowDef->tipo_cod_fin, $rowReceb->ano_codigo, $rowReceb->rec_codigo);
                $dadosReceb['tipo_pag_nome'] = $rowReceb->tipo_pag_nome;
                $dadosReceb['pago'] = ($rowReceb->pago) ? 'Sim' : 'Não';
                $dadosReceb['periodoNome'] = $rowReceb->periodoNome;
                $dadosReceb['parcelas'] = ($rowReceb->periodo_repeticao_id != 1) ? $rowReceb->quantidade_parcelas : '';
                $dadosReceb['id_recebimento'] = $rowReceb->idRecebimento;
                $dadosReceb['taxa'] = $taxa;
                $dadosReceb['valor_total'] = $rowReceb->totalParcelado;

//
                $arrayRecibo[$indice]['dadosRecebimento'][$countDadosReceb] = $dadosReceb;
                $arrayRecibo[$indice]['idsRecebimento'][] = $rowReceb->idRecebimento;
                $arrayRecibo[$indice]['fornecedor_nome'] = $rowReceb->fornecedor_nome;
                $arrayRecibo[$indice]['categoria_nome'] = $rowReceb->categoria_nome;
                $arrayRecibo[$indice]['recebimento_data_vencimento'] = $rowReceb->recebimento_data_vencimento;
                $arrayRecibo[$indice]['recebimento_data'] = $rowReceb->recebimento_data;
                $arrayRecibo[$indice]['dt_pagamento'] = $rowReceb->recebimento_data;
                $arrayRecibo[$indice]['valor_bruto'] = $rowReceb->valor_total_procedimento;
                $arrayRecibo[$indice]['valor_desconto'] = $rowReceb->valor_desconto;
                $arrayRecibo[$indice]['tipo_acrescimo'] = $rowReceb->acrescimo_tipo;
                $arrayRecibo[$indice]['percentual_acrescimo'] = $rowReceb->acrescimo_percentual;
                $arrayRecibo[$indice]['valor_acrescimo'] = $rowReceb->acrescimo_valor;
                $arrayRecibo[$indice]['valor_liquido'] = $rowReceb->total_recebimento;
                $arrayRecibo[$indice]['descricao'] = $rowReceb->recebimento_descricao;
                $arrayRecibo[$indice]['codigoRecebimento'] = Functions::codFinanceiroRecDesp($rowDef->tipo_cod_fin, $rowReceb->ano_codigo, $rowReceb->rec_codigo);
                $arrayRecibo[$indice]['dt_vencimento'] = $rowReceb->recebimento_data_vencimento;

                $countDadosReceb++;
                unset($dadosReceb);

                $codTipoAnt = $rowReceb->orcamentos_id . '_' . $rowReceb->idsProcParcial;
                unset($ProcedimentosA);
            }
        }
        return $arrayRecibo;
    }

    public function validateStoreDados($dadosInput) {


        if ((!isset($dadosInput['consultasId'])
                and
                !isset($dadosInput['pacientesId']))
                or
                (empty($dadosInput['consultasId']) and empty($dadosInput['pacientesId']))
        ) {
            return 'Informe um dos campos: consultasId ou pacientesId';
        }
        if ((isset($dadosInput['consultasId']) and !empty($dadosInput['consultasId'])) and
                isset($dadosInput['pacientesId']) and !empty($dadosInput['pacientesId'])
        ) {
            return 'Somente um dos campos podem ser usados: consultasId ou pacientesId';
        }
        //Desconto
        if (isset($dadosInput['tipoDesconto']) and !empty($dadosInput['tipoDesconto'])) {

            if ($dadosInput['tipoDesconto'] != 1 and $dadosInput['tipoDesconto'] != 2) {
                return 'Tipo de desconto inválido';
            }
        }
        //Acrescimo
        if (isset($dadosInput['tipoAcrescimo']) and !empty($dadosInput['tipoAcrescimo'])) {

            if ($dadosInput['tipoAcrescimo'] != 1 and $dadosInput['tipoAcrescimo'] != 2) {
                return 'Tipo de acréscimo inválido';
            }
        }



        if (!isset($dadosInput['pagFormaPagamentoId'])) {
            return 'Informe a forma de pagamento.';
        }

        foreach ($dadosInput['pagFormaPagamentoId'] as $chave => $idFormapag) {
            if (isset($dadosInput['pagFormaPagamentoId'][$chave]) and empty($dadosInput['pagFormaPagamentoId'][$chave])) {
                return 'Informe a forma de pagamento. pagFormaPagamentoId[' . $chave . '].';
            }

            if (!isset($dadosInput['pagDataVencimento'][$chave]) or
                    (isset($dadosInput['pagDataVencimento'][$chave]) and empty($dadosInput['pagDataVencimento'][$chave]))) {
                return 'Informe a data de vencimento. pagDataVencimento[' . $chave . ']';
            }
            if (!isset($dadosInput['pagDataPagamento'][$chave]) or
                    (isset($dadosInput['pagDataPagamento'][$chave]) and empty($dadosInput['pagDataPagamento'][$chave]))) {
                return 'Informe a data de pagamento. pagDataPagamento[' . $chave . ']';
            }




//            if (!isset($dadosInput['pagContaRecebimentoId'][$chave]) or
//                    (isset($dadosInput['pagContaRecebimentoId'][$chave]) and empty($dadosInput['pagContaRecebimentoId'][$chave]))) {
//                return 'Informe a conta de recebimento pagContaRecebimentoId[' . $chave . '].';
//            }

            if (!isset($dadosInput['pagValor'][$chave]) or
                    (isset($dadosInput['pagValor'][$chave]) and empty($dadosInput['pagValor'][$chave]))) {
                return 'Informe o valor do pagamento pagValor[' . $chave . '].';
            }
            if (!isset($dadosInput['pagTipoPeriodoRepeticao'][$chave]) or
                    (isset($dadosInput['pagTipoPeriodoRepeticao'][$chave]) and empty($dadosInput['pagTipoPeriodoRepeticao'][$chave]))) {
                return 'Informe o período de repetição pagTipoPeriodoRepeticao[' . $chave . '].';
            }

            if ($dadosInput['pagTipoPeriodoRepeticao'][$chave] != 1) {

                if (!isset($dadosInput['pagQntParcela'][$chave]) or
                        (isset($dadosInput['pagQntParcela'][$chave]) and empty($dadosInput['pagQntParcela'][$chave]))) {
                    return 'Informe a quantidade de parcelas pagQntParcela[' . $chave . '].';
                }
//                if (!isset($dadosInput['pagValorParcela'][$chave]) or
//                        (isset($dadosInput['pagValorParcela'][$chave]) and empty($dadosInput['pagValorParcela'][$chave]))) {
//                    return 'Informe a valor da parcela pagQntParcela[' . $chave . '].';
//                }
            }
        }
    }

    public function store($idDominio, $dadosInput) {

        $FornecedorRepository = new FornecedorRepository;

        $validate = validator($dadosInput,
                [
                    'consultasId' => 'numeric',
                    'pacientesId' => 'numeric',
                    'categoriaId' => 'numeric',
                    'tipoDesconto' => 'numeric',
                    'valorDesconto' => 'required_if:tipoDesconto,1|required_if:tipoDesconto,2|numeric',
                    'tipoAcrescimo' => 'numeric',
                    'valorAcrescimo' => 'required_if:tipoAcrescimo,1|required_if:tipoAcrescimo,2|numeric',
                    'valorBruto' => 'required|numeric',
//                    'orcamentosId' => 'numeric',
                ], [
            'consultasId.numeric' => 'O campo \'consultasId\' deve ser numérico',
            'pacientesId.numeric' => 'O campo \'pacientesId\' deve ser numérico',
            'categoriaId.numeric' => 'O campo \'categoriaId\' deve ser numérico',
            'tipoDesconto.numeric' => 'O campo \'tipoDesconto\' deve ser numérico',
            'valorDesconto.required_if' => 'Informe o valor do desconto',
            'valorDesconto.numeric' => 'O campo \'valorDesconto\' deve ser numérico',
            'tipoAcrescimo.numeric' => 'O campo \'tipoDesconto\' deve ser numérico',
            'valorAcrescimo.required_if' => 'Informe o valor do acréscimo',
            'valorAcrescimo.numeric' => 'O campo \'valorAcrescimo\' deve ser numérico',
            'valorBruto.required' => 'Informe o valor bruto',
            'valorBruto.numeric' => 'O campo \'valorBruto\' deve ser numérico',
        ]);

        if ($validate->fails()) {
            return $this->returnError(null, $validate->errors()->all()[0]);
        }

        $verificaCampos = $this->validateStoreDados($dadosInput);
        if (!empty($verificaCampos)) {
            return $this->returnError($verificaCampos);
        }


        $valorBruto = $dadosInput['valorBruto'];
        $valorLiquido = $valorBruto;
        $consultasId = (isset($dadosInput['consultasId']) and !empty($dadosInput['consultasId'])) ? $dadosInput['consultasId'] : null;
        $pacientesId = (isset($dadosInput['pacientesId']) and !empty($dadosInput['pacientesId'])) ? $dadosInput['pacientesId'] : null;
        $categoriaId = (isset($dadosInput['categoriaId']) and !empty($dadosInput['categoriaId'])) ? $dadosInput['categoriaId'] : null;
        $descricao = (isset($dadosInput['pagDescricao']) and !empty($dadosInput['pagDescricao'])) ? $dadosInput['pagDescricao'] : null;
        $tipoDesconto = (isset($dadosInput['tipoDesconto']) and !empty($dadosInput['tipoDesconto'])) ? $dadosInput['tipoDesconto'] : null;
        $valorDesconto = (isset($dadosInput['valorDesconto']) and !empty($dadosInput['valorDesconto'])) ? $dadosInput['valorDesconto'] : null;
        $motivoDesconto = (isset($dadosInput['motivoDesconto']) and !empty($dadosInput['motivoDesconto'])) ? $dadosInput['motivoDesconto'] : null;

        $tipoAcrescimo = (isset($dadosInput['tipoAcrescimo']) and !empty($dadosInput['tipoAcrescimo'])) ? $dadosInput['tipoAcrescimo'] : null;
        $valorAcrescimo = (isset($dadosInput['valorAcrescimo']) and !empty($dadosInput['valorAcrescimo'])) ? $dadosInput['valorAcrescimo'] : null;
        $motivoAcrescimo = (isset($dadosInput['motivoAcrescimo']) and !empty($dadosInput['motivoAcrescimo'])) ? $dadosInput['motivoAcrescimo'] : null;

        $pagDataPagamento = $dadosInput['pagDataPagamento'];
        $pagDataVencimento = $dadosInput['pagDataVencimento'];
        $arrayTipoPagamento = $dadosInput['pagFormaPagamentoId'];
        $arrayProcPagReceberEm = (!empty($dadosInput['pagContaRecebimentoId'])) ? $dadosInput['pagContaRecebimentoId'] : null;
        $arrayProcValor = $dadosInput['pagValor'];
        $arrayProcPeriodoRepeticao = $dadosInput['pagTipoPeriodoRepeticao'];
        $arrayProcQntParcelaTotal = (isset($dadosInput['pagQntParcela']) and !empty($dadosInput['pagQntParcela'])) ? $dadosInput['pagQntParcela'] : null;
//        $arrayProcValorParcela = $dadosInput['pagValorParcela'];



        $dadosInsert = null;
        if (!empty($categoriaId)) {
            $dadosInsert['categoria_id'] = $categoriaId;
        }

        $dadosInsert['identificador'] = $idDominio;
        $dadosInsert['recebimento_descricao'] = $descricao;
        $dadosInsert['valor_total_procedimento'] = $valorBruto;

        $origemPagamento = '';

        //Consultas
        if (!empty($consultasId)) {


            $origemPagamento = 'consulta';
            $dadosInsert['consulta_id'] = $consultasId;
            $dadosInsert['recebido_de'] = 1;
            $ConsultaRepository = new ConsultaRepository;
            $rowConsulta = $ConsultaRepository->getById($idDominio, $consultasId);
            if (!$rowConsulta) {
                return $this->returnError(null, 'Consultas não encontrada');
            }
            if (!empty($rowConsulta->idRecebimento)) {
                return $this->returnError(null, 'Esta consulta já está paga');
            }


            $ConsultaProcedimentoService = new ConsultaProcedimentoService;

            $ConsultaProcedimentoRepository = new ConsultaProcedimentoRepository;
            $qrProcedimentos = $ConsultaProcedimentoRepository->getByConsultaId($idDominio, $consultasId);
            if ($qrProcedimentos) {
                $idsProcLancadosAnterior = array_map(function ($item) {
                    return $item->id;
                }, $qrProcedimentos);

//                $totalValorPRoc = array_sum(array_map(function ($item) {
//                            return $item->valor_proc;
//                        }, $qrProcedimentos));
            }

            if (isset($dadosInput['procedimentos']) and count($dadosInput['procedimentos']) > 0) {

                //verifica procedimetnos já lançados
                $idsProcRequest = array_filter(array_map(function ($item) {
                            if (isset($item['idConsultaProcedimento'])) {
                                return $item['idConsultaProcedimento'];
                            } else {
                                return null;
                            }
                        }, $dadosInput['procedimentos']));

                if (isset($dadosInput['idConsultaProcedimentoExcluido'])) {
                    $idsProcRequest = array_merge($idsProcRequest, $dadosInput['idConsultaProcedimentoExcluido']);
                }


                if (count($idsProcLancadosAnterior) > 0) {

//                    var_dump($idsProcLancadosAnterior);
//                    var_dump($idsProcRequest);
//                    var_dump(array_intersect($idsProcRequest, $idsProcLancadosAnterior));
//                   

                    if (count(array_intersect($idsProcRequest, $idsProcLancadosAnterior)) < count($idsProcLancadosAnterior)) {
                        return $this->returnError(null, 'Existem procedimentos já lançados que não foram enviados. ');
                    }
                }
// dd($idsProcLancadosAnterior);
                //Exclusao de procedimentos
                if (isset($dadosInput['idConsultaProcedimentoExcluido']) and count(array_filter($dadosInput['idConsultaProcedimentoExcluido'])) > 0) {
                    foreach ($dadosInput['idConsultaProcedimentoExcluido'] as $idProcDelete) {
                        $ConsultaProcedimentoService->excluir($idDominio, $idProcDelete, $consultasId);
                    }
                }


//                   var_dump(array_intersect($idsProcLancadosAnterior,$idsProcRequest));
//                   var_dump(array_intersect($idsProcLancadosAnterior,$dadosInput['idConsultaProcedimentoExcluido']));
//                dd(array_intersect($idsProcLancadosAnterior, $idsProcRequest));
//                if ($totalValorPRoc > 0) {
//                    return $this->returnError(null, 'Existem procedimentos já lançados que não foram enviados. ');
//                }
                $totalValorPRoc = array_sum(array_map(function ($item) {
                            return $item['valor'] * $item['qnt'];
                        }, $dadosInput['procedimentos']));

                if ($totalValorPRoc != $valorBruto) {
                    return $this->returnError(null, 'O valor bruto informado é diferente do total de procedimentos da consulta');
                }
            } else {
                $idsConsultaProcedimentos = (array_map(function ($item) {
                            return $item->id;
                        }, $qrProcedimentos));

                if ($totalValorPRoc != $valorBruto) {
                    return $this->returnError(null, 'O valor bruto informado é diferente do total de procedimentos da consulta');
                }
            }
        }



        //Conta
        $rowFornecedorConta = $FornecedorRepository->getFornecedorPadrao($idDominio);
        if (!$rowFornecedorConta) {
            return $this->returnError(null, 'Sem conta padrão para o recebimento. Selecione uma conta padrão no sistema Simdoctor.');
        }


        $FormaPagamentoRepository = new FormaPagamentoRepository;
        $qrFormaPag = $FormaPagamentoRepository->getById($idDominio, $arrayTipoPagamento);
        $formaPagArray = null;
        foreach ($qrFormaPag as $rowPag) {
            $formaPagArray[$rowPag->idTipo_pagamento] = $rowPag;
        }

        $ChavesPagDinheiro = [];
        $valorTaxaTotal = 0;

        //DEsconto
        if ($tipoDesconto == 1) {
            $dadosInsert['tipo_desconto'] = $tipoDesconto;
            $dadosInsert['percentual_desconto'] = $valorDesconto;
            $dadosInsert['motivo_desconto'] = $motivoDesconto;
            $valorDescontoCalc = Functions::calcularDescontoPercentual($valorLiquido, 1, $valorDesconto);
            $dadosInsert['valor_desconto'] = $valorDescontoCalc;
            $valorLiquido = $valorLiquido - $valorDescontoCalc;
        } else if ($tipoDesconto == 2) {
            $dadosInsert['tipo_desconto'] = $tipoDesconto;
            $dadosInsert['desconto_reais'] = $valorDesconto;
            $dadosInsert['motivo_desconto'] = $motivoDesconto;
            $dadosInsert['valor_desconto'] = $valorDesconto;
            $valorLiquido = $valorLiquido - $valorDesconto;
        }



        //Acrescimo
        if ($tipoAcrescimo == 1) {
            $dadosInsert['acrescimo_tipo'] = $tipoAcrescimo;
            $dadosInsert['acrescimo_percentual'] = $valorAcrescimo;
            $dadosInsert['acrescimo_motivo'] = $motivoAcrescimo;
            $dadosInsert['acrescimo_valor'] = Functions::calcularDescontoPercentual($valorLiquido, 1, $valorAcrescimo);
            $valorLiquido = $valorLiquido + $dadosInsert['acrescimo_valor'];
        } else if ($tipoAcrescimo == 2) {
            $dadosInsert['acrescimo_tipo'] = $tipoAcrescimo;
            $dadosInsert['acrescimo_valor'] = $valorAcrescimo;
            $dadosInsert['acrescimo_motivo'] = $motivoAcrescimo;
            $valorLiquido = $valorLiquido + $valorAcrescimo;
        }


        $dadosInsert['total_recebimento'] = $valorLiquido;

        $totalvalorRecebido = array_sum($arrayProcValor);
        $valorTroco = 0;
        $verificaPagRecebido = $this->verificaValorRecebido($totalvalorRecebido, $valorLiquido, $arrayTipoPagamento);
        if (!$verificaPagRecebido['success']) {
            return $this->returnError(null, $verificaPagRecebido['error']);
        } else {
            $valorTroco = $verificaPagRecebido['valorTroco'];
        }

        if (isset($dadosInput['procedimentos']) and count($dadosInput['procedimentos']) > 0 and (!empty($consultasId) )) {
            $dadosProcInseridos = $ConsultaProcedimentoService->calculoProcedimentosConsultas($idDominio, $consultasId, $rowConsulta->doutores_id, $dadosInput['procedimentos']);
            $idsConsultaProcedimentos = $dadosProcInseridos['idsProcConsultas'];
        }



//        dd($totalvalorRecebido);
//        CAIXA
//        if ($permissaoOperadorCaixa == 1 and $rowCaixa) {
//            $Recebimento->setCaixa_id($rowCaixa->id);
//        }

        $RECEBIMENTOS_DINHEIRO = $dadosInsert;

        $idsRecebimentosProc = null;
        foreach ($arrayTipoPagamento as $chave => $TipoPag) {

            if ($TipoPag == 1) {
                $ChavesPagDinheiro[] = $chave;
                continue;
            }


            $RECEBIMENTOS_OUTROS = $dadosInsert;
            $valorPag = $arrayProcValor[$chave];

            $RECEBIMENTOS_OUTROS['recebimento_valor'] = $valorPag;

            $RECEBIMENTOS_OUTROS['idsProcParcial'] = implode(',', $idsConsultaProcedimentos);
            $RECEBIMENTOS_OUTROS['tipo_pag_id'] = $TipoPag;
            $RECEBIMENTOS_OUTROS['recebimento_data_vencimento'] = $pagDataVencimento[$chave];
            $RECEBIMENTOS_OUTROS['recebimento_competencia'] = $pagDataVencimento[$chave];
            $RECEBIMENTOS_OUTROS['recebimento_data'] = $pagDataPagamento[$chave];
            $RECEBIMENTOS_OUTROS['pago'] = 1;
            $RECEBIMENTOS_OUTROS['periodo_repeticao_id'] = $arrayProcPeriodoRepeticao[$chave];
            $RECEBIMENTOS_OUTROS['quantidade_parcelas'] = (isset($arrayProcQntParcelaTotal[$chave])) ? $arrayProcQntParcelaTotal[$chave] : null;

            $RECEBIMENTOS_OUTROS['pagar_com_adm_banco'] = $rowFornecedorConta->idFornecedor; //pacientes
            //
            //taxas
            if (!empty($formaPagArray[$TipoPag]->possui_taxa == 1)) {
                $valorDescontoTaxa = Functions::calcularDescontoPercentual($valorPag, 1, $formaPagArray[$TipoPag]->percentual_taxa);
                $valorDescontoTaxa = number_format($valorDescontoTaxa, 2, '.', '');
                $valorTaxaTotal += $valorDescontoTaxa;
                $RECEBIMENTOS_OUTROS['possui_tp_pag_taxa'] = 1;
                $RECEBIMENTOS_OUTROS['percentual_pag_taxa'] = $formaPagArray[$TipoPag]->percentual_taxa;
                $RECEBIMENTOS_OUTROS['valor_tp_pag_taxa'] = $valorDescontoTaxa;
            }


//            dd($RECEBIMENTOS_OUTROS);

            if ($arrayProcPeriodoRepeticao[$chave] == 1) {
                $idRecebimento = $this->recebimentoRepository->store($idDominio, $RECEBIMENTOS_OUTROS);

                $idsRecebimentosProc[] = $idRecebimento;
            } else {

                //lançar parcelados
                //
                $PeriodoRepeticaoRepository = new PeriodoRepeticaoRepository;
                $rowRepeticao = $PeriodoRepeticaoRepository->getById($arrayProcPeriodoRepeticao[$chave]);

                $valorTotalCalc = $arrayProcValor[$chave];
                $CalculosParcelas = Functions::parcelaMonetaria($valorTotalCalc, $arrayProcQntParcelaTotal[$chave]);

                $idRecebimentoPrincipal = null;

                foreach ($CalculosParcelas as $chave2 => $valorParcela) {

                    $parcela = $chave2 + 1;
                    $RECEBIMENTOS_OUTROS['recebimento_valor'] = $valorParcela;
                    $RECEBIMENTOS_OUTROS['numero_da_parcela'] = $parcela;

                    if ($parcela == 1) {
                        if ($rowFornecedorConta->fornecedor_tipo_empresa == 2) { //CASO FOR DO TIPO ADMINSITRODORA DE CARTAO
                            $dataProximoVencimento = explode('-', $pagDataPagamento[$chave]);
                        } else {
//                        if ($Recebimento->getPago() == 1) {
//                            $dataProximoVencimento = explode('-', $proc_data_pag);
//                        } else {
                            $dataProximoVencimento = explode('-', $pagDataVencimento[$chave]);
//                        }
                        }

                        $idRecebimentoPrincipal = $this->recebimentoRepository->store($idDominio, $RECEBIMENTOS_OUTROS);
                        $idsRecebimentosProc[] = $idRecebimentoPrincipal;
                    } else {

                        $RECEBIMENTOS_OUTROS['recebimento_data'] = '';
                        $RECEBIMENTOS_OUTROS['recebimento_competencia'] = '';

                        $dataProximoVencimento = mktime(0, 0, 0, $dataProximoVencimento[1] + $rowRepeticao->quantidade_meses, $dataProximoVencimento[2] + $rowRepeticao->quantidade_dias, $dataProximoVencimento[0]);
                        $dataProximoVencimento = date('Y-m-d', $dataProximoVencimento);

                        if ($rowFornecedorConta->fornecedor_tipo_empresa == 2) { //CASO FOR DO TIPO ADMINSITRODORA DE CARTAO
                            $RECEBIMENTOS_OUTROS['recebimento_data'] = $dataProximoVencimento;
                            $RECEBIMENTOS_OUTROS['recebimento_data_vencimento'] = $dataProximoVencimento;
                        } else {
                            $RECEBIMENTOS_OUTROS['recebimento_data_vencimento'] = $dataProximoVencimento;
                        }

//                        if ($arrayProcPago[$chave]) {
//                            $Recebimento->setPago(1);
                        $RECEBIMENTOS_OUTROS['pago'] = 1;
                        $RECEBIMENTOS_OUTROS['recebimento_data'] = $dataProximoVencimento;
//                        }

                        $dataProximoVencimento = explode('-', $dataProximoVencimento);
                        $RECEBIMENTOS_OUTROS['vinculo_periodo_recebimento_id'] = $idRecebimentoPrincipal;
                        $idRecebimento = $this->recebimentoRepository->store($idDominio, $RECEBIMENTOS_OUTROS);
                    }
                }
            }

//            dd($idsRecebimentosProc);
        }
        //lançar os pagmanetos em dinheiro
        $valorRecebidoDinheiro = 0;
        $valorTaxaTotal = 0;
        if (count($ChavesPagDinheiro) > 0) {
            foreach ($ChavesPagDinheiro as $rowChave) {
                $valorRecebidoDinheiro += $arrayProcValor[$rowChave];
            }

            $valorRecebimento = $valorRecebidoDinheiro - $valorTroco;

//  dd($valorRecebimento);
            $RECEBIMENTOS_DINHEIRO['idsProcParcial'] = implode(',', $idsConsultaProcedimentos);
            $RECEBIMENTOS_DINHEIRO['pagar_com_adm_banco'] = $rowFornecedorConta->idFornecedor; //pacientes
            $RECEBIMENTOS_DINHEIRO['recebimento_valor'] = $valorRecebimento;
            $RECEBIMENTOS_DINHEIRO['tipo_pag_id'] = 1;
            $RECEBIMENTOS_DINHEIRO['pago'] = 1;
            $RECEBIMENTOS_DINHEIRO['periodo_repeticao_id'] = 1;
            $RECEBIMENTOS_DINHEIRO['valor_troco'] = $valorTroco;
            $RECEBIMENTOS_DINHEIRO['valor_recebido'] = $valorRecebidoDinheiro;

            $RECEBIMENTOS_DINHEIRO['recebimento_data_vencimento'] = $pagDataVencimento[$rowChave];
            $RECEBIMENTOS_DINHEIRO['recebimento_competencia'] = $pagDataVencimento[$rowChave];
            $RECEBIMENTOS_DINHEIRO['recebimento_data'] = $pagDataPagamento[$rowChave];

            if (!empty($formaPagArray[$arrayTipoPagamento[$rowChave]]->possui_taxa == 1)) {
                $valorDescontoTaxa = Functions::calcularDescontoPercentual($valorRecebimento, 1, $formaPagArray[$arrayTipoPagamento[$rowChave]]->percentual_taxa);
                $valorDescontoTaxa = number_format($valorDescontoTaxa, 2, '.', '');
                $valorTaxaTotal += $valorDescontoTaxa;
                $RECEBIMENTOS_DINHEIRO['possui_tp_pag_taxa'] = 1;
                $RECEBIMENTOS_DINHEIRO['percentual_pag_taxa'] = $formaPagArray[$arrayTipoPagamento[$rowChave]]->percentual_taxa;
                $RECEBIMENTOS_DINHEIRO['valor_tp_pag_taxa'] = $valorDescontoTaxa;
            }

//            dd($RECEBIMENTOS_DINHEIRO);
//            $RecebimentoDinheiro->setValor_sinal($valorSinal);
//            if ($permissaoOperadorCaixa == 1 and $rowCaixa) {
//                $RecebimentoDinheiro->setCaixa_id($rowCaixa->id);
//            }

            $idRecebimento = $this->recebimentoRepository->store($idDominio, $RECEBIMENTOS_DINHEIRO);
            $idsRecebimentosProc[] = $idRecebimento;
        }










        //verificar nas consultas se tem que lançar os dados de desconto e acrescimo
        if (!empty($consultasId)) {
            $CONSULTA_INSERT['tipo_desconto'] = $tipoDesconto;
            $CONSULTA_INSERT['percentual_desconto'] = ($tipoDesconto == 1) ? $valorDesconto : null;
            $CONSULTA_INSERT['desconto_reais'] = ($tipoDesconto == 2) ? $valorDesconto : null;
            $CONSULTA_INSERT['motivo_desconto'] = (!empty($tipoDesconto)) ? $motivoDesconto : null;
            $CONSULTA_INSERT['acrescimo_tipo'] = $tipoAcrescimo;
            $CONSULTA_INSERT['acrescimo_valor'] = ($tipoAcrescimo == 2) ? $valorAcrescimo : null;
            $CONSULTA_INSERT['acrescimo_percentual'] = ($tipoAcrescimo == 1) ? $valorAcrescimo : null;
            $CONSULTA_INSERT['acrescimo_motivo'] = (!empty($tipoAcrescimo)) ? $motivoAcrescimo : null;

//        $CONSULTA_INSERT['consulta_sinal'] = '';
//        $CONSULTA_INSERT['valor_recebido'] = $valorRecebidoDinheiro;
            $CONSULTA_INSERT['valor_troco'] = $valorTroco;

            $ConsultaRepository = new ConsultaRepository;
            $ConsultaRepository->updateConsulta($idDominio, $consultasId, $CONSULTA_INSERT);
        }


        if (count($idsRecebimentosProc) > 0) {
            $LogAtividadesService = new LogAtividadesService();
            if ($rowConsulta) {
                $StatusRefreshRepository = new StatusRefreshRepository;
                $StatusRefreshRepository->insertAgenda($idDominio, $rowConsulta->doutores_id);
                $msgLog = 'Pagou a consulta do dia ' . Functions::dateDbToBr($rowConsulta->data_consulta) . ' às ' . substr($rowConsulta->hora_consulta, 0, 5) .
                        'Paciente : ' . $rowConsulta->nomePaciente . ' ' . $rowConsulta->sobrenomePaciente . ' com o(a) doutor(a) ' . $rowConsulta->nomeDoutor . '.';
                $LogAtividadesService->store($idDominio, 3, utf8_encode($msgLog), $rowConsulta->id, 3);
            }
            return $this->returnSuccess(['idsRecebimentos' => $idsRecebimentosProc]);
        } else {
            return $this->returnError(null, 'Ocorreu um erro ao realizar o pagamento');
        }
    }

    public function downloadRecibo($dadosQuery) {

        $codeRecibo = json_decode(Crypt::decrypt($dadosQuery['code']));
        $idDominio = $codeRecibo->idDominio;
        $nomeDominio = $codeRecibo->nomeDominio;
        $tipo = $codeRecibo->tipo;
        $idTipo = $codeRecibo->idTipo;

        $params = [
            'getReciboApi' => 1,
            'tipo' => $tipo,
            'idTipo' => $idTipo,
            'identificador' => $idDominio,
        ];
        $urlRecibo = env('APP_URL_CLINICAS') . '/' . $nomeDominio . '/admin/financeiro/recibo_print_pdf_api.php';

        $curlHandle = curl_init($urlRecibo);
        curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $params);
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);

        $curlResponse = curl_exec($curlHandle);
        
        if(!empty($curlResponse)){
          return $this->returnSuccess(env('APP_PATH_CLINICAS').  '/' . $nomeDominio . '/arquivos/temp/'.$curlResponse);
        } else {
            return $this->returnError(null, 'Ocorreu um erro ao baixar o recibo, por favor tente mais tarde.');
        }
        
      
    }
}
