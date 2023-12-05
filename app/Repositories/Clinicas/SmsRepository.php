<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Repositories\Clinicas;

use App\Repositories\BaseRepository;

/**
 * Description of ConvenioRepository
 *
 * @author ander
 */
class SmsRepository extends BaseRepository {

    public function getPrecos() {

        $qr = $this->connGerenciamento()->select("SELECT * FROM sms_precos");
        if (count($qr) > 0) {
            return $qr[0];
        } else {
            return false;
        }
    }

    public function getPrecosById($idPreco) {

        $qr = $this->connGerenciamento()->select("SELECT * FROM sms_precos WHERE id = $idPreco");
        if (count($qr) > 0) {
            return $qr[0];
        } else {
            return false;
        }
    }

    /**
     * 
     * @param type $identificador
     * @return type
     */
    public function getDados($idDominio) {


        $qr = $this->connGerenciamento()->select("SELECT A.*, DATE_ADD(A.data_alter,INTERVAL 5 MINUTE) AS ultimaAlteracao , B.chave_api_ziad
                                                    FROM sms_saldo AS A
                                                   INNER JOIN dominios AS B
                                                   ON A.dominios_id = B.id
                                                   WHERE A.dominios_id = $idDominio  ");

        if (count($qr) > 0) {
            return $qr[0];
        } else {
            return false;
        }
    }

    public function storeHistoricoEnvio($idDominio, $campos) {
        $campos['identificador'] = $idDominio;
        $qr = $this->insertDB('sms_envio_historico', $campos,  ['msg', 'numero'], 'gerenciamento');
        return $qr;
    }
    public function update($idDominio, $campos) {
        $qr = $this->updateDB('sms_saldo', $campos, " dominios_id = $idDominio  LIMIT 1", null, 'gerenciamento');
        return $qr;
    }
}
