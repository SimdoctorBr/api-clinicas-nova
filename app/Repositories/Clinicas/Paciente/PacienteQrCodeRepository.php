<?php

namespace App\Repositories\Clinicas\Paciente;

use Illuminate\Support\Facades\DB;
use App\Repositories\BaseRepository;

class PacienteQrCodeRepository extends BaseRepository {

    public function verificaExiste($idDominio, $idPaciente,$status=null) {

        $sql  ='';
        if(!empty($status)){
            $sql = " AND status = $status";
        }
        
        $qr = $this->connClinicas()->select("SELECT * FROM pacientes_qrcode WHERE identificador = $idDominio AND pacientes_id  = $idPaciente $sql");
        if (count($qr) > 0) {
            return $qr[0];
        } else {
            return false;
        }
    }

    public function alterarStatus($idDominio, $idCadQRcode, $status) {

        $campos['administrador_id_status'] = ( auth('clinicas')->check()) ? auth('clinicas')->user()->id : null;
        $campos['data_status'] = date('Y-m-d H:i:s');
        $campos['status'] = $status;

        $qr = $this->updateDB('pacientes_qrcode', $campos, " identificador = $idDominio AND id = $idCadQRcode ",null,'clinicas');
    }
}
