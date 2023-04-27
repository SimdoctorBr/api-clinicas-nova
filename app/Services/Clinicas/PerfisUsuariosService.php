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
use App\Repositories\Clinicas\PerfisUsuariosRepository;

/**
 * Description of Activities
 *
 * @author ander
 */
class PerfisUsuariosService extends BaseService {

    public function getAll($idDominio) {
        $PerfisUsuariosRepository = new PerfisUsuariosRepository;
        
        $retorno = null;
        $qr = $PerfisUsuariosRepository->getAll($idDominio);
        if ($qr) {

            foreach ($qr as $chave =>$row) {

                $retorno[$chave] = $row;
                $retorno[$chave]->nome = utf8_decode($row->nome);
            }
        }
        return $this->returnSuccess($retorno);
    }

}
