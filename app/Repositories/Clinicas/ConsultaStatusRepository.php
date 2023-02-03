<?php

namespace App\Repositories\Clinicas;

use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;

class ConsultaStatusRepository extends BaseRepository {

    public function alteraStatus($idDominio, $idConsulta, $dados) {
        $dados['identificador'] = $idDominio;
        $qr = $this->insertDB('consultas_status', $dados, null, 'clinicas');
        return $qr;
    }

    public function limpaStatus($idDominio, $idConsulta) {

        $qr = $this->connClinicas()->select("DELETE FROM consultas_status WHERE identificador IN($idDominio) AND consulta_id =$idConsulta LIMIT 1");
    }

    public function insereStatus($idDominio, $idConsulta, $dadosInsert) {

        $dadosInsert['identificador'] = $idDominio;
        $dadosInsert['consulta_id'] = $idConsulta;
        return $qr = $this->insertDB('consultas_status', $dadosInsert, null, 'clinicas');
    }

    public function updateStatus($idDominio, $idConsulta, $idStatus, $dadosInsert) {
        $dadosInsert['identificador'] = $idDominio;
        $dadosInsert['consulta_id'] = $idConsulta;
        return $qr = $this->updateDB('consultas_status', $dadosInsert, "  identificador = $idDominio AND id = $idStatus", null, 'clinicas');
    }

    public function getById($idDominio, $idStatus) {


        $qr = $this->connClinicas()->table('consultas_status')->where('id', $idStatus)->get()->toArray();

        if (count($qr) > 0) {

            return $qr[0];
        } else {

            return false;
        }
    }

}
