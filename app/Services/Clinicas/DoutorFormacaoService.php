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
    private $doutores_id;
    private $tipo_formacao;
    private $instituicao_ensino;
    private $nome_formacao;
    private $periodo_de;
    private $periodo_ate;

    public function setNome_formacao($nome_formacao) {
        $this->nome_formacao = $nome_formacao;
    }

    public function setPeriodo_de($periodo_de) {
        $this->periodo_de = $periodo_de;
    }

    public function setPeriodo_ate($periodo_ate) {
        $this->periodo_ate = $periodo_ate;
    }

    public function setDoutorFormacaoRepository($doutorFormacaoRepository) {
        $this->doutorFormacaoRepository = $doutorFormacaoRepository;
    }

    public function setDoutores_id($doutores_id) {
        $this->doutores_id = $doutores_id;
    }

    public function setTipo_formacao($tipo_formacao) {
        $this->tipo_formacao = $tipo_formacao;
    }

    public function setInstituicao_ensino($instituicao_ensino) {
        $this->instituicao_ensino = $instituicao_ensino;
    }

    public function __construct() {
        $this->doutorFormacaoRepository = new DoutorFormacaoRepository;
    }

    private function fieldsResponse($row) {

//        $retorno['id'] = $row->id;
        $retorno['tipoFormacao'] = Functions::utf8ToAccentsConvert($row->tipo_formacao);
        $retorno['nomeFormacao'] = Functions::utf8ToAccentsConvert($row->nome_formacao);
        $retorno['instituicaoEnsino'] = Functions::utf8ToAccentsConvert($row->instituicao_ensino);
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

    public function insertFormacaoDoutor($idDominio, $idDoutor) {

        $campos['doutores_id'] = $idDoutor;
        $campos['identificador'] = $idDominio;
        $campos['tipo_formacao'] = $this->tipo_formacao;
        $campos['nome_formacao'] = $this->nome_formacao;
        $campos['instituicao_ensino'] = $this->instituicao_ensino;
        $campos['periodo_de'] = $this->periodo_de;
        $campos['periodo_ate'] = $this->periodo_ate;
        return $this->doutorFormacaoRepository->storeFormacaoDoutor($idDominio, $idDoutor, $campos);
    }
}
