<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\Clinicas\Financeiro;

use App\Services\BaseService;
use DateTime;
use App\Repositories\Clinicas\Financeiro\RelatorioMensalPdfRepository;
use App\Helpers\Functions;
use App\Repositories\Gerenciamento\DominioRepository;
use App\Repositories\Clinicas\ConsultaRepository;
use App\Services\Clinicas\Financeiro\RecebimentoService;
use App\Repositories\Clinicas\Consulta\ConsultaProcedimentoRepository;
use App\Repositories\Clinicas\DoutorRepository;

/**
 * Description of Activities
 *
 * @author ander
 */
class RelatorioService extends BaseService {

    private $relatorioMensalPdfRepository;

    public function __construct() {
        $this->relatorioMensalPdfRepository = new RelatorioMensalPdfRepository;
    }

    public function getRelatorioMensalPdf($idDominio, $dadosFiltro = null) {

        $qr = $this->relatorioMensalPdfRepository->getAll($idDominio, $dadosFiltro);
        $retorno = [];

        $DominioRepository = new DominioRepository;
        $rowDominio = $DominioRepository->getById($idDominio);

        if (count($qr) > 0) {

            foreach ($qr as $row) {
                $retorno[] = [
                    'id' => $row->id,
                    'doutorId' => $row->doutores_id,
                    'nomeDoutor' => $row->nomeDoutor,
                    'mes' => $row->mes,
                    'ano' => $row->ano,
                    'dataCad' => $row->data_cad,
                    'userCad' => $row->administrador_id_cad,
                    'nomeUserCad' => $row->nomeUserCad,
                    'urlArquivo' => 'https://app.simdoctor.com.br/' . $rowDominio->dominio . '/arquivos/relatorios_mensais_pdf_inf/' . $row->ano . '/' . sprintf('%02s', $row->mes) . '/' . rawurlencode($row->arquivo),
                ];
            }


            return $this->returnSuccess($retorno, null);
        } else {
            return $this->returnError(null, 'Sem registros de arquivos');
        }
    }

