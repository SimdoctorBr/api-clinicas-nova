<?php

namespace App\Repositories\Clinicas;

use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;

class PagSeguroConfigRepository extends BaseRepository {

    public function getDados($idDominio) {

        $qr = $this->connClinicas()->select("SELECT * FROM pagseguro_config WHERE identificador = '$idDominio'");
        if (count($qr) > 0) {
            return $qr[0];
        } else {
            return false;
        }
    }

}
