<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\Clinicas;

use App\Services\BaseService;
use DateTime;
use App\Repositories\Clinicas\FeriasRepository;
use App\Helpers\Functions;

/**
 * Description of Activities
 *
 * @author ander
 */
class FeriasService extends BaseService {

    private $feriasRepository;

    public function __construct(FeriasRepository $feriasRep) {
        $this->feriasRepository = $feriasRep;
    }

    
    
    }
