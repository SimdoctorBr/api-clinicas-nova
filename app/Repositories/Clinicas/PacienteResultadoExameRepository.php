<?php

namespace App\Repositories\Clinicas;

use Illuminate\Support\Facades\DB;
use App\Repositories\BaseRepository;
use App\Helpers\Functions;

class PacienteResultadoExameRepository extends BaseRepository {

    public function getAll($idDominio, $idPaciente = null, Array $dadosFiltro = null) {

        if (is_array($idDominio)) {
            $sql = 'A.identificador IN(' . implode(',', $idDominio) . ")";
        } else {
            $sql = "A.identificador = $idDominio";
        }
        if (!empty($idPaciente)) {
            $sql .= "AND A.pacientes_id = '$idPaciente'";
        }
        if (isset($dadosFiltro['dataIniStart']) and!empty($dadosFiltro['dataIniStart'])) {
            $sql .= " AND DATE_FORMAT(A.data_cad,'%Y-%m-%d') >='{$dadosFiltro['dataIniStart']}' ";
        }

        if (isset($dadosFiltro['dataInicio']) and!empty($dadosFiltro['dataInicio'])) {

            if (isset($dadosFiltro['dataFim']) and!empty($dadosFiltro['dataFim'])) {
                $sql .= " AND DATE_FORMAT(A.data_cad,'%Y-%m-%d') >='{$dadosFiltro['dataInicio']}' AND DATE_FORMAT(A.data_cad,'%Y-%m-%d') <= '{$dadosFiltro['dataFim']}'";
            } else {
                $sql .= " AND DATE_FORMAT(A.data_cad,'%Y-%m-%d') ='{$dadosFiltro['dataInicio']}' ";
            }
        }

        if (isset($dadosFiltro['dateTimeInicio']) and!empty($dadosFiltro['dateTimeInicio'])) {

            if (isset($dadosFiltro['datetimeFim']) and!empty($dadosFiltro['datetimeFim'])) {
                $sql .= " AND A.data_cad >='{$dadosFiltro['dateTimeInicio']}' AND A.data_cad <= '{$dadosFiltro['datetimeFim']}'";
            } else {
                $sql .= " AND A.data_cad ='{$dadosFiltro['dateTimeInicio']}' ";
            }
        }
        if (isset($dadosFiltro['habilitado_paciente']) and $dadosFiltro['habilitado_paciente'] = 1) {
            $sql .= " AND habilita_visualizar_paciente  = 1";
        }

        if (isset($dadosFiltro['id']) and!empty($dadosFiltro['id'])) {
            $sql .= " AND A.id  = {$dadosFiltro['id']}";
        }

        $qr = $this->connClinicas()->select("SELECT A.*, AES_DECRYPT(B.nome_cript, '$this->ENC_CODE') as nomePaciente,
        AES_DECRYPT(B.sobrenome_cript, '$this->ENC_CODE') as sobrenomePaciente,
              AES_DECRYPT(B.celular_cript, '$this->ENC_CODE') as celular,
        AES_DECRYPT(B.email_cript, '$this->ENC_CODE') as email,
        AES_DECRYPT(B.cpf_cript, '$this->ENC_CODE') as cpf, B.sexo, B.data_nascimento,B.permitir_dados_tuotempo, B.permite_prontuario_tuotempo
            FROM pacientes_resultado_exames as A 
            LEFT JOIN pacientes as B
            ON A.pacientes_id = B.id
            WHERE $sql  ORDER BY A.data_cad");
        return $qr;
    }

    public function getById($idDominio, $idArquivo) {

        $dadosFiltro['id'] = $idArquivo;
        $qr = $this->getAll($idDominio, null, $dadosFiltro);
        return $qr;
    }

}
