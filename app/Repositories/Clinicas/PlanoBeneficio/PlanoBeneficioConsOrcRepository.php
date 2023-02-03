<?php

namespace App\Repositories\Clinicas\PlanoBeneficio;

use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;

class PlanoBeneficioConsOrcRepository extends BaseRepository {

    public function store($idDominio, $campos) {

        $campos['identificador'] = $idDominio;
        $qr = $this->insertDB('pl_beneficios_cons_orc', $campos, null, 'clinicas');
        return $qr;
    }

    public function update($idDominio, $id, $campos) {

        $campos['identificador'] = $idDominio;
        $qr = $this->updateDB('pl_beneficios_cons_orc', $campos, " identificador  =$idDominio and id = $id", null, 'clinicas');
        return $qr;
    }

    public function getByConsultaId($idDominio, $consultasId) {
        $qr = $this->select("SELECT * FROM pl_beneficios_cons_orc WHERE identificador =$idDominio AND tipo = 1 AND id_tipo = $consultasId");
        if (count($qr) > 0) {
            return $qr[0];
        } else {
            return false;
        }
    }

    public function getByIdTipo($idDominio, $tipo, $idTipo) {
        $qr = $this->connClinicas()->select("SELECT id FROM pl_beneficios_cons_orc WHERE tipo= $tipo AND id_tipo = $idTipo"
                . " AND identificador = '$idDominio'");
        if (count($qr) > 0) {
            return $qr[0];
        } else {
            return false;
        }
    }

}
