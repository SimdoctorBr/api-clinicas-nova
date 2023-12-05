<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\Clinicas;

use App\Services\BaseService;
use App\Repositories\Clinicas\CodigoConfirmacaoRepository;
use App\Repositories\Gerenciamento\DominioRepository;
use App\Services\Clinicas\Utils\EmailService;
use App\Services\Clinicas\Utils\SmsService;
use App\Helpers\Functions;
use App\Services\Clinicas\Utils\IntegracaoMultiAtendimentoService;

/**
 * Description of Activities
 *
 * @author ander
 */
class CodigoConfirmacaoService extends BaseService {

    private $codigoConfirmacaoRepository;
    private $dominioRepository;
    private $id;
    private $tipo;
    private $id_tipo;
    private $codigo;
    private $identificador;
    private $email;
    private $dadosTemp;

    public function setDadosTemp($dadosTemp) {
        $this->dadosTemp = $dadosTemp;
    }

    public function setEmail($email) {
        $this->email = $email;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function setTipo($tipo) {
        $this->tipo = $tipo;
    }

    public function setId_tipo($id_tipo) {
        $this->id_tipo = $id_tipo;
    }

    public function setCodigo($codigo) {
        $this->codigo = $codigo;
    }

    public function setIdentificador($identificador) {
        $this->identificador = $identificador;
    }

    public function __construct() {
        $this->codigoConfirmacaoRepository = new CodigoConfirmacaoRepository;
    }

    public function insertUpdate() {

        $campos['tipo'] = $this->tipo;
        $campos['codigo'] = $this->codigo;
        $campos['identificador'] = $this->identificador;
        $campos['data_cad'] = date('Y-m-d H:i:s');
        if (!empty($this->email)) {
            $campos['email'] = $this->email;
        }
        if (!empty($this->id_tipo)) {
            $campos['id_tipo'] = $this->id_tipo;
        }
        if (!empty($this->dadosTemp)) {
            $campos['temp_content'] = $this->dadosTemp;
        }

        if ($this->tipo == 2) {
            $rowVerifica = $this->codigoConfirmacaoRepository->verificaRegistro($this->identificador, null, $this->tipo);
        } else {
            $rowVerifica = $this->codigoConfirmacaoRepository->verificaRegistro($this->identificador, $this->id_tipo, $this->tipo);
        }


        if ($rowVerifica) {
            $campos['status'] = 1;
            $this->codigoConfirmacaoRepository->update($this->identificador, $rowVerifica->id, $campos);
            return $rowVerifica->id;
        } else {
            return $this->codigoConfirmacaoRepository->store($this->identificador, $campos);
        }
    }

    public function verificaCodigo($idDominio, $tipo, $idTipo, $codigo) {

        $qr = $this->codigoConfirmacaoRepository->verificaCodigo($idDominio, $idTipo, $tipo, $codigo);
        if ($qr) {
            return $this->returnSuccess($qr);
        } else {
            return $this->returnError();
        }
    }

    public function verificaCodigoPorEmail($idDominio, $tipo, $email, $codigo) {

        $qr = $this->codigoConfirmacaoRepository->verificaCodigoPorEmail($idDominio, $tipo, $email, $codigo);
        if ($qr) {

            $this->codigoConfirmacaoRepository->update($idDominio, $qr->id, ['status' => 0]);
            return $this->returnSuccess($qr);
        } else {
            return $this->returnError();
        }
    }

    public function enviarCodigo($idDominio, $tipo, $idTipo, $nomeDestino, $email = null, $celular = null, $dadosTemp = null) {

        $cod = substr(mt_rand(), 0, 6);

        $CodEnviado = false;
        $envios = ['email' => false, 'sms' => false, 'whatsapp' => false];

        if (!empty($email)) {

            $EmailService = new EmailService();

            $msg = '<div style="width:50%;padding:5px;>"
                        <p style="font-weight:bold;">Olá, <b>' . $nomeDestino . '</b>!
                        </p>
                        <br>
                        Abaixo está o código de confirmação:
                        <br><br>
                        <div style=" font-weight:bold;text-align:center;backgrond:#ccc;padding:15px;border: 1px solid #ccc">
                            ' . $cod . ' 
                        </div>
                </div>';

            $envioEmail = $EmailService->enviaEmailTemplatePadrao($idDominio, $nomeDestino, $email, 'Código de confirmação', $msg);

            if ($envioEmail) {
                $CodEnviado = true;
                $envios['email'] = true;
            }
        }
        $celular = Functions::limpaTelefone($celular);

       
        if (!empty($celular) and is_numeric($celular) and strlen(($celular)) >= 11) {

            //SMS
//            $SmsService = new SmsService;
//            $rowSmsDados = $SmsService->getDados($idDominio);
//
//            if ($rowSmsDados['success']) {
//                $saldo = $rowSmsDados['data']->saldo;
//                if ($saldo > 0) {
//                    $mensagem = "Olá, $nomeDestino! O seu código de confirmação é: $cod";
//                    $envio = $SmsService->enviarSmsUnico($idDominio, $celular, $mensagem);
//                    if ($envio) {
//                     $CodEnviado = true;
//                        $envios['sms'] = true;
//                    }
//                }
//            }
            ///Multiatendiment(Whatsapp)
            $IntegracaoMultiAtendimentoService = new IntegracaoMultiAtendimentoService;
            $rowConfig = $IntegracaoMultiAtendimentoService->getConfig($idDominio);
            if ($rowConfig['success']) {
                if ($rowConfig['data']->habilitado == 1) {
                    $mensagem = "Olá, $nomeDestino!\n O seu código de confirmação é: $cod";
                    $enviado = $IntegracaoMultiAtendimentoService->enviaMsgTxt($idDominio, $celular, $mensagem, $rowConfig['data']);
                    if ($enviado) {
                        $CodEnviado = true;
                        $envios['whatsapp'] = true;
                    }
                }
            }
        }

        if ($CodEnviado) {
            $this->setIdentificador($idDominio);
            $this->setId_tipo($idTipo);
            $this->setTipo($tipo);
            $this->setCodigo($cod);
            $this->setEmail($email);
            $this->insertUpdate();
            return $this->returnSuccess($envios, 'O código de confirmação foi enviado!');
        } else {
            return $this->returnError($envios, 'Nenhum código enviado. Verifique se existe e-mail ou celular cadastrado.');
        }
    }
}
