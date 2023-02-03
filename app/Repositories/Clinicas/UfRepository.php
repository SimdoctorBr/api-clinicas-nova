<?php

namespace App\Repositories\Clinicas;

use Illuminate\Support\Facades\DB;
use App\Repositories\BaseRepository;

class UfRepository extends BaseRepository {

    public function getBySigla($sigla) {
        $qr = $this->connClinicas()->select("SELECT * FROM uf WHERE ds_uf_sigla = '$sigla';");
        return $qr[0];
    }

}
