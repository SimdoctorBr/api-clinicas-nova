<?php

namespace App\Repositories\Clinicas\Paciente;

use Illuminate\Support\Facades\DB;
use App\Repositories\BaseRepository;
use App\Helpers\Functions;

class PacienteAtestadoRepository extends BaseRepository {

    public function getAll($idDominio, Array $dadosFiltro = null, $page = null, $perPage = null) {

        $sqlOrdem = 'ORDER BY B.data_consulta';

        if (is_array($idDominio)) {
            $sql = 'A.identificador IN(' . implode(',', $idDominio) . ")";
        } else {
            $sql = " A.identificador = $idDominio ";
        }
        if (isset($dadosFiltro['pacienteId']) and ! empty($dadosFiltro['pacienteId'])) {
            $sql .= " AND B.pacientes_id = " . $dadosFiltro['pacienteId'];
        }


        if (isset($dadosFiltro['data']) and ! empty($dadosFiltro['data'])) {

            if (isset($dadosFiltro['dataFim']) and ! empty($dadosFiltro['dataFim'])) {
                $sql .= " AND B.data_consulta >='{$dadosFiltro['data']}' AND B.data_consulta <= '{$dadosFiltro['dataFim']}'";
            } else {
                $sql .= " AND B.data_consulta ='{$dadosFiltro['data']}' ";
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


        $camposSql = "A.*, B.`data`,B.data_consulta, B.hora_consulta, AES_DECRYPT(F.nome_cript, '$this->ENC_CODE') as nome, B.pacientes_id, B.id as idConsulta,G.descricao_cid10, G.codigo_cid10, G.tipo";
        $from = "FROM consultas_atestados as A 
                INNER JOIN consultas as B
                ON A.consulta_id = B.id                                           
                INNER JOIN doutores as F
                ON F.id = B.doutores_id
                LEFT JOIN atestado_cid10 as G
                   ON G.consulta_atestado_id = A.id  
                WHERE $sql";

        if ($page == null and $perPage == null) {
            $qr = $this->connClinicas()->select("SELECT $camposSql $from");
            return $qr;
        } else {
            $qr = $this->paginacao($camposSql, $from, 'clinicas', $page, $perPage, false);
            return $qr;
        }
    }

    public function getByConsultaId($idDominio, $consultaId) {
        $dadosFiltro['consultaId'] = $consultaId;
        $qr = $this->getAll($idDominio, null, $dadosFiltro);


        if (count($qr) > 0) {
            return $qr;
        } else {
            return false;
        }
    }

    public function store($idDominio, $pacienteId, Array $dadosInsert) {
        
    }

}
