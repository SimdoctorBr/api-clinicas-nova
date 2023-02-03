<?php

namespace App\Repositories\Clinicas\Paciente;

use Illuminate\Support\Facades\DB;
use App\Repositories\BaseRepository;
use App\Helpers\Functions;

class PacienteFotosRepository extends BaseRepository {

    public function getAll($idDominio, $idPaciente = null, Array $dadosFiltro = null, $page = null, $perPage = null) {

        $sqlOrdem = 'ORDER BY A.data_cad';
        if (is_array($idDominio)) {
            $sql = 'A.identificador IN(' . implode(',', $idDominio) . ") ";
        } else {
            $sql = "A.identificador = $idDominio  ";
        }
        if (!empty($idPaciente)) {
            $sql .= "AND A.pacientes_id = '$idPaciente'";
        }
        if (isset($dadosFiltro['dataInicio']) and ! empty($dadosFiltro['dataInicio'])) {

            if (isset($dadosFiltro['dataFim']) and ! empty($dadosFiltro['dataFim'])) {
                $sql .= " AND DATE_FORMAT(A.data_cad,'%Y-%m-%d') >='{$dadosFiltro['dataInicio']}'  AND DATE_FORMAT(A.data_cad,'%Y-%m-%d') <='{$dadosFiltro['dataFim']}' ";
            } else {
                $sql .= " AND DATE_FORMAT(A.data_cad,'%Y-%m-%d') >='{$dadosFiltro['dataInicio']}' ";
            }
        }

        if (isset($dadosFiltro['consultaId']) and ! empty($dadosFiltro['consultaId'])) {
            $sql .= " AND A.consultas_id  = {$dadosFiltro['consultaId']}";
        }

        if (isset($dadosFiltro['campoOrdenacao']) and ! empty($dadosFiltro['campoOrdenacao'])) {


            $sqlOrdem = " ORDER BY {$dadosFiltro['campoOrdenacao']} ";
            if (isset($dadosFiltro['tipoOrdenacao']) and ! empty($dadosFiltro['tipoOrdenacao'])) {
                $sqlOrdem .= $dadosFiltro['tipoOrdenacao'];
            }
        }


        $camposSQL = "A.*, AES_DECRYPT(B.nome_cript, '$this->ENC_CODE') as nome,
        AES_DECRYPT(B.sobrenome_cript, '$this->ENC_CODE') as sobrenome,
              AES_DECRYPT(B.celular_cript, '$this->ENC_CODE') as celular,
        AES_DECRYPT(B.email_cript, '$this->ENC_CODE') as email,
        AES_DECRYPT(B.cpf_cript, '$this->ENC_CODE') as cpf, B.sexo, B.data_nascimento,B.permitir_dados_tuotempo, B.permite_prontuario_tuotempo";

        $from = "  FROM pacientes_fotos as A 
            LEFT JOIN pacientes as B
            ON A.pacientes_id = B.id
            WHERE $sql $sqlOrdem";



        if ($page == null and $perPage == null) {
            $qr = $this->connClinicas()->select("SELECT $camposSQL $from");
            return $qr;
        } else {
            $qr = $this->paginacao($camposSQL, $from, 'clinicas', $page, $perPage, false);
            return $qr;
        }
    }

    public function getById($idDominio, $idFoto) {

        $qr = $this->connClinicas()->select("SELECT * FROM pacientes_fotos as A WHERE A.identificador = $idDominio AND id = $idFoto ");

        if (count($qr) > 0) {
            return $qr[0];
        } else {
            return false;
        }
    }

    public function store($idDominio, $pacienteId, $dadosInsert) {

        $dadosInsert['data_cad'] = date('Y-m-d H:i:s');
        $dadosInsert['pacientes_id'] = $pacienteId;
        $qr = $this->connClinicas()->table('pacientes_fotos')->insertGetId($dadosInsert);
        return $qr;
    }

    public function update($idDominio, $idFoto, $dadosUpdate) {
        $qr = $this->updateDB('pacientes_fotos', $dadosUpdate, " identificador = $idDominio AND id = $idFoto LIMIT 1 ", null, 'clinicas');
        return $qr;
    }

    public function delete($idDominio, $idFoto) {
        $qr = $this->connClinicas()->select("DELETE FROM pacientes_fotos WHERE identificador  = $idDominio AND id = $idFoto LIMIT 1");
        return $qr;
    }

}
