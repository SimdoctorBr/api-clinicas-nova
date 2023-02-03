<?php

namespace App\Repositories\Clinicas\Financeiro\Fornecedores;

use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;

class FornecedorRepository extends BaseRepository {

    public function getFornecedorPadrao($idDominio) {
        $qr = $this->connClinicas()->select("SELECT * FROM financeiro_fornecedor WHERE conta_padrao = 1 AND identificador = '$idDominio'");
        if (count($qr) > 0) {
            return $qr[0];
        } else {
            return false;
        }
    }


}
