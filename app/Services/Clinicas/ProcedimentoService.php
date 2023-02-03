<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\Clinicas;

use App\Services\BaseService;
use DateTime;
use App\Repositories\Clinicas\ProcedimentosRepository;
use App\Helpers\Functions;
use App\Repositories\Gerenciamento\DominioRepository;
use App\Repositories\Clinicas\ConvenioRepository;

/**
 * Description of Activities
 *
 * @author ander
 */
class ProcedimentoService extends BaseService {

    private $procedimentoRepository;

    public function __construct(ProcedimentosRepository $procRep) {
        $this->procedimentoRepository = $procRep;
    }

    public function getByConsulta($idDominio, $consultaId, $request) {

        $qr = $this->procedimentoRepository->getByConsultaId($idDominio, $consultaId);

        if (count($qr) > 0) {
            $retorno = [];
            foreach ($qr as $row) {
                $retorno[] = [
                    'idConsultaProcedimento' => $row->id,
                    'consultaId' => $row->consultas_id,
                    'procedimentoId' => $row->procedimentos_id,
                    'nomeProcedimento' => Functions::utf8Fix($row->nome_proc),
//                    'codigo' => $row->codigo_proc,
                    'qnt' => $row->qnt,
                    'valor' => $row->valor_proc,
                    'repasse' => [
                        'valorRepasse' => $row->valor_repasse,
                        'origemRepasse' => $row->origem_repasse,
                        'tipoRepasse' => $row->tipo_repasse,
                        'percentualRepasse' => $row->percentual_proc_repasse,
                    ],
                    'doutorExecutante' => [
                        'id' => $row->executante_doutores_id,
                        'nome' => $row->executante_nome
                    ],
                    'convenio' => [
                        'id' => $row->convenios_id,
                        'nome' => Functions::correcaoUTF8Decode($row->nome_convenio),
                    ],
                    'procedimentoCategoria' => [
                        'id' => $row->procedimentos_cat_id,
                        'nome' => Functions::utf8Fix($row->procedimentos_cat_nome)
                    ],
                    'dataCad' => $row->data_cad,
                    'impostoRendaConvenio' => $row->imposto_renda_convenio,
                    'valorTaxaTipoPg' => $row->valor_taxa_tipo_pg,
                    'aliquotaIss' => $row->aliquota_iss,
                    'aliquotaIssPercentual' => $row->aliquota_iss_percentual,
                    'possuiParceiro' => $row->possui_parceiro,
                    'parceiro' => (!empty($row->doutor_parceiro_id)) ? [
                'id' => $row->doutor_parceiro_id,
                'nome' => $row->nomeDoutorParceiro,
                    ] : null
                ];
            }


            return $this->returnSuccess($retorno);
        } else {
            return $this->returnError(null, ["Sem procedimentos para esta consulta"]);
        }
    }

    public function getByDoutor($idDominio, $doutorId, $request) {

        $DominioRepository = new DominioRepository;
        $rowDominio = $DominioRepository->getById($idDominio);

        $dadosPaginacao = $this->getPaginate($request);
        $dadosFiltro = null;

        if ($rowDominio->alteracao_docbizz == 1 and auth('clinicas')->check()) {
            $dadosFiltro['exibeDocBizz'] = 1;
        }


        if ($request->has('search') and!empty($request->query('search'))) {
            $dadosFiltro['search'] = $request->query('search');
        }


        if ($request->has('convenioId') and!empty($request->query('convenioId')) and is_numeric($request->query('convenioId'))) {
            $dadosFiltro['convenioId'] = $request->query('convenioId');
        }


        $qr = $this->procedimentoRepository->getProcedimentoPorDoutor($idDominio, $doutorId, $dadosFiltro, $dadosPaginacao['page'], $dadosPaginacao['perPage']);

        if (count($qr['results']) > 0) {
//            dd($qr);
            $retorno = [];
            foreach ($qr['results'] as $row) {

                $retorno[] = [
                    'id' => $row->id,
//                    'doutor' => [
//                        'id' => $row->doutores_id,
//                        'nome' => $row->nomeDoutor
//                    ],
                    'procedimentoId' => $row->procedimentos_id,
                    'procConvId' => $row->procedimentos_id . '_' . $row->proc_convenios_id,
                    'nomeProcedimento' => Functions::utf8Fix($row->nomeProcedimento),
                    'codigo' => $row->codigoProc,
                    'descricaoProcedimento' => Functions::utf8Fix($row->procedimento_descricao),
                    'duracao' => $row->duracao,
                    'retorno' => $row->retorno,
                    'mostraMinisite' => $row->mostra_minisite,
                    'impostoConv' => $row->impostoConv,
                    'valor' => $row->valor_proc,
                    'exibeAppNeofor' => ($row->exibir_app_docbizz == 1) ? true : false,
                    'procedimentoCategoria' => [
                        'id' => $row->procedimento_categoria_id,
                        'nome' => Functions::utf8Fix($row->proc_cat_nome)
                    ],
                    'repasse' => [
                        'possuiRepasse' => $row->possui_repasse,
                        'tipoRepasse' => $row->tipo_repasse,
                        'valorPercentual' => $row->valor_percentual,
                        'valorReal' => $row->valor_real,
                        'valorRepasse' => $row->valorRepasse,
                    ],
                    'convenio' => [
                        'id' => $row->proc_convenios_id,
                        'nome' => Functions::correcaoUTF8Decode($row->nomeConvenioProc),
                    ],
                    'possui_parceiro' => $row->possui_parceiro,
                    'parceiro' => (!empty($row->doutor_parceiro_id)) ? [
                'id' => $row->doutor_parceiro_id,
                'nome' => $row->nomeDoutorParceiro,
                    ] : null
                ];
            }
            $qr['results'] = $retorno;

//               var_dump($qr);
//            dd($qr);
            return $this->returnSuccess($qr);
        } else {
            return $this->returnError(null, ["Sem procedimentos para este profissional"]);
        }
    }

}
