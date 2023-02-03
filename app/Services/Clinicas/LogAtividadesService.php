<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\Clinicas;

use App\Services\BaseService;
use DateTime;
use App\Repositories\Clinicas\AdministradorRepository;
use App\Repositories\Gerenciamento\DominioRepository;
use App\Repositories\Clinicas\LogAtividadesRepository;
use App\Helpers\Functions;
use Illuminate\Support\Facades\Mail;

/**
 * Description of Activities
 *
 * @author ander
 */
class LogAtividadesService extends BaseService {

    private $logAtividadesRepository;

    public function __construct() {
        $this->logAtividadesRepository = new LogAtividadesRepository;
    }

    public function getDispositivo($origemLogin) {

        if (
                strpos($origemLogin, "okhttp/3.14.9") !== false
                or strpos($origemLogin, "iPhone") !== false
                or strpos($origemLogin, "iPad") !== false
                or strpos($origemLogin, "Android") !== false
                or strpos($origemLogin, "webOS") !== false
                or strpos($origemLogin, "BlackBerry") !== false
                or strpos($origemLogin, "iPod") !== false
                or strpos($origemLogin, "Symbian") !== false
                or strpos($origemLogin, "Windows Phone") !== false
        ) {
            return 'mobile';
        } else {
            return 'computador';
        }
    }

    /**
     * 
     * @param type $idDominio
     * @param type $tipoAcao
     * @param type $mensagem
     * @param type $idRegistro
     * @param type $tipoRegistro
     * @return type
     */
    public function store($idDominio, $tipoAcao, $mensagem, $idRegistro = null, $tipoRegistro = null, $origemLogin = null) {

        if (!auth('clinicas')->check()) {
            return false;
        }

        $campos['administradores_id'] = auth('clinicas')->user()->id;
        $campos['administrador_nome'] = auth('clinicas')->user()->nome;
        $campos['ip'] = $_SERVER['REMOTE_ADDR'];
        $campos['identificador'] = $idDominio;
        $campos['data_hora'] = date('Y-m-d H:i:s');
        $campos['tipo_acao'] = $tipoAcao;
        $campos['mensagem'] = $mensagem;
        $campos['id_registro'] = $idRegistro;
        $campos['tipo_registro_id'] = $tipoRegistro;
        if (!empty($origemLogin)) {
            $campos['origem_agent'] = $origemLogin;
            $campos['origem_dispositivo'] = $this->getDispositivo($origemLogin);
        }




        return $this->logAtividadesRepository->store($idDominio, $campos);
    }

}
