<?php

namespace App\Repositories\Clinicas;

use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;
use stdClass;

class DefinicaoMarcacaoGlobalRepository extends BaseRepository {

    public function getDadosDefinicao($idDominio, $campos = []) {
        if (is_array($idDominio)) {
            $sql = 'identificador IN(' . implode(',', $idDominio) . ")";
        } else {
            $sql = "identificador = $idDominio";
        }

        $camposSql = '*';
        if (count($campos) > 0) {
            $camposSql = implode(',', $campos);
        }

        $qr = $this->connClinicas()->select("SELECT $camposSql FROM definicoes_marcacao_consulta_global
               WHERE  $sql
               ");
        return $qr[0];
    }

}
