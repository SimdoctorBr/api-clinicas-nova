<?php

namespace App\Repositories\Clinicas\Administracao;

use Illuminate\Support\Facades\DB;
use App\Repositories\BaseRepository;

class LgpdAutorizacoesRepository extends BaseRepository {

    public function store($idDominio, $campos) {
        $campos['identificador'] = $idDominio;
        $qr = $this->insertDB('lgpd_autorizacoes', $campos, null, 'clinicas');
    }

    public function update($idDominio, $id, $campos) {
        $campos['identificador'] = $idDominio;
        $qr = $this->updateDB('lgpd_autorizacoes', $campos, " identificador = $idDominio and id = $id LIMIT 1", null, 'clinicas');
    }

    public function verificaDocAutorizacaoLancado($idDominio, $pacientesId, $modeloDocId = null) {
        $sql = '';
        $sqlModelo = '';
        if (!empty($modeloDocId)) {
            $sqlModelo = "AND modelos_documentos_id = $modeloDocId";
        }

        $qr = $this->connClinicas()->select("SELECT * FROM lgpd_autorizacoes WHERE identificador = $idDominio $sqlModelo AND pacientes_id = $pacientesId");

        if (!empty($modeloDocId)) {
            return (count($qr) > 0) ? $qr[0] : false;
        } else {
            return (count($qr) > 0) ? $qr : false;
        }
    }

    public function verificaTermoCondicoes($idDominio, $tipoTermo, $pacientesId) {
        $sqlModelo = '';
        if ($tipoTermo == 1) {
            $sqlTermo = " termos_simdoctor = 1";
        } else if ($tipoTermo == 2) {
            $sqlTermo = " termos_clinica = 1";
        }
        $qr = $this->connClinicas()->select("SELECT * FROM lgpd_autorizacoes WHERE identificador = $idDominio AND $sqlTermo AND pacientes_id = $pacientesId");
        return (count($qr) > 0) ? $qr[0] : false;
    }
}
