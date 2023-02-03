<?php


namespace App\Services\Clinicas;


/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Websocket
 *
 * @author ander
 */

//var_dump(scandir('../../websocket/vendor/'));
//var_dump(\Ratchet);
require '../../websocket/vendor/autoload.php';

///vai ser usada no minisite e nas apis
class WebsocketSistemaService {

    private $conn;
    private $url = "wss://ws.simdoctor.com.br/wss/sistema";

    public function sendUpdateAgenda($identificador, $tipo, $idTipo, $data) {
        $dataSend = array(
            'command' => 'updateAgenda',
            'dominioId' => base64_encode($identificador),
            'tipo' => $tipo,
            'idTipo' => $idTipo,
            'data' => $data,
            'minisite' => true
        );

//        var_Dump($dataSend;;
        $this->send($dataSend);
    }

    /**
     * 
     * @param type $identificador
     * @param type $idPaciente
     * @param type $status 1 - cadastrado, 2 - autorizado
     */
    public function sendAlteracaoQRCodePaciente($identificador, $idPaciente, $status) {
        $dataSend = array(
            'command' => 'updateQrCodePaciente',
            'dominioId' => base64_encode($identificador),
            'pacienteId' => $idPaciente,
            'status' => $status,
        );

        $this->send($dataSend);
    }

    public function sendUpdateNotaFiscal($identificador, $idNota, $status, $motivoStatus) {
        $dataSend = array(
            'command' => 'updateNotaFiscal',
            'dominioId' => base64_encode($identificador),
            'idNota' => $idNota,
            'status' => $status,
            'motivoStatus' => $motivoStatus,
        );

        $this->send($dataSend);
        return $dataSend;
    }

//    public function sendUpdateAgenda($identificador, $tipo, $idTipo, $data) {


    public function send($msgData = null) {


//        \Ratchet\Client\connect($this->url)->then(function($conn) use ($msgData) {
//            $conn->on('message', function($msg) use ($conn) {
////                var_dump(json_decode($msg));
////                echo "Received: {$msg}\n";
//                $conn->close();
//            });
//
//
//
//
//            $conn->send(json_encode($msgData));
//        }, function ($e) {
//            echo "Could not connect: {$e->getMessage()}\n";
//        });
    }

}
