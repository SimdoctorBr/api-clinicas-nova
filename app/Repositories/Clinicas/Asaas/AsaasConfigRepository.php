<?php

namespace App\Repositories\Clinicas\Asaas;

use Illuminate\Support\Facades\DB;
use App\Repositories\BaseRepository;

class AsaasConfigRepository extends BaseRepository {

    public function getConfig($idDominio) {
        $qr = $this->connClinicas()->select("SELECT *,AES_DECRYPT(api_key, '$this->ENC_CODE') as apiKey,"
                . "AES_DECRYPT(email_notificacoes, '$this->ENC_CODE') as emailNotificacoes"
                . " FROM assas_config WHERE identificador = $idDominio");
        if (count($qr) > 0) {
            return $qr[0];
        } else {
            return false;
        }
    }

    public function vincularPacienteAsaas($identificador, $idPaciente, $customerId) {

        try {
            $campos['identificador'] = $identificador;
            $campos['pacientes_id'] = $idPaciente;
            $campos['customer_id'] = $customerId;
            $qr = $this->insertDB('pacientes_assas_clientes', $campos,null,'clinicas');
            return true;
        } catch (Exception $ex) {
            return $ex;
        }
    }

}
