<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\Clinicas\Administracao;

use App\Services\BaseService;
use DateTime;
use App\Helpers\Functions;
use App\Repositories\Clinicas\Administracao\ModeloAutorizacaoRepository;

/**
 * Description of Activities
 *
 * @author ander
 */
class ModeloAutorizacoesService extends BaseService {

    private $ModeloAutorizacaoRepository;

    public function __construct() {
        $this->ModeloAutorizacaoRepository = new ModeloAutorizacaoRepository;
    }

    public function getAll($idDominio) {

        $qr = $this->ModeloAutorizacaoRepository->getAll($idDominio);
        if ($qr) {
            return $this->returnSuccess($qr);
        } else {
            return $this->returnError(null, 'Sem registros cadastrados');
        }
    }

    public function getById($idDominio, $id) {
        $qr = $this->ModeloAutorizacaoRepository->getById($idDominio, $id);
        if ($qr) {
            return $this->returnSuccess($qr);
        } else {
            return $this->returnError(null, 'Registro não encontrado');
        }
    }

    public function getModelosPacientes($identificador, $idDoc = null) {

        $qr = $this->ModeloAutorizacaoRepository->getModelosPacientes($idDominio, $idDoc);
        if ($qr) {
            return $this->returnSuccess($qr);
        } else {
            return $this->returnError(null, 'Sem registros cadastrados');
        }
    }
}
