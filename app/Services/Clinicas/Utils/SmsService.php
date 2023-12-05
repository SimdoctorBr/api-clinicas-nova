<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\Clinicas\Utils;

use App\Services\BaseService;
use App\Services\ZIAD_SMS\ZIAD;
use App\Services\ZIAD_SMS\ZIAD_Contact;
use App\Repositories\Clinicas\SmsRepository;
use App\Services\Clinicas\Utils\SmsService;

/**
 * Description of Activities
 *
 * @author ander
 */
class SmsService extends BaseService {

    private $SmsRepository;

    public function __construct() {
        $this->SmsRepository = new SmsRepository;
    }

    public function insertHistoricoEnvio($idDominio, $numero, $msg, $pacientes_id = null) {

        $campos['identificador'] = $idDominio;
        $campos['pacientes_id'] = $pacientes_id;
        $campos['data_cad'] = date('Y-m-d H:i:s');
        $campos['msg'] = $msg;
        $campos['celular'] = $numero;

        return $this->SmsRepository->storeHistoricoEnvio($idDominio, $campos);
    }

    public function enviarSmsUnico($idDominio, $numero, $mensagem, $idPaciente = null) {
        $numero = str_replace('(', '', str_replace(')', '', str_replace(' ', '', str_replace('-', '', $numero))));

        if (strlen($numero) == 11) {

            $ZIAD = new ZIAD();
            $Contact = new ZIAD_Contact();
            $Contact->setMessage($mensagem);
            $Contact->setPhone($numero);
            $send = $ZIAD->envioUnico($Contact);
       
            if (isset($send->errors)) {
                return false;
            } else {

                $this->insertHistoricoEnvio($idDominio, $numero, $mensagem, $idPaciente);
                $this->diminuirSaldo($idDominio, 1);
                return true;
            }
        }
    }

    public function getDados($idDominio) {
        $qr = $this->SmsRepository->getDados($idDominio);
        if ($qr) {
            return $this->returnSuccess($qr);
        } else {
            return $this->returnError();
        }
    }

    public function aumentarSaldo($idDominio, $qnt) {
        $row = $this->SmsRepository->getDados($idDominio);
        $saldo = $row->saldo;
        $saldo = $saldo + $qnt;
        $campos['saldo'] = $saldo;
        $campos['data_alter'] = date('Y-m-d H:i:s');
        $this->SmsRepository->update($idDominio, $campos);
    }

    public function diminuirSaldo($idDominio, $qnt) {
        $row = $this->SmsRepository->getDados($idDominio);
        $saldo = $row->saldo;
        $saldo = $saldo - $qnt;
        $campos['saldo'] = $saldo;
        $campos['data_alter'] = date('Y-m-d H:i:s');
        $this->SmsRepository->update($idDominio, $campos);
    }
}
