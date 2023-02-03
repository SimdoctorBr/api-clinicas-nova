<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\Clinicas;

use App\Services\BaseService;
use App\Repositories\Clinicas\ProcedimentosRepository;
use App\Repositories\Gerenciamento\DominioRepository;

/**
 * Description of Activities
 *
 * @author ander
 */
class CalculosService extends BaseService {

    private $procedimentosRepository;
    private $dominioRepository;

    public function __construct(ProcedimentosRepository $procRep, DominioRepository $domRep) {
        $this->procedimentosRepository = $procRep;
        $this->dominioRepository = $domRep;
    }

    public static function calcularDescontoPercentual($valorProc, $valorRepasse) {

        $retorno = ($valorProc * ($valorRepasse / 100));


        return $retorno;
    }

    /*
     * 
     * @param type $valorProc
     * @param type $tipoRepasse - 1- percentual, 2 - valor real
     * @param type $valorRepasse
     * @return type
     */

    public static function calcularRepasse($valorProc, $tipoRepasse, $valorRepasse) {

        if ($tipoRepasse == 1) {
            $retorno = ($valorProc * ($valorRepasse / 100));
        } else if ($tipoRepasse == 2) {
            $retorno = ($valorProc - $valorRepasse);
        }


        return $retorno;
    }

}
