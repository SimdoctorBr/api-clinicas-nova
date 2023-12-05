<?php

namespace App\Repositories\Clinicas\Configuracoes;

use Illuminate\Support\Facades\DB;
use App\Repositories\BaseRepository;
use App\Helpers\Functions;

class DocumentosExigidosRepository extends BaseRepository {

    public function store($idDominio, Array $dadosInsert) {

        return $this->insertDB('documentos_exigidos', $dadosInsert, null, 'clinicas');
    }

    public function update($idDominio, $idDocEx, Array $dadosInsert) {

        return $this->updateDB('documentos_exigidos', $dadosInsert, " identificador = $idDominio AND id = $idDocEx LIMIT 1", null, 'clinicas');
    }

    public function getAllExibeCadastro($idDominio, $tipoDoc=null) {

        $sql = '';
        if (!empty($tipoDoc)) {
            $sql = " AND tipo =$tipoDoc";
        }
        $qr = $this->connClinicas()->select("SELECT * FROM  documentos_exigidos WHERE identificador = '$idDominio' AND status = 1 AND exibe_cadastro=1 $sql");
        return $qr;
    }

}
