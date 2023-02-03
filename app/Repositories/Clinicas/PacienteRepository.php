<?php

namespace App\Repositories\Clinicas;

use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;

class PacienteRepository extends BaseRepository {

    public function validateLogin($idDominio, $email, $senha) {


        $qr = $this->connClinicas()->select("SELECT * FROM pacientes WHERE identificador = $idDominio AND AES_DECRYPT(email_cript,'$this->ENC_CODE') = '$email' AND senha = '$senha'");
        if (count($qr) > 0) {
            return $qr[0];
        } else {
            return false;
        }
    }

    public function getById($idDominio, $idPaciente, $verify = false) {

        if ($verify) {
            $camposSql = "id";
        } else {
            $camposSql = "*,DATE_FORMAT(FROM_UNIXTIME(data_cadastro),'%d/%m/%Y') as dataCadastro, DATE_FORMAT(data_ultima_alter,'%d/%m/%Y') as dataUltimaAlter ,
                                                AES_DECRYPT(nome_cript, '$this->ENC_CODE') as nome_cript,
                                                AES_DECRYPT(sobrenome_cript, '$this->ENC_CODE') as sobrenome_cript,
                                                AES_DECRYPT(cpf_cript, '$this->ENC_CODE') as cpf_cript,
                                                AES_DECRYPT(telefone_cript, '$this->ENC_CODE') as telefone_cript,
                                                AES_DECRYPT(telefone2_cript, '$this->ENC_CODE') as telefone2_cript,
                                                AES_DECRYPT(celular_cript, '$this->ENC_CODE') as celular_cript,
                                                AES_DECRYPT(email_cript, '$this->ENC_CODE') as email_cript, 
                                                AES_DECRYPT(rg_cript, '$this->ENC_CODE') as rg_cript,
                                                AES_DECRYPT(cpf_cript, '$this->ENC_CODE') as cpf_cript,
                                                AES_DECRYPT(nome_conjuge_cript, '$this->ENC_CODE') as nome_conjuge_cript,
                                                AES_DECRYPT(cartao_nacional_saude_cript, '$this->ENC_CODE') as cartao_nacional_saude_cript,
                                                AES_DECRYPT(filiacao_pai, '$this->ENC_CODE') as filiacao_pai, 
                                                AES_DECRYPT(filiacao_mae, '$this->ENC_CODE') as filiacao_mae,
                                                AES_DECRYPT(nome_social, '$this->ENC_CODE') as nome_social
                                                    ";
        }

        $qr = $this->connClinicas()->select("SELECT $camposSql FROM pacientes WHERE identificador = $idDominio AND id = $idPaciente");
        if (count($qr) > 0) {
            return $qr[0];
        } else {
            return false;
        }
    }

}
