<?php

namespace App\Repositories\Clinicas;

use Illuminate\Support\Facades\DB;
use App\Repositories\BaseRepository;

class AssistenteRepository extends BaseRepository {

    public function getAll($idDominio) {

        $qr = $this->connClinicas()->select("SELECT * FROM assistentes WHERE identificador = $idDominio;");
        return $qr;
    }

}
