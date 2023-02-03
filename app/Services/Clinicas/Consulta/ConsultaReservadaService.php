<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\Clinicas\Consulta;

use App\Services\BaseService;
use DateTime;
use App\Repositories\Clinicas\Consulta\ConsultaReservadaRepository;
use App\Helpers\Functions;

/**
 * Description of Activities
 *
 * @author ander
 */
class ConsultaReservadaService extends BaseService {

    public function store($idDominio, $dadosInsert) {
        $ConsultaReservadaRepository = new ConsultaReservadaRepository;
        return $ConsultaReservadaRepository->store($idDominio, $dadosInsert);
    }

    public function excluir($idDominio, $consultaId) {
        $ConsultaReservadaRepository = new ConsultaReservadaRepository;
        $qr = $ConsultaReservadaRepository->delete($idDominio, $consultaId);

        if ($qr) {
            return $this->returnSuccess('', 'Excluído com sucesso.');
        } else {
            return $this->returnError(null, 'Ocorreu um erro ao excluir o procedimento');
        }
    }

}
