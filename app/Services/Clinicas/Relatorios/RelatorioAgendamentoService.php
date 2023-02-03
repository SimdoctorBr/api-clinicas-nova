<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\Clinicas\Relatorios;

use App\Services\BaseService;
use DateTime;
use App\Services\Clinicas\CalculosService;
use App\Services\Clinicas\ConsultaService;
use App\Services\Clinicas\Consulta\ConsultaProcedimentoService;
use App\Repositories\Clinicas\ConsultaRepository;
use App\Services\Clinicas\Financeiro\RecebimentoService;
use App\Helpers\Functions;

/**
 * Description of Activities
 *
 * @author ander
 */
class RelatorioAgendamentoService extends BaseService {

    public function __construct() {
        
    }

    public function getRelatorioAgendamento($idDominio, $dadosFiltro) {



        dd($dadosFiltro);
    }

    public function getRelatorioAgendamento2($idDominio, $dadosFiltro) {

        $ConsultaRepository = new ConsultaRepository;
        $dataFim = (isset($dadosFiltro['dataFim']) and!empty($dadosFiltro['dataFim'])) ? $dadosFiltro['dataFim'] : null;

        $RecebimentoService = new RecebimentoService;
        $retorno = null;
//        $dadosFiltro['orderBy'] = ''
        $page = (isset($dadosFiltro['page']) and!empty($dadosFiltro['page'])) ? $dadosFiltro['page'] : 1;
        $perPage = 100;
        if (isset($dadosFiltro['perPage']) and!empty($dadosFiltro['perPage'])) {
            $perPage = ($dadosFiltro['perPage'] > 100) ? 100 : $dadosFiltro['perPage'];
        }
        $qr = $ConsultaRepository->getAllConsultasAgendaPorData($idDominio, $dadosFiltro['data'], $dataFim, $dadosFiltro, $page, $perPage);
        foreach ($qr['results'] as $chave => $rowCons) {
            $retorno[$chave] = [
                'consultaId' => $rowCons->id,
                'dataAgendamento' => $rowCons->data_consulta,
                'horaAgendamento' => $rowCons->hora_consulta,
                'marcadoPor' => $rowCons->marcadoPor,
                'statusConsulta' => $rowCons->statusConsulta,
                'tipoConfirmacao' => $rowCons->confirmacao,
                'pacientes' => [
                    'id' => $rowCons->pacientes_id,
                    'nome' => $rowCons->nomePaciente,
                    'sobrenome' => $rowCons->sobrenomePaciente,
                    'telefone' => $rowCons->telefone,
                    'telefone2' => $rowCons->telefone2,
                    'celular' => $rowCons->celular,
                ],
                'doutores' => [
                    'id' => $rowCons->doutores_id,
                    'nome' => $rowCons->nomeDoutor
                ]
            ];
            $retorno[$chave]['valorTotal'] = 0;
            $retorno[$chave]['valorLiquido'] = 0;
            $retorno[$chave]['valorTotalRepasse'] = 0;

            $qrRecebimentoEfetuados = $RecebimentoService->getDadosPagamentosEfetuados($idDominio, 'consulta', $rowCons->id);
//            dd($retorno);
//            dd($qrRecebimentoEfetuados);
            $valorTotalBruto = 0;
            $countProc = 0;
            $valorProc = null;
            $PROCEDIMENTOS = null;
            $TIPOS_PG = null;

            ///consultas pagas
            if (!empty($qrRecebimentoEfetuados)) {
                foreach ($qrRecebimentoEfetuados as $items) {

                    $idsRecebimento = $items['idsRecebimento'];

                    foreach ($items['procedimentos'] as $proc) {

                        $PROCEDIMENTOS[$countProc] = array(
                            'nome' => Functions::correcaoUTF8Decode($proc['nome']),
                            'valor' => $proc['valorProc'],
                            'valorRepasse' => $proc['valorRepasse'],
                            'convenio' => [
                                'id' => $proc['idConvenio'],
                                'nome' => Functions::correcaoUTF8Decode($proc['nomeConvenio']),
                            ],
                            'tipoRepasse' => $proc['tipoRepasse'],
                            'origemRepasse' => (isset($proc['origemRepasse'])) ? $proc['origemRepasse'] : '',
                            'percentualRepasse' => $proc['percentualRepasse'],
                            'percentImpostoRendaConvenio' => $proc['percentImpostoRendaConvenio'],
                            'valorLiquidoProc' => (isset($proc['nome_pacote']) and!empty($proc['nome_pacote'])) ? 0 : $proc['valorLiquidoProc'],
                            'idCarteiraItem' => (isset($proc['id_carteira_item'])) ? $proc['id_carteira_item'] : '',
                            'codCarteiraVirtual' => (isset($proc['codCarteiraVirtual'])) ? $proc['codCarteiraVirtual'] : '',
                            'aliquotaIss' => $proc['aliquotaIss'],
                            'aliquotaIssPercentual' => $proc['aliquotaIssPercentual'],
                            'nomePacote' => (isset($proc['nome_pacote']) and!empty($proc['nome_pacote'])) ? $proc['nome_pacote'] : '',
                            'baseCalcPacoteItem' => $proc['baseCalcPacoteItem'],
                        );

                        if (!empty($proc['percentImpostoRendaConvenio'])) {
                            $valorProc = (empty($proc['nome_pacote'])) ? $proc['valorProc'] : $proc['baseCalcPacoteItem'];
//                        $ARRAY_TOTAL_DOUT[$rowCons->doutores_id]['totalIRConvenio'] += Functions::calcularDescontoPercentual($valorProc, 1, $proc['percentImpostoRendaConvenio']);
                        }

                        $valorTotalBruto += (empty($proc['nome_pacote'])) ? $proc['valorProc'] * $proc['qnt'] : 0;
                        $retorno[$chave]['valorTotal'] += (isset($proc['nome_pacote']) and!empty($proc['nome_pacote'])) ? 0 : $proc['valorProc'] * $proc['qnt'];
                        $retorno[$chave]['valorLiquido'] += (isset($proc['nome_pacote']) and!empty($proc['nome_pacote'])) ? 0 : $proc['valorProc'] * $proc['qnt'];
                        $retorno[$chave]['valorTotalRepasse'] += $proc['valorRepasse'];

                        $countProc++;
                    }

                    if ($items['valor_desconto'] > 0) {
                        $retorno[$chave]['valorDesconto'] = $items['valor_desconto'];
                        $retorno[$chave]['valorLiquido'] -= $items['valor_desconto'];
                    }

                    foreach ($items['dadosRecebimento'] as $rowReceb) {
                        if (!empty($rowReceb['taxa'])) {

                            $valoDescPag = number_format(Functions::calcularDescontoPercentual($rowReceb['valor_total'], 1, $rowReceb['taxa']['percentual']), 2, '.', '');
                            $retorno[$chave]['valorLiquido'] -= $valoDescPag;
//                            $ARRAY_TOTAL_DOUT[$row->doutores_id]['totalDescontoPag'] += $valoDescPag;
                        }
                        $TIPOS_PG[] = array(
                            'codigoRecebimento' => $rowReceb['codigoRecebimento'],
                            'tipoPg' => utf8_decode($rowReceb['tipo_pag_nome']),
                            'taxa' => $rowReceb['taxa'],
                            'valor' => $rowReceb['valor_total'],
                        );
                    }
                }
            } else {
                $ConsultaProcedimentoService = new ConsultaProcedimentoService;
                $qrConsultaProc = $ConsultaProcedimentoService->getByConsultaId($idDominio, $rowCons->id);
                if ($qrConsultaProc) {
                    foreach ($qrConsultaProc as $proc) {

//                    dd($proc);
                        $PROCEDIMENTOS[$countProc] = array(
                            'nome' => Functions::correcaoUTF8Decode($proc['nome']),
                            'valor' => $proc['valor'],
//                    'valorRepasse' => $proc->valor_repasse,
                            'convenio' => [
                                'id' => $proc['convenioId'],
                                'nome' => Functions::correcaoUTF8Decode($proc['convenioNome']),
                            ],
                            'tipoRepasse' => '',
                            'origemRepasse' => '',
                            'percentualRepasse' => '',
                            'percentImpostoRendaConvenio' => '',
                            'valorLiquidoProc' => '',
                            'idCarteiraItem' => (isset($proc['idCarteiraItem'])) ? $proc['idCarteiraItem'] : '',
                            'codCarteiraVirtual' => (isset($proc['codCarteiraVirtual'])) ? $proc['codCarteiraVirtual'] : '',
                            'aliquotaIss' => '',
                            'aliquotaIssPercentual' => '',
                            'nomePacote' => (isset($proc['nome_pacote']) and!empty($proc['nome_pacote'])) ? $proc['nome_pacote'] : '',
//                        'baseCalcPacoteItem' => $proc->base_calc_pacote_item,
                        );

                        if (!empty($proc['impostoRendaConvenio'])) {
                            $valorProc = (empty($proc['nomePacote'])) ? $proc['valor'] : $proc['baseCalcPacoteItem'];
//                        $ARRAY_TOTAL_DOUT[$row->doutores_id]['totalIRConvenio'] += calcularDescontoPercentual($valorProc, 1, $proc->imposto_renda_convenio);
                        }



//                    $ARRAY_TOTAL_DOUT[$row->doutores_id]['totalBruto'] += $proc->valor_proc;
                        $valorTotalBruto += (empty($proc['nome_pacote'])) ? $proc['valor'] * $proc['qnt'] : 0;
                        $retorno[$chave]['valorTotal'] += (isset($proc['nome_pacote']) and!empty($proc['nome_pacote'])) ? 0 : $proc['valor'] * $proc['qnt'];

                        $countProc++;
                    }
                }

                $retorno[$chave]['valorDesconto'] = 0;
                if ($rowCons->tipo_desconto == 1 and $rowCons->desconto_reais > 0) {
                    $retorno[$chave]['valorDesconto'] = $rowCons->desconto_reais;
                } else
                if ($rowCons->tipo_desconto == 2 and!empty($rowCons->percentual_desconto)) {

                    $retorno[$chave]['valorDesconto'] = calcularDescontoPercentual($valorTotalBruto, 1, $row->percentual_desconto);
                }
            }
            $retorno[$chave]['procedimentos'] = $PROCEDIMENTOS;
            $retorno[$chave]['tiposPagamento'] = $TIPOS_PG;
        }

        $qr['results'] = $retorno;
        $retorno = $qr;
//        var_Dump($retorno);

        return $this->returnSuccess($retorno);
    }

}
