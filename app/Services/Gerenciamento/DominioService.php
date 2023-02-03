<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\Gerenciamento;

use App\Services\BaseService;
use DateTime;
use App\Services\Clinicas\CalculosService;
use App\Repositories\Gerenciamento\DominioRepository;

/**
 * Description of Activities
 *
 * @author ander
 */
class DominioService extends BaseService {

    public function getById($idDominio) {

        $DominioRepository = new DominioRepository;
        $qr = $DominioRepository->getById($idDominio);

        if ($qr) {
            return $this->returnSuccess($qr);
        } else {
            return $this->returnError(null, 'Dominio não encontrado');
        }
    }

    public function getDominiosByUserApiInterno($userApiInternoId) {

        $DominioRepository = new DominioRepository;
        $qr = $DominioRepository->getDominiosByUserApiInterno($userApiInternoId);
        if (count($qr) > 0) {
            $retorno = null;
            foreach ($qr as $row) {
                $retorno[] = $row->dominio_id;
            }
            return $retorno;
        } else {
            return false;
        }
    }

}
