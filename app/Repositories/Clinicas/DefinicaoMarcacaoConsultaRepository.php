<?php

namespace App\Repositories\Clinicas;

use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;
use stdClass;

class DefinicaoMarcacaoConsultaRepository extends BaseRepository {

    public function getByDoutoresId($idDominio, $doutoresId) {
     
        $qr = $this->connClinicas()->select("SELECT * FROM definicoes_marcacao_consulta WHERE identificador = $idDominio AND  doutores_id =  '$doutoresId'");

        if (count($qr) > 0) {
            return $qr[0];
        } else {
            return false;
        }
    }

}
