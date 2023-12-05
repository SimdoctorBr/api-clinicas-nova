<?php

namespace App\Repositories\Clinicas\Administracao;

use Illuminate\Support\Facades\DB;
use App\Repositories\BaseRepository;

class ModeloAutorizacaoRepository extends BaseRepository {

    public function store($idDominio, $campos) {
        $campos['identificador'] = $idDominio;
        $qr = $this->insertDB('modelos_documentos', $campos, null, 'clinicas');
    }

    public function update($idDominio, $id, $campos) {
        $campos['identificador'] = $idDominio;
        $qr = $this->updateDB('modelos_documentos', $campos, " identificador = $idDominio and id = $id LIMIT 1", null, 'clinicas');
    }

    public function getAll($idDominio) {

        $qr = $this->connClinicas()->select("SELECT A.* FROM  modelos_documentos as A 
            WHERE A.identificador = '$idDominio'");

        return (count($qr) > 0) ? $qr : false;
    }

    public function getById($idDominio, $id) {
        $qr = $this->connClinicas()->select("SELECT * FROM  modelos_documentos WHEREidentificador = $idDominio AND id = '$id'");
        return (count($qr) > 0) ? $qr[0] : false;
    }

    public function getModelosPacientes($idDominio, $idDoc = null) {
        $sql = '';
        if (!empty($idDoc)) {
            $sql = "AND id = $idDoc";
        }
        $qr = $this->connClinicas()->select("SELECT * FROM  modelos_documentos WHERE identificador = '$idDominio'  AND autorizacao_paciente = 1  $sql");

        if (!empty($idDoc)) {
            return (count($qr) > 0) ? $qr[0] : false;
        } else {
            return (count($qr) > 0) ? $qr : false;
        }
    }
}
