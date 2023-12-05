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
use App\Repositories\Gerenciamento\DominioRepository;

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
        foreach ($qrEmpresas as $rowEmpresa) {
            $retorno[] = [
                'id' => $rowEmpresa->identificador,
                'nome' => utf8_decode($rowEmpresa->nome),
                'email' => $rowEmpresa->email,
            ];
        }


        return $this->returnSuccess($retorno);
    }

    public function getTermosCondicoes($idominio) {
        
        $DominioRepository = new DominioRepository;
        $rowDominio = $DominioRepository->getById($idominio);
    
        
        $row = $this->empresaRepository->getById($idominio);

        $retorno = [
        'termoSimdoctor' => ['status' => true, 'url' => 'https://simdoctor.com.br/termos-e-condicoes'],
        'termoClinica' => ['status' => !empty($row[0]->termos_clinica), 'url' => env('APP_URL_CLINICAS').'perfis/'.$rowDominio->dominio.'/termos-e-condicoes-clinica'],
        ];


        return $this->returnSuccess($retorno);
    }
}
