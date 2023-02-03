<?php

namespace App\Repositories\Clinicas;

use Illuminate\Support\Facades\DB;
use App\Repositories\BaseRepository;

class SalaRepository extends BaseRepository {

    public function getAll($idDominio) {
        $qr = $this->connClinicas()->select("SELECT * FROM salas WHERE identificador = $idDominio;");
        return $qr;
    }

}
