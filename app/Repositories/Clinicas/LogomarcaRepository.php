<?php

namespace App\Repositories\Clinicas;

use Illuminate\Support\Facades\DB;
use App\Repositories\BaseRepository;

class LogomarcaRepository extends BaseRepository {

    public function getLogomarca($identificador) {

        $pegaFotos = "SELECT * FROM logomarca WHERE identificador = '$identificador'";
        $qrFotos = $this->connClinicas()->select($pegaFotos);
        return $obj;
    }

}
