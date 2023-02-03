<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\Clinicas;

use App\Services\BaseService;
use DateTime;
use App\Services\Clinicas\CalculosService;
use App\Repositories\Clinicas\NotificacoesSistemaRepository;

/**
 * Description of Activities
 *
 * @author ander
 */
class NotificacoesSistemaService extends BaseService {

    private $notificacoesSistemaRepository;

    public function __construct() {
        $this->notificacoesSistemaRepository = new NotificacoesSistemaRepository;
    }

    public function store($idDominio, $tipo, $idTipo, $tipoAcao, $dadosInput) {

        $dadosInsert['identificador'] = $idDominio;
        $dadosInsert['tipo'] = $tipo;
        $dadosInsert['tipo_acoes'] = $tipoAcao;
        $dadosInsert['consulta_id'] = null;
        switch ($tipo) {
            case 1: $dadosInsert['consulta_id'] = $idTipo;
                break;
        }

        $id = $this->notificacoesSistemaRepository->insert($idDominio, $dadosInsert);

        if ($id) {
            return $this->returnSuccess('', 'Notificação cadastrada com sucesso');
        } else {
            return $this->returnError(NULL, 'Ocorreu um erro ao cadastrar a notificação');
        }
    }

    public function deleteByConsultaId($idDominio, $consultaId) {

        $qr = $this->notificacoesSistemaRepository->deleteByConsultaId($idDominio, $consultaId);
        return $this->returnSuccess('', 'Notificação excluida com sucesso');
    }

}
