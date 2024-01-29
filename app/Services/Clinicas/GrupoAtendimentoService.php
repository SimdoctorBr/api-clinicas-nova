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
use App\Repositories\Clinicas\GrupoAtendimentoRepository;

/**
 * Description of Activities
 *
 * @author ander
 */
class GrupoAtendimentoService extends BaseService {

    private $grupoAtendimento;

    public function __construct() {
        $this->grupoAtendimento = new GrupoAtendimentoRepository;
    }

    private function fieldsResponse($row) {

        $retorno['id'] = $row->grupo_atendimento_id;
        $retorno['nome'] = utf8_decode($row->nome);
        return $retorno;
    }

    public function getAll($idDominio, $dadosFiltro = null) {


        $qr = $this->grupoAtendimento->getAll($idDominio, $dadosFiltro);

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

                    $qrGrupoDoutor = $this->grupoAtendimento->getDoutoresPorGrupoAtendimentoId($idDominio, $row->id);
                    foreach ($qrGrupoDoutor as $rowDout) {
                        $retorno[$i]['doutores'][] = ['id' => $rowDout->doutores_id,
                            'nome' => $rowDout->nomeDoutor,
                            'perfilId' => $rowDout->identificador,
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


        $qr = $this->grupoAtendimento->getByDoutorId($idDominio, $idDoutor);
        if ($qr) {
            $retorno = null;
            foreach ($qr as $row) {
                $retorno[] = $this->fieldsResponse($row);
            }

            return $this->returnSuccess($retorno);
        } else {
            return $this->returnError(null, 'Nenhuma especialidade encontrada');
        }
    }

    public function insertGrupoDoutor($idDominio, $idDoutor, $idGrupo) {



        $qrVerifica = $this->grupoAtendimento->verificaGrupoDoutor($idDominio, $idDoutor, $idGrupo);
        if ($qrVerifica) {
            $row = $qrVerifica;
            $this->grupoAtendimento->updateGrupoDoutorByIdDoutoresGrupo($idDominio, $row->id, ['status' => 1]);
            return $row->id;
        } else {
            $campos['doutores_id'] = $idDoutor;
            $campos['grupo_atendimento_id'] = $idGrupo;
            $campos['identificador'] = $idDominio;
            return $this->grupoAtendimento->storeGrupoDoutor($idDominio, $idDoutor, $campos);
        }
    }
}
