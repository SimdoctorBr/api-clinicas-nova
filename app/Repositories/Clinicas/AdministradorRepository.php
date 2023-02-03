<?php

namespace App\Repositories\Clinicas;

use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;

class AdministradorRepository extends BaseRepository {

    public function getById($idDominio, $userId) {


        $qrVerifica = $this->connClinicas()->select("SELECT A.*, AES_DECRYPT(B.nome_cript,'$this->ENC_CODE') as nomeDoutor, C.abreviacao FROM  administradores AS A
                                                    LEFT JOIN doutores as B
                                                    ON A.doutor_user_vinculado = B.id

                                                    LEFT JOIN pronomes_tratamento as C
                                                    ON B.pronome_id= C.idPronome
                                                     WHERE A.id = $userId
                                                    AND A.identificador = $idDominio
                                                    ");

        if (count($qrVerifica) == 0) {
            return false;
        } else {
            return $qrVerifica[0];
        }
    }

    public function update($idDominio, $idAdministrador, $dados) {
        $qr = $this->updateDB('administradores', $dados, " id =$idAdministrador AND identificador = $idDominio LIMIT 1 ", null, 'clinicas');
        return $qr;
    }

    public function verificaSenha($idDominio, $idAdministrador, $senha) {
        $qr = $this->connClinicas()->select("SELECT * FROM administradores WHERE identificador = $idDominio AND id = $idAdministrador AND senha = '$senha'");
        if (count($qr) > 0) {
            return $qr[0];
        } else {
            return false;
        }
    }

    public function buscaPorEmail($idDominio = null, $email, $codigo = null) {
        $sqlFiltro = '';
        if (!empty($idDominio)) {

            if (is_array($idDominio)) {
                $sqlFiltro = " AND identificador IN( " . implode(',', $idDominio) . ")";
            } else {
                $sqlFiltro = " AND identificador = $idDominio   ";
            }
        }
        if (!empty($codigo)) {
            $sqlFiltro .= " AND cod_troca_senha = $codigo  AND  cod_senha_validade >= '" . date('Y-m-d H:i:s') . "' ";
        }

        $qr = $this->connClinicas()->select("SELECT * FROM administradores WHERE email = '$email' $sqlFiltro");

        return $qr;
    }

}
