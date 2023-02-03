<?php

namespace App\Repositories\Clinicas;

use Illuminate\Support\Facades\DB;
use App\Repositories\BaseRepository;

class AgendaFilaEsperaRepository extends BaseRepository {

    public function excluirPorConsultaId($idDominio, $consulta_id) {

        $this->connClinicas()->select("DELETE FROM agenda_fila_espera WHERE  identificador = $idDominio  AND consultas_id = $consulta_id LIMIT 1");
    }

    private function maxOrdem($idDominio, $doutores_id) {

        $dataHOje = date('Y-m-d');
        $qr = $this->connClinicas()->select("SELECT MAX(A.ordem) as ultimo FROM agenda_fila_espera as A 
            INNER JOIN consultas as B 
            ON A.consultas_id = B.id
            WHERE B.data_consulta = '$dataHOje' AND B.doutores_id = $doutores_id AND A.identificador = $idDominio
            ");

        if (count($qr) > 0) {
            return $qr[0]->ultimo;
        } else {
            return 0;
        }
    }

    public function insert($idDominio, $idConsulta, $doutoresId) {
        $campos['consultas_id'] = $idConsulta;
        $campos['identificador'] = $idDominio;
        $campos['doutores_id'] = $doutoresId;
        $campos['data_cad'] = date('Y-m-d h:i:s');

        $ordem = $this->maxOrdem($idDominio, $doutoresId);
        $campos['ordem'] = $ordem + 1;


        $qrVerifica = $this->connClinicas()->select("SELECT * FROM  agenda_fila_espera WHERE consultas_id = $idConsulta  AND identificador = $idDominio");

        if (count($qrVerifica) == 0) {
            return $qrInsert = $this->insertDB('agenda_fila_espera', $campos, null, 'clinicas');
        } else {
            $rowFilaItem = $qrVerifica[0];
            return $this->updateDB('agenda_fila_espera', $campos, "  id = $rowFilaItem->id AND identificador = $idDominio LIMIT 1", null, 'clinicas');
        }
    }

    /**
     * Busca a fila de espera de um doutor
     * @param type $identificador
     * @param type $doutores_id
     * @param type $idsExcetoFila
     * @param type $count
     * @param type $filtro
     * @return type
     */
    public function getFila($idDominio, $doutorId, $filtro = null, $count = false, $idsExcetoFila = null) {

        $dataHoje = date('Y-m-d');
        $orderBy = "ORDER BY A.ordem ASC";
        $sqlFiltro = '';
        $join = '';
        $sqlExceto = '';
        $wherejoin = '';

        if ($filtro != null) {
            if (isset($filtro['ordem']) and $filtro['ordem'] == 1) {
                $orderBy = $orderBy;
            } elseif (isset($filtro['ordem']) and $filtro['ordem'] == 2) {
                $orderBy = "ORDER BY B.hora_consulta ASC";
            }

            if (isset($filtro['liberacaoFilaAtendimento']) and $filtro['liberacaoFilaAtendimento'] == 1) {
                $sqlFiltro = " AND B.liberado_fila_espera = 1";
            }
        }

        //exibe somente agemdamentos pagos
        if ($filtro['somente_pago_fila'] == 1) {

            $join = "  LEFT JOIN financeiro_recebimento AS D
              ON (A.consultas_id = D.consulta_id AND A.identificador = D.identificador )";
            $wherejoin = "AND D.status = 1";
        }

        $campos = '';
        $groupBy = '';
        $sqlDOutor = '';

        if ($count) {
            $campos = 'Count(*) as total';
            $groupBy = '';
        } else {
            $campos = "A.*,B.pacientes_id, B.data_consulta, B.hora_consulta,B.hora_consulta_fim,TIMEDIFF(B.hora_consulta_fim,B.hora_consulta) as qntHorasAtendimento,
                    AES_DECRYPT(C.nome_cript, '$this->ENC_CODE') as nomePaciente,
                    AES_DECRYPT(C.sobrenome_cript, '$this->ENC_CODE') as sobrenomePaciente,
                    AES_DECRYPT(F.nome_cript, '$this->ENC_CODE') as nomeDoutor,
                     (SELECT status FROM consultas_status WHERE consulta_id = A.consultas_id ORDER BY id DESC LIMIT 1) AS status_consulta,
                     (SELECT  CONVERT_TZ(CAST(FROM_UNIXTIME(hora, '%Y-%m-%d %H:%i:%s') AS DATETIME),'+00:00','-03:00')  AS horaAg  FROM consultas_status WHERE consulta_id = A.consultas_id ORDER BY id DESC LIMIT 1) AS horaStatus,
                     B.dados_consulta,B.videoconferencia,E.hora_inicio AS horaInicioVideoPac, B.doutores_id,B.pausada, B.inicio_atendimento,B.liberado_fila_espera ";
            $groupBy = "   GROUP BY A.id";
        }

        if (!empty($idsExcetoFila)) {
            $sqlExceto = "AND id IS NOT IN($idsExcetoFila)";
        }


        if ($doutorId == 'todos') {
            $sqlDOutor = "";
        } else {
            $sqlDoutor = "AND B.doutores_id = $doutorId";
        }

//          $qr = $this->paginacao($camposSQL, $from, 'clinicas', $page, $perPage, false);
        $qr = $this->connClinicas()->select("SELECT 
                            $campos
                             FROM agenda_fila_espera as A 
                            INNER JOIN consultas as B 
                            ON A.consultas_id = B.id
                            INNER JOIN pacientes AS C
                            ON C.id = B.pacientes_id
                            $join
                                LEFT JOIN consultas_video_paciente_online AS E
                            ON E.consultas_id = A.consultas_id
                            LEFT JOIN doutores as F
                            ON F.id = A.doutores_id
                            WHERE B.data_consulta = '$dataHoje' $sqlDoutor AND A.identificador = $idDominio $sqlExceto $sqlFiltro     $wherejoin
                             $groupBy $orderBy               
                                ");
//
//        var_dump("SELECT 
//                            $campos
//                             FROM agenda_fila_espera as A 
//                            INNER JOIN consultas as B 
//                            ON A.consultas_id = B.id
//                            INNER JOIN pacientes AS C
//                            ON C.id = B.pacientes_id
//                            $join
//                                LEFT JOIN consultas_video_paciente_online AS E
//                            ON E.consultas_id = A.consultas_id
//                            LEFT JOIN doutores as F
//                            ON F.id = A.doutores_id
//                            WHERE B.data_consulta = '$dataHoje' $sqlDoutor AND A.identificador = $idDominio $sqlExceto $sqlFiltro     $wherejoin
//                             $groupBy $orderBy               
//                                ");
        if ($count) {
            return $qr[0]->total;
        } else {
            return $qr;
        }
    }


}
