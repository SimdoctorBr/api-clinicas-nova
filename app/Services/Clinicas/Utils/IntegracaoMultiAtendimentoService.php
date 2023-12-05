<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\Clinicas\Utils;

use App\Services\BaseService;
use App\Repositories\Clinicas\IntegracaoMultiatendimentoRepository;
use App\Services\MultiatendimentoAPI\MultiatendimentoAPI;

/**
 * Description of Activities
 *
 * @author ander
 */
class IntegracaoMultiAtendimentoService extends BaseService {

    private $IntegracaoMultiatendimentoRepository;

    public function __construct() {
        $this->IntegracaoMultiatendimentoRepository = new IntegracaoMultiatendimentoRepository;
    }

    public function getConfig($idDominio) {

        $qr = $this->IntegracaoMultiatendimentoRepository->getConfig($idDominio);
        if ($qr) {
            return $this->returnSuccess($qr);
        } else {
            return $this->returnError();
        }
    }

    public function enviaMsgTxt($idDominio, $telefone, $msg, $rowConfig = null) {

        if (empty($rowConfig)) {
            $rowConfig = $this->IntegracaoMultiatendimentoRepository->getConfig($idDominio);
        }

        if ($rowConfig and $rowConfig->habilitado) {
            $MultiatendimentoAPI = new MultiatendimentoAPI;
            $MultiatendimentoAPI->setToken($rowConfig->token);
            $envia = $MultiatendimentoAPI->sendMsgTxt($telefone, $msg);
         
            if (!isset($envia->error)) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}
