<?php

namespace App\Repositories\Clinicas;

use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;

class CodigoConfirmacaoRepository extends BaseRepository {

    public function verificaRegistro($idDominio, $idTipo, $tipo) {
         $sql = '';
        if (!empty($idTipo)) {
            $sql .= " and  id_tipo = $idTipo";
        }
      
        $qr = $this->connClinicas()->select("SELECT id FROM codigo_confirmacao WHERE identificador =$idDominio and tipo = $tipo  $sql  ");
        if (count($qr) > 0) {
            return $qr[0];
        } else {
            return false;
        }
    }

    public function verificaCodigo($idDominio, $idPaciente, $tipo, $codigo) {

        $qr = $this->connClinicas()->select("SELECT id FROM codigo_confirmacao WHERE identificador =$idDominio and tipo = $tipo and  id_tipo = $idPaciente  AND codigo = $codigo and status = 1");
        if (count($qr) > 0) {
            return $qr[0];
        } else {
            return false;
        }
    }

    public function verificaCodigoPorEmail($idDominio, $tipo, $email, $codigo) {
        $qr = $this->connClinicas()->select("SELECT id FROM codigo_confirmacao WHERE identificador =$idDominio and tipo = $tipo and  AES_DECRYPT(email, '$this->ENC_CODE') = '$email'  AND codigo = $codigo and status = 1");
        if (count($qr) > 0) {
            return $qr[0];
        } else {
            return false;
        }
    }

    public function store($idDominio, $campos) {
        $campos['identificador'] = $idDominio;
        $qr = $this->insertDB('codigo_confirmacao', $campos, ['email','temp_content'], 'clinicas');
        return $qr;
    }

    public function update($idDominio, $id, $campos) {
        $campos['identificador'] = $idDominio;
        $qr = $this->updateDB('codigo_confirmacao', $campos, " identificador = $idDominio AND id =$id  LIMIT 1", ['email','temp_content'], 'clinicas');
        return $qr;
    }
  
}
