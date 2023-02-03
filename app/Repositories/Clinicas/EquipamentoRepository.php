<?php

namespace App\Repositories\Clinicas;

use Illuminate\Support\Facades\DB;
use App\Repositories\BaseRepository;

class EquipamentoRepository extends BaseRepository {

    public function getAll($idDominio) {

        $qr = $this->connClinicas()->select("SELECT * FROM equipamentos WHERE identificador = $idDominio;");
        return $qr;
    }

}
