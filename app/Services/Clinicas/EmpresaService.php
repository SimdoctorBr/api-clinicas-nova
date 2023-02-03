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
use App\Repositories\Clinicas\EmpresaRepository;

/**
 * Description of Activities
 *
 * @author ander
 */
class EmpresaService extends BaseService {

    private $empresaRepository;

    public function __construct() {
        $this->empresaRepository = new EmpresaRepository;
    }

    public function getAll($idsDominio) {
        $qrEmpresas = $this->empresaRepository->getListByIds($idsDominio);
        $retorno = null;
        foreach($qrEmpresas as $rowEmpresa){
            $retorno[] =[
                'id' => $rowEmpresa->identificador,
                'nome' => utf8_decode($rowEmpresa->nome),
                'email' => $rowEmpresa->email,
            ];
            
        }
        
        
        return $this->returnSuccess($retorno);
    }

}
