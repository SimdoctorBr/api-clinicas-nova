<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\Clinicas;

use App\Services\BaseService;
use DateTime;
use App\Services\Clinicas\CalculosService;
use App\Helpers\Functions;
use App\Repositories\Clinicas\ConvenioRepository;
use Illuminate\Support\Facades\Cache;

/**
 * Description of Activities
 *
 * @author ander
 */
class ConvenioService extends BaseService {

    public function __construct() {
        
    }

    private function responseFields($rowConsulta, $nomeDominio, $showProcedimentos = false, $showProntuario = false) {

        return $retorno;
    }

    public function getAll($idDominio, $dadosFiltro = null) {

//        $value = Cache::get('key');
        $keyCache = $idDominio . request()->server('REQUEST_URI');

       
        
        if (Cache::has($keyCache)) {
            $retorno = Cache::get($keyCache);
        } else {
            $ConvenioRepository = new ConvenioRepository;

            $qr = $ConvenioRepository->getAll($idDominio);
            $retorno = [];
            if (count($qr) > 0) {
                foreach ($qr as $row) {
                    $retorno[] = [
                        'id' => $row->id,
                        'nome' => $row->nome,
                        'registroANS' => $row->registro_ans,
                        'perfilId' => $row->identificador,
                        'cnpj' => $row->cnpj_operadora,
                        'cnes' => $row->cnes,
                        'imposto' => $row->imposto,
                    ];
                }
            }
            Cache::add($keyCache, $retorno, 120);
        }
        return $this->returnSuccess($retorno);
    }

}
