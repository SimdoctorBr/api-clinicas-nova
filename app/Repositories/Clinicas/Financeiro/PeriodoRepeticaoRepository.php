<?php

namespace App\Repositories\Clinicas\Financeiro;

use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;

class PeriodoRepeticaoRepository extends BaseRepository {

    public function getById($idPeriodoRepeticao) {
        $qr = $this->connClinicas()->select("SELECT * FROM financeiro_periodo_repeticao WHERE id = '$idPeriodoRepeticao'");
        return $qr[0];
    }

}
