<?php

namespace App\Repositories\Clinicas\Asaas;

use Illuminate\Support\Facades\DB;
use App\Repositories\BaseRepository;

class AssasSubcontasSplitClinicasRepository extends BaseRepository {

    public function store($idDominio, $dados) {
        $qr = $this->insertDB('asaas_subcontas', $dados, ['apiKey', 'walletId'], 'clinicas');
        return $qr;
    }

    public function update($idDominio, $id, $dados) {

        $qr = $this->updateDB('asaas_subcontas', $dados, " identificador = $idDominio AND id = $id", null, 'clinicas');
        return $qr;
    }

    /**
     * 
     * @param type $idDominio
     * @param type $tipoCliente 1- DOutores
     * @param type $tipoId
     * @return bool
     */
    public function getByTipoClienteId($idDominio, $tipoCliente, $tipoId) {

        $qr = $this->connClinicas()->select("SELECT  *,AES_DECRYPT(walletId, '$this->ENC_CODE') as walletId FROM asaas_subcontas AS A  WHERE identificador = $idDominio
            AND tipo_cliente = $tipoCliente AND id_tipo = $tipoId");
        if (count($qr) > 0) {
            return $qr[0];
        } else {
            return false;
        }
    }

    /**
     * 
     * @param type $idDominio
     * @param type $tipoCliente 1- DOutores
     * @param type $tipoId
     * @return bool
     */
    public function insertSplitHistorico($idDominio, $dados) {
        $dados['identificador'] = $idDominio;
        $qr = $this->insertDB('pacientes_assas_pag_split', $dados,null,'clinicas');
        return $qr;
    }
}
