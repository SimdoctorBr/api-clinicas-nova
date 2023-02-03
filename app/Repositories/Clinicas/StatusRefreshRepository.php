<?php

namespace App\Repositories\Clinicas;

use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;

class StatusRefreshRepository extends BaseRepository {

    public function insertAgenda($idDominio, $doutorId) {

        $campos['doutores_id'] = $doutorId;
        $campos['agenda'] = 1;
        $campos['agenda_time'] = time();
        $campos['identificador'] = $idDominio;

        $qrVerifica = $this->connClinicas()->select("SELECT id FROM status_refresh WHERE identificador = '$idDominio' AND agenda = 1 AND doutores_id = '$doutorId'");

        if (count($qrVerifica) == 0) {
            return $this->insertDB('status_refresh', $campos, null, 'clinicas');
        } else {
            $row = $qrVerifica[0];

            $this->updateDB('status_refresh', $campos, "id = '$row->id' LIMIT 1", null, 'clinicas');
        }
    }

    public function verificaStatus($idDominio, $doutorId) {

        $time = time();

        $retorno['success'] = false;
        $qrVerifica = $this->connClinicas()->select("SELECT * FROM  status_refresh WHERE identificador  = $idDominio AND doutores_id = '$doutorId'
                                                AND DATE_SUB(NOW(),INTERVAL 30 SECOND) < FROM_UNIXTIME(agenda_time)");

        if (count($qrVerifica) > 0) {
            $row = $qrVerifica[0];
            $retorno['token'] = md5($doutorId . $row->agenda_time);
            $retorno['success'] = true;
        }

        return $retorno;
    }

    public function verificaStatusTodos($idDominio) {

        $time = time();

        $retorno['success'] = false;
        $qrVerifica = $this->connClinicas()->select("SELECT * FROM  status_refresh WHERE identificador  = $idDominio 
                                                AND DATE_SUB(NOW(),INTERVAL 30 SECOND) < FROM_UNIXTIME(agenda_time)");

        if (count($qrVerifica) > 0) {
            $row = $qrVerifica[0];
            $retorno['token'] = md5($idDominio . $row->agenda_time);
            $retorno['success'] = true;
        }

        return $retorno;
    }

}
