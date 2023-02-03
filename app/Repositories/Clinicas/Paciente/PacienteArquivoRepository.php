<?php

namespace App\Repositories\Clinicas\Paciente;

use Illuminate\Support\Facades\DB;
use App\Repositories\BaseRepository;
use App\Helpers\Functions;

class PacienteArquivoRepository extends BaseRepository {

    public function getAll($idDominio, $idPaciente = null, Array $dadosFiltro = null, $page = null, $perPage = null) {

        $sqlOrdem = 'ORDER BY A.data_cad';

        if (is_array($idDominio)) {
            $sql = 'A.identificador IN(' . implode(',', $idDominio) . ")";
        } else {
            $sql = " A.identificador = $idDominio ";
        }
        if (!empty($idPaciente)) {
            $sql .= " AND A.pacientes_id = '$idPaciente' ";
        }
        if (isset($dadosFiltro['dataIniStart']) and ! empty($dadosFiltro['dataIniStart'])) {
            $sql .= " AND DATE_FORMAT(A.data_cad,'%Y-%m-%d') >='{$dadosFiltro['dataIniStart']}' ";
        }

        if (isset($dadosFiltro['dataInicio']) and ! empty($dadosFiltro['dataInicio'])) {

            if (isset($dadosFiltro['dataFim']) and ! empty($dadosFiltro['dataFim'])) {
                $sql .= " AND DATE_FORMAT(A.data_cad,'%Y-%m-%d') >='{$dadosFiltro['dataInicio']}' AND DATE_FORMAT(A.data_cad,'%Y-%m-%d') <= '{$dadosFiltro['dataFim']}'";
            } else {
                $sql .= " AND DATE_FORMAT(A.data_cad,'%Y-%m-%d') ='{$dadosFiltro['dataInicio']}' ";
            }
        }
        if (isset($dadosFiltro['dateTimeInicio']) and ! empty($dadosFiltro['dateTimeInicio'])) {

            if (isset($dadosFiltro['datetimeFim']) and ! empty($dadosFiltro['datetimeFim'])) {
                $sql .= " AND A.data_cad >= '{$dadosFiltro['dateTimeInicio']}' AND A.data_cad <= '{$dadosFiltro['datetimeFim']}'";
            } else {
                $sql .= " AND A.data_cad ='{$dadosFiltro['dateTimeInicio']}' ";
            }
        }
        if (isset($dadosFiltro['habilitado_paciente']) and $dadosFiltro['habilitado_paciente'] == 1) {
            $sql .= " AND A.habilita_visualizar_paciente  = 1";
        }

        if (isset($dadosFiltro['consultaId']) and ! empty($dadosFiltro['consultaId'])) {
            $sql .= " AND A.consultas_id  = {$dadosFiltro['consultaId']}";
        }

        if (isset($dadosFiltro['id']) and ! empty($dadosFiltro['id'])) {
            $sql .= " AND A.id  = {$dadosFiltro['id']}";
        }

        if (isset($dadosFiltro['campoOrdenacao']) and ! empty($dadosFiltro['campoOrdenacao'])) {
            $sqlOrdem = " ORDER BY {$dadosFiltro['campoOrdenacao']} ";
            if (isset($dadosFiltro['tipoOrdenacao']) and ! empty($dadosFiltro['tipoOrdenacao'])) {
                $sqlOrdem .= $dadosFiltro['tipoOrdenacao'];
            }
        }


        $camposSql = "A.*, AES_DECRYPT(B.nome_cript, '$this->ENC_CODE') as nomePaciente,
        AES_DECRYPT(B.sobrenome_cript, '$this->ENC_CODE') as sobrenomePaciente,
              AES_DECRYPT(B.celular_cript, '$this->ENC_CODE') as celular,
        AES_DECRYPT(B.email_cript, '$this->ENC_CODE') as email,
        AES_DECRYPT(B.cpf_cript, '$this->ENC_CODE') as cpf, B.sexo, B.data_nascimento,B.permitir_dados_tuotempo, B.permite_prontuario_tuotempo";

        $from = "FROM pacientes_arquivos as A 
            LEFT JOIN pacientes as B
            ON A.pacientes_id = B.id
            WHERE $sql  $sqlOrdem";

        if ($page == null and $perPage == null) {
            $qr = $this->connClinicas()->select("SELECT $camposSql $from");
            return $qr;
        } else {
            $qr = $this->paginacao($camposSql, $from, 'clinicas', $page, $perPage, false);
            return $qr;
        }
    }

    public function getById($idDominio, $idArquivo) {

        $dadosFiltro['id'] = $idArquivo;
        $qr = $this->getAll($idDominio, null, $dadosFiltro);
        return $qr;
    }

    public function store($idDominio, $pacienteId, Array $dadosInsert) {

        $dadosInsert['data_cad'] = date('Y-m-d H:i:s');
        $dadosInsert['pacientes_id'] = $pacienteId;
        $qr = $this->connClinicas()->table('pacientes_arquivos')->insertGetId($dadosInsert);
        return $qr;
    }

    
        public function update($idDominio, $idArquivo, $dadosUpdate) {


        $qr = $this->updateDB('pacientes_arquivos', $dadosUpdate, " identificador = $idDominio AND id = $idArquivo LIMIT 1 ", null, 'clinicas');
        return $qr;
    }

    public function delete($idDominio, $idArquivo) {


        $qr = $this->connClinicas()->select("DELETE FROM pacientes_arquivos WHERE identificador  = $idDominio AND id = $idArquivo LIMIT 1");
        return $qr;
    }
}
