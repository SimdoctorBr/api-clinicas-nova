<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\TuoTempo\Clinicas;

use App\Repositories\Gerenciamento\DominioRepository;
use App\Services\BaseService;

class DominioService extends BaseService{

    private $dominioRepository;

    public function __construct(DominioRepository $emp) {
        $this->dominioRepository = $emp;
    }

    public function getAllByUser($userId) {

        $qrEmpresas = $this->dominioRepository->getAllByUser($userId);
    
    }

}
