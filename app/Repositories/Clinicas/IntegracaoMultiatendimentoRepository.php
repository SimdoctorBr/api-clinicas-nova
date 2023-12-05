<?php

namespace App\Repositories\Clinicas;

use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;

class IntegracaoMultiatendimentoRepository extends BaseRepository {

    public function store($idDominio, $campos) {
        $campos['identificador'] = $idDominio;
        $qr = $this->insertDB('integracao_multiatendimento', $campos, null, 'clinicas');
        return $qr;
    }

    public function update($idDominio, $id, $campos) {
        $campos['identificador'] = $idDominio;
        $qr = $this->updateDB('integracao_multiatendimento', $campos, "identificador = $idDominio and id = $id", null, 'clinicas');
        return $qr;
    }

    public function getConfig($idDominio) {
        $qrVerifica = $this->connClinicas()->select(" SELECT A.* FROM integracao_multiatendimento as A WHERE  identificador = $idDominio");
        if (count($qrVerifica)) {
            return $qrVerifica[0];
        } else {
            return false;
        }
    }
}
