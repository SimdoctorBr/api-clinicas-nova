<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\TuoTempo\Clinicas;

use App\Services\BaseService;
use App\Repositories\Clinicas\ProcedimentosRepository;
use App\Repositories\Gerenciamento\DominioRepository;

/**
 * Description of Activities
 *
 * @author ander
 */
class ActivitiesService extends BaseService {

    private $procedimentosRepository;
    private $dominioRepository;

    public function __construct(ProcedimentosRepository $procRep, DominioRepository $domRep) {
        $this->procedimentosRepository = $procRep;
        $this->dominioRepository = $domRep;
    }

    public function getProcedimentosPorDoutor($request) {


//        if (!$request->has('LOCATION_LID') or empty($request->get('LOCATION_LID'))) {
//            return $this->returnError(null, "uninformed LOCATION_LID ");
//        }

        $idDoutor = (!empty($request->get('RESOURCE_LID'))) ? str_replace('dout', '', $request->get('RESOURCE_LID')) : null;
        $convenioId = (!empty($request->get('INSURANCE_LID'))) ? $request->get('INSURANCE_LID') : null;

        $possuiFiltro = false;
        if (!empty($idDoutor) or!empty($convenioId)) {
            $possuiFiltro = true;
        }

        $qrDominios = $this->dominioRepository->getAllByUser(auth()->user()->id);
//        if ($request->has('LOCATION_LID')) {
//            $idsDominio = $request->get('LOCATION_LID');
//        } else {
        $idsDominio = array_map(function($item) {
            return $item->id;
        }, $qrDominios);
//        }
        $convArray = explode('-', $convenioId);

        if (count($convArray) == 2 and in_array($convArray[0], $idsDominio)) {
            $idsDominio = $convArray[0];
                $convenioId = $convArray[1];
        }
        $dadosPaginacao = $this->getPaginate($request);


//        if ($possuiFiltro) {
            $qrProcedimentos = $this->procedimentosRepository->getProcedimentoPorDoutor($idsDominio, $idDoutor, $convenioId, null, $dadosPaginacao['page'], $dadosPaginacao['perPage'], null, true);
//        } else {
//            $qrProcedimentos = $this->procedimentosRepository->getAllProcedimentosVinculados($idsDominio, $dadosPaginacao['page'], $dadosPaginacao['perPage']);
//        }

        
//        dd($qrProcedimentos);
        if (count($qrProcedimentos) > 0) {
            $ACTIVITY = array();
            foreach ($qrProcedimentos['RESULTS'] as $chave => $row) {

//                $ACTIVITY[$chave]['ACTIVITY_LID'] = $row->doutores_id.'_'.$row->procedimentos_id.'_'.$row->proc_convenios_id;
                $ACTIVITY[$chave]['ACTIVITY_LID'] = $row->procedimentos_id;
                $ACTIVITY[$chave]['NAME'] = ($this->utf8Fix($row->nomeProcedimento));
                $ACTIVITY[$chave]['GROUP_NAME'] = (!empty($this->utf8Fix($row->proc_cat_nome))) ? ($this->utf8Fix($row->proc_cat_nome)) : "Sem categoria";
                $ACTIVITY[$chave]['GROUP_LID'] = ($row->procedimento_categoria_id == 0) ? 0 : $row->procedimento_categoria_id;
                $ACTIVITY[$chave]['DURATION'] = (empty($row->duracao) or $row->duracao == 0) ? null : $row->duracao;

                if ($possuiFiltro and !empty($convenioId)) {
                    $ACTIVITY[$chave]['PRICE'] = ($row->valor_proc == 0) ? 0 : $row->valor_proc;
                } else {
                    $ACTIVITY[$chave]['PRICE'] = 0;
                }


                $ACTIVITY[$chave]['NOTICE'] = null;
                $ACTIVITY[$chave]['PREPARATION'] = (empty($row->procedimento_descricao)) ? null : $this->utf8Fix($row->procedimento_descricao);
                $ACTIVITY[$chave]['WEB_ENABLED'] = ($row->mostra_tuotempo_api == 1) ? $row->mostra_tuotempo_api : 0; //adicionar opção na parte de procedimetnos dentro de doutores
                $ACTIVITY[$chave]['LOCATION_LID'] = $row->identificador; //caso use o Locationid
            }
//            var_dump($ACTIVITY);
//            exit;
            $qrProcedimentos['RESULTS'] = $ACTIVITY;
            return $this->returnSuccess($qrProcedimentos, null);
        } else {
            return $this->returnError(null, "No activity found");
        }
    }

}
