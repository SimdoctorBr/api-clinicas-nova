<?php

namespace App\Repositories\Clinicas;

use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;
use stdClass;

class FinanceiroFornecedorRepository extends BaseRepository {

    public function getFornecedorPadrao($idDominio) {
        $qr = $this->connClinicas()->select("SELECT * FROM financeiro_fornecedor WHERE conta_padrao = 1 AND identificador = '$idDominio'");

        return $qr;
    }

    public function atualizaSaldo($idDominio, $idFornecedor, $saldo) {
        $qr = $this->connClinicas()->select("UPDATE financeiro_fornecedor SET  saldo = '$saldo'  WHERE  identificador = $idDominio AND idFornecedor = '$idFornecedor'");
    }

    public function getSaldoConta($idDominio, $idFornecedor) {

        $qr = $this->connClinicas()->select("SELECT saldo FROM  financeiro_fornecedor WHERE  identificador = $idDominio AND  idFornecedor = '$idFornecedor'");
        if (count($qr) > 0) {
            return $qr[0];
        } else {
            return false;
        }
    }

}
