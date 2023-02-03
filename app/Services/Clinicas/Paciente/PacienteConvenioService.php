<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\Clinicas\Paciente;

use App\Services\BaseService;
use DateTime;
use App\Helpers\Functions;
use App\Repositories\Gerenciamento\DominioRepository;
use App\Services\Clinicas\PacienteService;
use App\Services\Clinicas\LogAtividadesService;
use App\Repositories\Clinicas\ConvenioRepository;

/**
 * Description of Activities
 *
 * @author ander
 */
class PacienteConvenioService extends BaseService {

    public function getAll($idDominio, $pacienteId, $dadosFiltro = null) {

        $ConvenioRepository = new ConvenioRepository;

        $qrConvPacientes = $ConvenioRepository->getConveniosPacientes($idDominio, $pacienteId);

        $retorno = [];
        if (count($qrConvPacientes) > 0) {

            foreach ($qrConvPacientes as $row) {


                if (!isset($convenios[$row->convenios_id])) {
                    $retorno[$row->convenios_id] = [
                        'pacientesId' => $row->pacientes_id,
                        'perfilId' => $row->identificador,
                        'validadeCarteira' => $row->validade_carteira,
                        'numeroCarteira' => $row->numero_carteira,
                        'convenio' => [
                            'id' => $row->convenios_id,
                            'nome' => $row->nomeConvenio
                        ]
                    ];
                }

                $retorno[$row->convenios_id]['doutores'][] = [
                    'id' => $row->doutores_id,
                    'nome' => $row->nomeDoutor,
                    'idConvenioPaciente' => $row->id,
                ];
            }
        }
        $retorno = array_values($retorno);
//        dd($convenios);

        return $this->returnSuccess($retorno);
    }

    public function store($idDominio, $pacienteId, $dadosInput) {

        $ConvenioRepository = new ConvenioRepository;

        $qrConvenio = $ConvenioRepository->getById($idDominio, $dadosInput['conveniosId']);

        if (!$qrConvenio and $dadosInput['conveniosId'] != 41) {
            return $this->returnError(null, 'Convênio não encontrado');
        }


        $DoutoresRepository = new DoutoresRepository;
        $qrDoutores = $DoutoresRepository->getAll($idDominio);
        foreach ($qrDoutores as $rowDout) {
            $qrVErificaExiste = $ConvenioRepository->verificaExisteConveniosPacientes($idDominio, $rowDout->id, $pacienteId, $dadosInput['conveniosId']);
            if (count($qrVErificaExiste) > 0) {
                $ConvenioRepository->updateAllConveniosPacientesByConvenioId($idDominio, $pacienteId, $dadosInput['conveniosId'], $dadosInput['numeroCarteira'], $dadosInput['validadeCarteira']);
            } else {
                $ConvenioRepository->vinculaConveniosPacientes($idDominio, $pacienteId, $dadosInput['conveniosId'], $dadosInput['numeroCarteira'], $dadosInput['validadeCarteira'], $rowDout->id);
            }
        }


//        dd($qrDoutores);
//        if (count($qrVErificaExiste) > 0) {
//            return $this->returnError(null, 'Convênio já vinculado com o paciente');
////                    $qrConveniosPacientes = $ConvenioRepository->updateAllConveniosPacientesByConvenioId($idDominio, $pacienteId, $dadosInput['conveniosId'],
////                    $dadosInput['numeroCarteira'], $dadosInput['validadeCarteira']);
//        }
//
//        $id = $ConvenioRepository->vinculaConveniosPacientes($idDominio, $pacienteId, $dadosInput['conveniosId'], $dadosInput['numeroCarteira'],
//                $dadosInput['validadeCarteira'], null, 1);
//      if ($id) {
        return $this->returnError('', 'Adicionado com sucesso');
//        } else {
//            return $this->returnError(null, 'Convênio não encontrado');
//        }
    }

    public function update($idDominio, $pacienteId, $convenioId, $dadosInput) {

        $ConvenioRepository = new ConvenioRepository;
        $qrConvenio = $ConvenioRepository->getById($idDominio, $convenioId);

        if (!$qrConvenio and $convenioId != 41) {
            return $this->returnError(null, 'Convênio não encontrado');
        }

        $qrVErificaExiste = $ConvenioRepository->verificaExisteConveniosPacientesTodos($idDominio, $pacienteId, $convenioId);
        if (count($qrVErificaExiste) == 0) {
            return $this->returnError(null, 'Convênio não vinculado a este paciente');
        }
        $id = $ConvenioRepository->updateAllConveniosPacientesByConvenioId($idDominio, $pacienteId, $convenioId, $dadosInput['numeroCarteira'],
                $dadosInput['validadeCarteira']);

        if ($id) {
            return $this->returnError('', 'Atualizado com sucesso');
        } else {
            return $this->returnError(null, 'Convênio não encontrado');
        }
    }

    public function delete($idDominio, $pacienteId, $conveniosId) {

        $ConvenioRepository = new ConvenioRepository;
        $qrConvenio = $ConvenioRepository->getById($idDominio, $conveniosId);

        if (!$qrConvenio and $conveniosId != 41) {
            return $this->returnError(null, 'Convênio não encontrado');
        }
        $qrVErificaExiste = $ConvenioRepository->verificaExisteConveniosPacientesTodos($idDominio, $pacienteId, $conveniosId);
        if (count($qrVErificaExiste) == 0) {
            return $this->returnError(null, 'Convênio não vinculado a este paciente');
        }

        $qrVErificaExiste = $ConvenioRepository->desvinculaConveniosPacientes($idDominio, $pacienteId, $conveniosId);

        return $this->returnError(null, 'Excluído com sucesso');
    }

}
