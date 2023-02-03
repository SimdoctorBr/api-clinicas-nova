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
use App\Repositories\Clinicas\Paciente\PacienteFavoritosRepository;
use App\Services\Clinicas\Doutores\DoutoresService;
use App\Repositories\Gerenciamento\DominioRepository;

//use App\Repositories\Clinicas\Paciente\PacienteExameRepository;
//use App\Repositories\Clinicas\Paciente\PacienteLaudoRepository;
//use App\Repositories\Clinicas\Paciente\PacienteResultadoExameRepository;

/**
 * Description of Activities
 *
 * @author ander
 */
class PacienteFavoritosService extends BaseService {

    private $pacienteFavoritosRepository;

    public function __construct() {
        $this->pacienteFavoritosRepository = new PacienteFavoritosRepository;
    }

    public function getAll($idDominio, $idPaciente) {

        $qr = $this->pacienteFavoritosRepository->getAll($idDominio, $idPaciente);
        $DoutoresService = new DoutoresService;

        $Dominio = new DominioRepository;

        if ($qr) {
            $nomeDominio = null;
            $retorno = null;
            foreach ($qr as $row) {

                if (!isset($nomeDominio[$row->identificador])) {
                    $rowDominio = $Dominio->getById($row->identificador);
                    $nomeDominio[$row->identificador] = $rowDominio->dominio;
                }

                $retF['idFavorito'] = $row->doutores_id;
                $retF['doutorId'] = $row->doutores_id;
                $retF = array_merge( $retF,$DoutoresService->fieldsResponse($row, $nomeDominio[$row->identificador]));
                unset($retF['id']);
                $retorno[] = $retF;
            }

            return $this->returnSuccess($retorno);
        } else {
            return $this->returnError(null, 'Nenhum(a) doutor(a) favorito encontrado');
        }
    }

    public function store($idDominio, $idPaciente, $dadosInput) {


        $DoutoresService = new DoutoresService;
        $rowDoutor = $DoutoresService->getById($idDominio, $dadosInput['idDoutor']);


        if (!$rowDoutor['success']) {
            return $this->returnError(NULL, $rowDoutor['message']);
        }

        $qr = $this->pacienteFavoritosRepository->store($idDominio, $idPaciente, $dadosInput['idDoutor']);
        if ($qr) {
            return $this->returnSuccess(['id' => $qr], 'Adicionado com sucesso');
        } else {
            return $this->returnError(null, 'Erro ao adicionar o doutor favorito');
        }
    }

    public function delete($idDominio, $idPaciente, $idFavorito) {


        $verifica = $this->pacienteFavoritosRepository->verificaAdicionadoByIdFavorito($idDominio, $idPaciente, $idFavorito);


        if ($verifica) {

            $qr = $this->pacienteFavoritosRepository->delete($idDominio, $idPaciente, $idFavorito);

            return $this->returnSuccess(null, 'Excluído com sucesso');
        } else {
            return $this->returnError(null, 'Registro não encontrado');
        }
    }

}
