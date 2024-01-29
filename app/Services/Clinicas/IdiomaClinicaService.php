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
use App\Repositories\Clinicas\IdiomaClinicaRepository;

/**
 * Description of Activities
 *
 * @author ander
 */
class IdiomaClinicaService extends BaseService {

    private $idiomaClinicaRepository;

    public function __construct() {
        $this->idiomaClinicaRepository = new IdiomaClinicaRepository;
    }

    private function fieldsResponse($row) {

        $retorno['id'] = $row->idiomas_id;
        $retorno['nome'] = utf8_decode($row->nome);
        return $retorno;
    }

    public function getAll($idDominio, $dadosFiltro = null) {


        $qr = $this->idiomaClinicaRepository->getAll($idDominio, $dadosFiltro);

        if ($qr) {
            $retorno = null;
            $idAnt = null;
            $i = -1;
            foreach ($qr as $row) {

                if ($idAnt != $row->id) {
                    $i++;
                }
                $retorno[$i]['id'] = $row->id;
                $retorno[$i]['nome'] = utf8_decode($row->nome);
//
                if (isset($dadosFiltro['withDoctors']) and $dadosFiltro['withDoctors'] == true) {
                    $qrGrupoDoutor = $this->idiomaClinicaRepository->getDoutoresPorIdiomaId($idDominio, $row->id);
                    foreach ($qrGrupoDoutor as $rowDout) {
                        $retorno[$i]['doutores'][] = ['id' => $rowDout->doutores_id,
                            'nome' => $rowDout->nomeDoutor,
                            'perfilId' => $rowDout->identificador
                        ];
                    }
                }

                $idAnt = $row->id;
            }


            return $this->returnSuccess($retorno);
        } else {
            return $this->returnError(null, 'Nenhuma especialidade encontrada');
        }
    }

    public function getByDoutorId($idDominio, $idDoutor) {


        $qr = $this->idiomaClinicaRepository->getByDoutorId($idDominio, $idDoutor);

        if ($qr) {
            $retorno = null;
            foreach ($qr as $row) {
                $retorno[] = $this->fieldsResponse($row);
            }


            return $this->returnSuccess($retorno);
        } else {
            return $this->returnError(null, 'Nenhum idioma encontrado');
        }
    }

 
    public function insertIdiomasDoutor($idDominio, $idDoutor, $idGrupo) {



        $qrVerifica = $this->idiomaClinicaRepository->verificaIdiomaDoutor($idDominio, $idDoutor, $idGrupo);
        if ($qrVerifica) {
            $row = $qrVerifica;
            $this->idiomaClinicaRepository->updateIdiomaDoutorByIdDoutoresIdioma($idDominio, $row->id, ['status' => 1]);
            return $row->id;
        } else {
            $campos['doutores_id'] = $idDoutor;
            $campos['idiomas_id'] = $idGrupo;
            $campos['identificador'] = $idDominio;
            return $this->idiomaClinicaRepository->storeIdiomaDoutor($idDominio, $idDoutor, $campos);
        }
    }

}
