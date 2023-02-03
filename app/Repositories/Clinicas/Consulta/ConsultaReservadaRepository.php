<?php

namespace App\Repositories\Clinicas\Consulta;

use Illuminate\Support\Facades\DB;
use App\Repositories\BaseRepository;
use App\Helpers\Functions;

class ConsultaReservadaRepository extends BaseRepository {

   

    public function store($idDominio, $dadosInsert) {
        return $qr = $this->insertDB('consultas_reservadas', $dadosInsert, null, 'clinicas');
    }

    public function update($idDominio, $id, $dadosInsert) {
        return $qr = $this->updateDB('consultas_reservadas', $dadosInsert, "identificador = $idDominio AND id = $id LIMIT 1", $dadosCript, 'clinicas');
    }


    public function getByConsultaId($idDominio, $consultaid) {

        return $qr = $this->connClinicas()->select("SELECT * FROM consultas_reservadas WHERE identificador = $idDominio AND consultas_id = $consultaid ");
    }

    public function deleteByConsultaId($idDominio, $consultaid) {

        return $qr = $this->connClinicas()->select("SELECT * FROM consultas_reservadas WHERE identificador = $idDominio AND consultas_id = $consultaid ");
    }


}
