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
use App\Repositories\Clinicas\DoutorFormacaoRepository;

/**
 * Description of Activities
 *
 * @author ander
 */
class DoutorFormacaoService extends BaseService {

    private $doutorFormacaoRepository;

    public function __construct() {
        $this->doutorFormacaoRepository = new DoutorFormacaoRepository;
    }

    private function fieldsResponse($row) {

//        $retorno['id'] = $row->id;
        $retorno['tipoFormacao'] = utf8_decode($row->tipo_formacao);
        $retorno['nomeFormacao'] = utf8_decode($row->nome_formacao);
        $retorno['instituicaoEnsino'] = utf8_decode($row->instituicao_ensino);
        $retorno['periodoDe'] = $row->periodo_de;
        $retorno['periodoAte'] = $row->periodo_ate;
        return $retorno;
    }

    public function getAll($idDominio, $dadosFiltro = null) {



        $qr = $this->doutorFormacaoRepository->getAll($idDominio, $dadosFiltro);
//dd($qr);
        if ($qr) {
            $retorno = null;
            $idAnt = null;
            $i = -1;
            foreach ($qr as $row) {

                if ($idAnt != $row->id) {
                    $i++;
                }
//                $retorno[$i]['id'] = $row->id;
//                $retorno[$i]['tipoFormacao'] = utf8_decode($row->tipo_formacao);
                $retorno[$i]['nomeFormacao'] = utf8_decode($row->nome_formacao);

//                $retorno[$i]['instituicaoEnsino'] = utf8_decode($row->instituicao_ensino);
//                $retorno[$i]['periodoDe'] = utf8_decode($row->periodo_de);
//                $retorno[$i]['periodoAte'] = utf8_decode($row->periodo_ate);
//
                if (isset($dadosFiltro['withDoctors']) and $dadosFiltro['withDoctors'] == true) {
                    $retorno[$i]['perfilId'] = ($row->identificador);
                    $qrGrupoDoutor = $this->doutorFormacaoRepository->getDoutoresPorNomeFormacao($idDominio, $row->nome_formacao);
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


        $qr = $this->doutorFormacaoRepository->getByDoutorId($idDominio, $idDoutor);

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

}
