<?php

namespace App\Repositories\Clinicas;

use Illuminate\Support\Facades\DB;
use App\Repositories\BaseRepository;
use App\Helpers\Functions;

class PlanoBeneficioRepository extends BaseRepository {

    public function getAll($idDominio, $filtro = null) {
        $qr = $this->connClinicas()->select("SELECT * FROM  planos_beneficios WHERE identificador = '$idDominio' AND status = 1 ");
        return $qr;
    }

    public function store($idDominio, $dados) {
        $dados['identificador'] = $idDominio;
        $qr = $this->insertDB("planos_beneficios", $dados, null, 'clinicas');
        return $qr;
    }

    public function update($idDominio, $id, $dados) {
        $qr = $this->updateDB("planos_beneficios", $dados, "identificador = $idDominio AND id = $id", null, 'clinicas');
        return $qr;
    }

    public function delete($idDominio, $id) {

        $qr = $this->updateDB("planos_beneficios", ['status' => 0], "identificador = $idDominio AND id = $id", null, 'clinicas');
    }

    public function getById($idDominio, $idPlano) {
        $qr = $this->connClinicas()->select("SELECT * FROM  planos_beneficios WHERE identificador = '$idDominio' AND id = $idPlano");

        if (count($qr) > 0) {
            return $qr[0];
        } else {
            return false;
        }
    }

}