    public function getRelatorioDiarioDoutor($idDominio, $idDoutor, $dadosFiltro) {


        $dadosFiltroConsulta['doutoresId'] = $idDoutor;
        $RecebimentoService = new RecebimentoService;
        $ConsultaRepository = new ConsultaRepository;
        $DoutorRepository = new DoutorRepository;

        $rowDoutor = $DoutorRepository->getById($idDominio, $idDoutor);

        if (!$rowDoutor) {
            return $this->returnError(null, "Doutor(a) não encontrado");
        }



        if (isset($dadosFiltro['calendario']) and $dadosFiltro['calendario'] == true) {
            $dadosFiltroConsulta['dataInicio'] = $dadosFiltro['ano'] . '-' . sprintf('%02d', $dadosFiltro['mes']) . '-01';


            $ultimoDia = date('t', strtotime($dadosFiltroConsulta['dataInicio']));

            $dadosFiltroConsulta['dataFim'] = $dadosFiltro['ano'] . '-' . sprintf('%02d', $dadosFiltro['mes']) . '-' . $ultimoDia;


            $qrConsultas = $ConsultaRepository->getAll($idDominio, $dadosFiltroConsulta);
            $calendarioDados = null;


            $count = 0;
            $retorno = [];
            if (count($qrConsultas) > 0) {
                foreach ($qrConsultas as $rowConsulta) {

                    $calendarioDados[$rowConsulta->data_consulta] = true;
                }


                foreach ($calendarioDados as $dataCons => $row) {
                    $retorno[] = array('date' => $dataCons,
//                    'disp' => $row
                    );
                }
            }
            if (count($retorno) > 0) {
                return $this->returnSuccess($retorno);
            } else {
                return $this->returnError(null, 'Nenhum registro encontrado');
            }
        } else {
            $dadosFiltroConsulta['orderBy'] = 'A.hora_consulta ASC';
            $dadosFiltroConsulta['dataInicio'] = $dadosFiltro['data'];
            $qrConsultas = $ConsultaRepository->getAll($idDominio, $dadosFiltroConsulta);
        }

//  if (auth('clinicas')->user()->id = 4055) {
//      dd($dadosFiltroConsulta);
//  }


        $DADOS = null;
        $count = 0;
        $qntProcedimentos = 0;
        $qntConsultas = 0;

        $totalFaturado = 0;
        $totalNaoFaturado = 0;

        if (count($qrConsultas) > 0) {
            foreach ($qrConsultas as $rowConsulta) {

                $DADOS[$count]['id'] = $rowConsulta->id;
                $DADOS[$count]['doutorId'] = $rowConsulta->doutores_id;
                $DADOS[$count]['telefone'] = $rowConsulta->telefonePaciente;
                $DADOS[$count]['telefone2'] = $rowConsulta->telefonePaciente2;
                $DADOS[$count]['celular'] = $rowConsulta->celularPaciente;
                $DADOS[$count]['dataConsulta'] = $rowConsulta->data_consulta;
                $DADOS[$count]['horaConsulta'] = $rowConsulta->hora_consulta;
                $DADOS[$count]['nomeDoutor'] = $rowConsulta->nomeDoutor;
//                $DADOS[$count]['especialidade'] = $rowConsulta->especialidade;
                $DADOS[$count]['nomePaciente'] = $rowConsulta->nomePaciente;
                $DADOS[$count]['sobrenomePaciente'] = $rowConsulta->sobrenomePaciente;
                $DADOS[$count]['emailPaciente'] = $rowConsulta->emailPaciente;
                $DADOS[$count]['confirmacao'] = $rowConsulta->confirmacao;
                $DADOS[$count]['statusConsulta'] = $rowConsulta->statusConsulta;
//                $DADOS[$count]['dadoonsulta'] = $rowConsulta->dados_consulta;
                $DADOS[$count]['marcadoPor'] = ($rowConsulta->marcadoPor);
//                $DADOS[$count]['nomeTipoContato'] = $rowConsulta->nomeTipoContato;
                $DADOS[$count]['nomeConvenio'] = Functions::correcaoUTF8Decode($rowConsulta->nomeConvenio);
                $DADOS[$count]['valorTotalLiquido'] = 0;
                $DADOS[$count]['pago'] = false;


                $qrRecebimentosEfetuados = $RecebimentoService->getDadosPagamentosEfetuados($idDominio, 'consulta', $rowConsulta->id);


                $PROCEDIMENTOS = null;
                $PROCEDIMENTOS = 0;

                $tipoPagamentoReceb = [];
                $procedimentosList = null;


                if (!empty($qrRecebimentosEfetuados)) {
                    foreach ($qrRecebimentosEfetuados as $items) {

                        $DADOS[$count]['pago'] = true;

//                        $idsRecebimento = $items['idsRecebimento'];
//                        if (auth('clinicas')->user()->id == 4055) {
//                            dd($items);
//                        }
                        foreach ($items['procedimentos'] as $proc) {
//
                            $qntProcConsulta = Functions::qntProcedimentosDocBizz(utf8_decode($proc['nome']), $proc['qnt'], $proc['idConvenio'], count($items['procedimentos']));
                            $qntConsultas += $qntProcConsulta['consulta'];
                            $qntProcedimentos += $qntProcConsulta['procedimento'];

                            //utf8 correção
                            $nomeProc = Functions::correcaoUTF8Decode($proc['nome']);

                            $procedimentosList[] = [
                                'id' => $proc['idProcItem'],
                                'procedimentoId' => $proc['procedimentoId'],
                                'procConvId' => $proc['procedimentoId'] . '_' . $proc['idConvenio'],
                                'nome' => $nomeProc,
                                'convenioId' => $proc['idConvenio'],
                                'convenioNome' =>  Functions::correcaoUTF8Decode($proc['nomeConvenio']),
                                'valor' => $proc['valorProc'],
                                'qnt' => $proc['qnt'],
                                'nomeCategoria' => $proc['nomeCategoria'],
                                'pagParcial' => $proc['pagParcial'],
                                'idCarteiraItem' => $proc['idCarteiraItem'],
                                'financeiroRecebimentosId' => $proc['financeiroRecebimentosId'],
                                'codCarteiraVirtual' => $proc['codCarteiraVirtual'],
                                'nomePacote' => $proc['nomePacote'],
                                'exibeAppNeofor' => ($proc['exibirAppNeofor'] == 1) ? true : false
                            ];


//
//                            $PROCEDIMENTOS[$countProc] = array(
//                                'nome' => $proc['nome'],
//                                'valor' => $proc['valorProc'],
//                                'valorRepasse' => $proc['valorRepasse'],
//                                'nomeConvenio' => $proc['nomeConvenio'],
//                                'tipoRepasse' => $proc['tipoRepasse'],
//                                'origemRepasse' => $proc['origemRepasse'],
//                                'percentualRepasse' => $proc['percentualRepasse'],
//                                'percentImpostoRendaConvenio' => $proc['percentImpostoRendaConvenio'],
//                                'valorLiquidoProc' => (empty($proc['nome_pacote'])) ? $proc['valorLiquidoProc'] : 0,
//                                'idCarteiraItem' => $proc['id_carteira_item'],
//                                'codCarteiraVirtual' => $proc['codCarteiraVirtual'],
//                                'aliquotaIss' => $proc['aliquotaIss'],
//                                'aliquotaIssPercentual' => $proc['aliquotaIssPercentual'],
//                                'nomePacote' => $proc['nome_pacote'],
//                                'baseCalcPacoteItem' => $proc['baseCalcPacoteItem'],
//                            );
//
//
//
//                            if (!empty($proc['percentImpostoRendaConvenio'])) {
//                                $valorProc = (empty($proc['nome_pacote'])) ? $proc['valorProc'] : $proc['baseCalcPacoteItem'];
//                                $ARRAY_TOTAL_DOUT[$row->doutores_id]['totalIRConvenio'] += calcularDescontoPercentual($valorProc, 1, $proc['percentImpostoRendaConvenio']);
//                            }
//
//
//
//                            $ARRAY_TOTAL_DOUT[$row->doutores_id]['totalBruto'] += $proc['valorProc'];
//                            $ARRAY_TOTAL_DOUT[$row->doutores_id]['totalRepasses'] += $proc['valorRepasse'];
//                            $valorTotalBruto += (empty($proc['nome_pacote'])) ? $proc['valorProc'] * $proc['qnt'] : 0;
//                            $DADOS[$count]['valorTotal'] += (empty($proc['nome_pacote'])) ? $proc['valorProc'] * $proc['qnt'] : 0;
//                            $DADOS[$count]['valorLiquido'] += (empty($proc['nome_pacote'])) ? $proc['valorProc'] * $proc['qnt'] : 0;
//                            $DADOS[$count]['valorTotalRepasse'] += $proc['valorRepasse'];
//
//
//
//
////            if (!empty($proc['aliquotaIss'])  !isset($DADOS[$count]['issNF'][implode(',', $idsRecebimento)])) {
////                $DADOS[$count]['issNF'] = array(
////                    'aliquotaIss' => $proc['aliquotaIss'],
////                    'aliquotaIss' => $proc['aliquotaIss'],
////                    'aliquotaIssPercentual' => $proc['aliquotaIssPercentual'],
////                );
////            }
//
//                            $countProc++;
//                        }
//                           
//                            if (!empty($rowReceb['taxa'])) {
//                                $valoDescPag = number_format(calcularDescontoPercentual($rowReceb['valor_total'], 1, $rowReceb['taxa']['percentual']), 2, '.', '');
//                                $DADOS[$count]['valorLiquido'] -= $valoDescPag;
//                                $ARRAY_TOTAL_DOUT[$row->doutores_id]['totalDescontoPag'] += $valoDescPag;
//                            }
//                            $TIPOS_PG[] = array(
//                                'codigoRecebimento' => $rowReceb['codigoRecebimento'],
//                                'tipoPg' => $rowReceb['tipo_pag_nome'],
//                                'taxa' => $rowReceb['taxa'],
//                                'valor' => $rowReceb['valor_total'],
//                            );
                        }

                        foreach ($items['dadosRecebimento'] as $rowReceb) {

                            if (!in_array(utf8_decode($rowReceb['tipo_pag_nome']), $tipoPagamentoReceb)) {
                                $tipoPagamentoReceb[] = utf8_decode($rowReceb['tipo_pag_nome']);
                            }


//                       


                            $totalFaturado += $rowReceb['valor_total'];
                            $DADOS[$count]['valorTotalLiquido'] += $rowReceb['valor_total'];
                        }
                    }
                } else {
                    $ConsultaProcedimentoRepository = new ConsultaProcedimentoRepository;
                    $qrProc = $ConsultaProcedimentoRepository->getByConsultaId($idDominio, $rowConsulta->id);
                    $countProc = 0;
                    $valorTotalNaoPago = 0;




                    foreach ($qrProc as $proc) {



                        $qntProcConsulta = Functions::qntProcedimentosDocBizz(utf8_decode($proc->nome_proc), $proc->qnt, $proc->convenios_id, count($qrProc));
                        $qntConsultas += $qntProcConsulta['consulta'];
                        $qntProcedimentos += $qntProcConsulta['procedimento'];


                        //utf8 correção
                        $nomeProc = Functions::correcaoUTF8Decode($proc->nome_proc);


                        $procedimentosList[] = [
                            'id' => $proc->id,
                            'procedimentoId' => $proc->procedimentos_id,
                            'procConvId' => $proc->procedimentos_id . '_' . $proc->convenios_id,
                            'nome' => $nomeProc,
                            'convenioId' => $proc->convenios_id,
                            'convenioNome' => Functions::correcaoUTF8Decode($proc->nome_convenio),
                            'valor' => $proc->valor_proc,
                            'qnt' => $proc->qnt,
                            'nomeCategoria' => $proc->procedimentos_cat_nome,
                            'pagParcial' => $proc->pag_parcial,
                            'idCarteiraItem' => $proc->id_carteira_item,
                            'financeiroRecebimentosId' => $proc->financeiro_recebimentos_id,
                            'codCarteiraVirtual' => $proc->codCarteiraVirtual,
                            'nomePacote' => $proc->nome_pacote,
                            'exibeAppNeofor' => ($proc->exibir_app_docbizz == 1) ? true : false
                        ];



//                        $PROCEDIMENTOS[$countProc] = array(
//                            'nome' => $proc->nome_proc,
//                            'valor' => $proc->valor_proc,
////                    'valorRepasse' => $proc->valor_repasse,
//                            'nomeConvenio' => $proc->nome_convenio,
//                            'tipoRepasse' => $proc->tipo_repasse,
//                            'origemRepasse' => $proc->origem_repasse,
//                            'percentualRepasse' => $proc->percentual_proc_repasse,
//                            'percentImpostoRendaConvenio' => $proc->imposto_renda_convenio,
//                            'valorLiquidoProc' => (empty($proc->nome_pacote)) ? $proc->valor_liquido_proc : 0,
//                            'idCarteiraItem' => $proc->id_carteira_item,
//                            'codCarteiraVirtual' => $proc->codCarteiraVirtual,
//                            'aliquotaIss' => $proc->aliquota_iss,
//                            'aliquotaIssPercentual' => $proc->aliquota_iss_percentual,
//                            'nomePacote' => $proc->nome_pacote,
//                            'baseCalcPacoteItem' => $proc->base_calc_pacote_item,
//                        );
//                        if (!empty($proc->imposto_renda_convenio)) {
//                            $valorProc = (empty($proc->nome_pacote)) ? $proc->valor_proc : $proc->base_calc_pacote_item;
//                            $ARRAY_TOTAL_DOUT[$row->doutores_id]['totalIRConvenio'] += calcularDescontoPercentual($valorProc, 1, $proc->imposto_renda_convenio);
//                        }
//                        $ARRAY_TOTAL_DOUT[$row->doutores_id]['totalBruto'] += $proc->valor_proc;
//                $ARRAY_TOTAL_DOUT[$row->doutores_id]['totalRepasses'] += $proc->valorRepasse;
//                        $valorTotalBruto += (empty($proc->nome_pacote)) ? $proc->valor_proc : 0;
//                $DADOS[$count]['valorTotalRepasse'] += $proc->valor_repasse;
//            if (!empty($proc['aliquotaIss'])  !isset($DADOS[$count]['issNF'][implode(',', $idsRecebimento)])) {
//                $DADOS[$count]['issNF'] = array(
//                    'aliquotaIss' => $proc['aliquotaIss'],
//                    'aliquotaIss' => $proc['aliquotaIss'],
//                    'aliquotaIssPercentual' => $proc['aliquotaIssPercentual'],
//                );
//            }


                        $valorTotalNaoPago += (empty($proc->nome_pacote)) ? $proc->valor_proc : 0;


                        $countProc++;
                    }

                    $valorDesconto = 0;
                    $tipo_desconto = $rowConsulta->tipo_desconto;
                    $valorDesconto = ($tipo_desconto == 2) ? $rowConsulta->desconto_reais : Functions::calcularDescontoPercentual($valorTotalNaoPago, 1, $rowConsulta->percentual_desconto);



                    $valorAcrescimo = 0;
                    $tipo_acrescimo = $rowConsulta->acrescimo_tipo;
                    $valorAcrescimo = ($tipo_acrescimo == 2) ? $rowConsulta->acrescimo_valor : Functions::calcularDescontoPercentual($valorTotalNaoPago, 1, $rowConsulta->acrescimo_percentual);

                    $valorTotalNaoPago = $valorTotalNaoPago - $valorDesconto + $valorAcrescimo;
                    $totalNaoFaturado += $valorTotalNaoPago;
                    $DADOS[$count]['valorTotalLiquido'] = number_format($valorTotalNaoPago, 2, '.', '');
                    $valorTotalNaoPago = 0;
                }

//                if (auth('clinicas')->user()->id == 4055) {
//                    array_filter($tipoPagamentoReceb);
//                    var_Dump(array_unique($tipoPagamentoReceb));
//                }
                $DADOS[$count]['tipoPagamento'] = implode(',', $tipoPagamentoReceb);
                $DADOS[$count]['procedimentos'] = $procedimentosList;


                $count++;
            }
        }

//        dd($DADOS);
        $retorno['dataAgendamento'] = Functions::dateDbToBr($dadosFiltro['data']);
        $retorno['qntConsultas'] = $qntConsultas;
        $retorno['qntProcedimentos'] = $qntProcedimentos;
        $retorno['totalFaturado'] = number_format($totalFaturado, 2, '.', '');
        $retorno['totalNaoFaturado'] = number_format($totalNaoFaturado, 2, '.', '');
        $retorno['dados'] = $DADOS;

        return $this->returnSuccess($retorno);
    }

}
