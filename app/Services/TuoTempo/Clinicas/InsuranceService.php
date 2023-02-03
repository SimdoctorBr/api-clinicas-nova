<?php

namespace App\Services\TuoTempo\Clinicas;

use App\Services\BaseService;
use App\Repositories\Clinicas\ConvenioRepository;
use App\Repositories\Gerenciamento\DominioRepository;
use App\Repositories\Clinicas\ProcedimentosRepository;

class InsuranceService extends BaseService {

    private $convenioRepository;
    private $dominioRepository;
    private $procedimentosRepositoy;

    public function __construct(ConvenioRepository $conRep, DominioRepository $domRep, ProcedimentosRepository $procRep) {
        $this->convenioRepository = $conRep;
        $this->dominioRepository = $domRep;
        $this->procedimentosRepositoy = $procRep;
    }

    public function getAll($request) {



        $qrDominios = $this->dominioRepository->getAllByUser(auth()->user()->id);
        $idsDominio = array_map(function($item) {
            return $item->id;
        }, $qrDominios);


        if (count($qrDominios) == 0) {
            return $this->returnError(null, 'LOCATION not found');
        }


       





        if (!empty($request->get('ACTIVITY_LID'))) {
            $qrConvenios = $this->procedimentosRepositoy->getProcedimentoPorDoutor($idsDominio, null, null, $request->get('ACTIVITY_LID'));
            if (count($qrConvenios) == 0) {
                return $this->returnError(null, 'ACTIVITY_LID not found');
            }
        } else {
            $qrConvenios = $this->convenioRepository->getAll($idsDominio);
        }


        if (count($qrConvenios) > 0) {
            $CONVENIOS = array();
            $i = 0;
            foreach ($qrConvenios as $chave => $row) {


                if (!empty($request->get('ACTIVITY_LID'))) {
                    $nomeConvenio = $row->nomeConvenioProc;
                    $convenios_id = $row->proc_convenios_id;
                    $valor = $row->valor_proc;
                } else {
                    $nomeConvenio = $row->nomeConvenio;
                    $convenios_id = $row->convenios_id;
                    $valor = (isset($row->valor)) ? $row->valor : null;
                }

                if ($convenios_id == 41 and empty($procedimentoId)) {
                    foreach ($idsDominio as $IdDom) {
                        $CONVENIOS[$i]['INSURANCE_LID'] = $IdDom . '-' . $convenios_id;
                        $CONVENIOS[$i]['INSURANCE_NAME'] = ($nomeConvenio);
                        $CONVENIOS[$i]['ACTIVITY_PRICE'] = (isset($valor)) ? $valor : null;
                        $CONVENIOS[$i]['WEB_ENABLED'] = 1;
                        $i++;
                    }
                } else {
                    $CONVENIOS[$i]['INSURANCE_LID'] = $row->identificador . '-' . $convenios_id;
                    $CONVENIOS[$i]['INSURANCE_NAME'] = ($nomeConvenio);
                    $CONVENIOS[$i]['ACTIVITY_PRICE'] = (isset($valor)) ? $valor : null;
                    $CONVENIOS[$i]['WEB_ENABLED'] = 1;
                    $i++;
                }
            }
//             dd($CONVENIOS);
            return $this->returnSuccess($CONVENIOS);
        } else {
            return $this->returnError(null, 'No insurance registered');
        }
    }

}
