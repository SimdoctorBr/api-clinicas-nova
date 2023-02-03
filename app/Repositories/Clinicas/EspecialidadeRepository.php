<?php

namespace App\Repositories\Clinicas;

use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;
use App\Repositories\Clinicas\DoutoresRepository;

class EspecialidadeRepository extends DoutoresRepository {

    public function getAll($idDominio, $dadosFiltro = null, $page = null, $perPage = null) {

        if (is_array($idDominio)) {
            $sqlDominio = 'identificador IN(' . implode(',', $idDominio) . ")";
        } else {
            $sqlDominio = "identificador = $idDominio";
        }


        $sqlFiltro = '';
        $sqlFiltroUnion = '';

        $orderBy = "ORDER BY    nome ASC";
        if (isset($dadosFiltro['orderBy']) and!empty($dadosFiltro['orderBy'])) {
            $orderBy = " ORDER BY {$dadosFiltro['orderBy']} ";
        }

        if (isset($dadosFiltro['withDoctors']) and $dadosFiltro['withDoctors'] == true) {

            $sqlFiltro = '';
            if (isset($dadosFiltro['tipoAtendimento']) and!empty($dadosFiltro['tipoAtendimento'])) {
                $sqlFiltro .= $this->sqlFilterTipoAtendimento($dadosFiltro['tipoAtendimento'], 'C');
                $sqlFiltroUnion .= $this->sqlFilterTipoAtendimento($dadosFiltro['tipoAtendimento'], 'A');
            }
            if (isset($dadosFiltro['valorConsulta']) and!empty($dadosFiltro['valorConsulta'])) {

                $tipoAtendimentoF = (isset($dadosFiltro['tipoAtendimento'])) ? $dadosFiltro['tipoAtendimento'] : null;
                $valorConsultaMax = (isset($dadosFiltro['valorConsultaMax'])) ? $dadosFiltro['valorConsultaMax'] : null;
                $sqlFiltro .= $this->sqlFilterValorConsulta($idDominio, $dadosFiltro['valorConsulta'], $valorConsultaMax, $tipoAtendimentoF, 'C');
                $sqlFiltroUnion .= $this->sqlFilterValorConsulta($idDominio, $dadosFiltro['valorConsulta'], $valorConsultaMax, $tipoAtendimentoF, 'A');
                unset($tipoAtendimentoF);
            }

//            if (isset($dadosFiltro['sexo']) and!empty($dadosFiltro['sexo'])) {
//              $sqlFiltro .=  $this->sqlFilterSexo($dadosFiltro['sexo']);
//            }
//            if (isset($dadosFiltro['nomeFormacao']) and !empty($dadosFiltro['nomeFormacao'])) {
////                 dd( $dadosFiltro['nomeFormacao']);
//                $sqlFiltro .= $this->sqlFilterNomeFormacao($dadosFiltro['nomeFormacao'], 'C');
//            }

            $camposSQL = " A.identificador, A.doutores_id, A.especialidade_id,CAST(A.outro AS CHAR(255)) AS outro, if(A.outro IS NOT NULL, A.outro, B.nome) AS nome,
                AES_DECRYPT(C.nome_cript, '$this->ENC_CODE')  AS nomeDoutor, C.sexo";
            $from = " FROM doutores_especialidades AS A
            LEFT JOIN especialidades AS B
            ON A.especialidade_id = B.id
            LEFT JOIN doutores AS C
            ON C.id = A.doutores_id
                        LEFT JOIN procedimentos_doutores_assoc as H
            ON (H.id =  C.proc_doutor_id_presencial AND  H.`status`=1)
             LEFT JOIN procedimentos as I
            ON (I.idProcedimento =  H.procedimentos_id)
               LEFT JOIN convenios as J
            ON (J.id =  H.proc_convenios_id)
              LEFT JOIN procedimentos_convenios_assoc as L
            ON (L.convenios_id =  H.proc_convenios_id AND L.procedimentos_id = H.procedimentos_id
				AND L.`status` = 1)
            WHERE
            A.$sqlDominio AND C.status_doutor = 1 $sqlFiltro 
                
            UNION
            SELECT A.identificador, A.id AS doutores_id, A.especialidades_id AS especialidade_id,
            CAST(A.outra_especialidade AS CHAR(255))  AS outro,
            if(A.outra_especialidade != '', CAST(A.outra_especialidade AS CHAR(255)), B.nome) AS nome,
            AES_DECRYPT(A.nome_cript,
            '$this->ENC_CODE') AS nomeDoutor,A.sexo AS sexo
              FROM doutores AS A 
            LEFT JOIN especialidades AS B
            ON A.especialidades_id = B.id
             LEFT JOIN procedimentos_doutores_assoc as H
            ON (H.id =  A.proc_doutor_id_presencial AND  H.`status`=1)
             LEFT JOIN procedimentos as I
            ON (I.idProcedimento =  H.procedimentos_id)
               LEFT JOIN convenios as J
            ON (J.id =  H.proc_convenios_id)
              LEFT JOIN procedimentos_convenios_assoc as L
            ON (L.convenios_id =  H.proc_convenios_id AND L.procedimentos_id = H.procedimentos_id
				AND L.`status` = 1)
            WHERE A.$sqlDominio AND A.status_doutor = 1
            AND (B.nome IS NOT NULL OR A.outra_especialidade != '')
            $sqlFiltroUnion
              ";
//            $from .= (!empty($sqlFiltro)) ? " WHERE $sqlFiltro" : '';
            $from .= $orderBy;
        } else {
            $camposSQL = "*";
            $from = " 
            FROM (SELECT A.nome
                    FROM especialidades AS A
                    WHERE $sqlDominio                     
                    UNION
                    SELECT outro AS nome FROM doutores_especialidades WHERE
                    $sqlDominio AND outro IS NOT NULL) as q1
              ";
            $from .= (!empty($sqlFiltro)) ? " WHERE $sqlFiltro" : '';
            $from .= $orderBy;
        }




// 
//
//        if (auth('clinicas_pacientes')->user()->id = 89375) {
//            var_dump("SELECT $camposSQL $from");
//            exit;
//        }
//        if ($page == null and $perPage == null) {
        $qr = $this->connClinicas()->select("SELECT $camposSQL $from");
        return $qr;
//        } else {
//            $qr = $this->paginacao($camposSQL, $from, 'clinicas', $page, $perPage, false);
//            return $qr;
//        }
    }

    public function getByDoutorId($idDominio, $doutores_id) {

        if (is_array($idDominio)) {
            $sqlDominio = 'A.identificador IN(' . implode(',', $idDominio) . ")";
        } else {
            $sqlDominio = "A.identificador = $idDominio";
        }

        $qr = $this->connClinicas()->select("SELECT *, if(outro IS NOT NULL, outro, B.nome) AS nome 
            FROM doutores_especialidades AS A
            LEFT JOIN especialidades AS B
            ON A.especialidade_id = B.id
            WHERE $sqlDominio AND doutores_id = $doutores_id");
        return $qr;
    }

}
