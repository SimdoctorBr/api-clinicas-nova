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
use App\Repositories\Clinicas\EspecialidadeRepository;

/**
 * Description of Activities
 *
 * @author ander
 */
class EspecialidadeService extends BaseService {

    private $especialidadeRepository;

    public function __construct() {
        $this->especialidadeRepository = new EspecialidadeRepository;
    }

    private function fieldsResponse($row) {

//        $retorno['id'] = $row->id;
        $retorno['nome'] = html_entity_decode(utf8_decode($row->nome));
        return $retorno;
    }

    public function getAll($idDominio, $dadosFiltro = null) {



        $qr = $this->especialidadeRepository->getAll($idDominio, $dadosFiltro);
//dd($qr);
        if ($qr) {
            $retorno = null;
            $idEspecialidadeAnt = null;
            $i = -1;
            foreach ($qr as $row) {

                if ($idEspecialidadeAnt != $row->nome) {
                    $i++;
                }
                $retorno[$i]['nome'] = html_entity_decode(utf8_decode($row->nome));

                if (isset($dadosFiltro['exibeListaDoutores']) and $dadosFiltro['exibeListaDoutores'] == true) {

                    $retorno[$i]['perfilId'] = $row->identificador;
                    $retorno[$i]['doutores'][] = ['id' => $row->doutores_id,
                        'nome' => $row->nomeDoutor,
                        'sexo' => $row->sexo,
                    ];
                }
                $idEspecialidadeAnt = $row->nome;
            }


            return $this->returnSuccess($retorno);
        } else {
            return $this->returnError(null, 'Nenhuma especialidade encontrada');
        }
    }

    public function getByDoutorId($idDominio, $idDoutor) {


        $qr = $this->especialidadeRepository->getByDoutorId($idDominio, $idDoutor);

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

}
