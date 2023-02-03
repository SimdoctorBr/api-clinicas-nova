<?php

namespace App\Repositories\Clinicas;

use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;

class GrupoAtendimentoRepository extends BaseRepository {

    public function getAll($idDominio, $dadosFiltro = null) {

        if (is_array($idDominio)) {
            $sqlDominio = 'A.identificador IN(' . implode(',', $idDominio) . ")";
        } else {
            $sqlDominio = "A.identificador = $idDominio";
        }


        $sqlFiltro = '';
//        $orderBy = "ORDER BY    nome ASC";
//        if (isset($dadosFiltro['orderBy']) and ! empty($dadosFiltro['orderBy'])) {
//            $orderBy = " ORDER BY {$dadosFiltro['orderBy']} ";
//        }

        $camposSQL = "A.*";
        $from = " 
                    FROM grupo_atendimento as A WHERE 
                                        ($sqlDominio OR identificador IS NULL) $sqlFiltro
              ";
        $from .= (!empty($sqlFiltro)) ? " WHERE $sqlFiltro" : '';
//            $from .= $orderBy;


        $qr = $this->connClinicas()->select("SELECT $camposSQL $from  ORDER BY ordem ASC");
        return $qr;
    }

    public function getDoutoresPorGrupoAtendimentoId($idDominio, $grupoAtendId) {

        if (is_array($idDominio)) {
            $sqlDominio = 'A.identificador IN(' . implode(',', $idDominio) . ")";
        } else {
            $sqlDominio = "A.identificador = $idDominio";
        }

        $qr = $this->connClinicas()->select("SELECT A.*,   AES_DECRYPT(B.nome_cript, '$this->ENC_CODE')  as nomeDoutor
                                                 FROM doutores_grupo_atendimento AS A
                                                LEFT JOIN doutores AS B
                                                ON B.id = A.doutores_id
                                                WHERE
                                               $sqlDominio AND A.grupo_atendimento_id = $grupoAtendId AND A.STATUS = 1 AND B.status_doutor=1");
        return $qr;
    }

    public function getByDoutorId($idDominio, $doutorId) {

        if (is_array($idDominio)) {
            $sqlDominio = 'A.identificador IN(' . implode(',', $idDominio) . ")";
        } else {
            $sqlDominio = "A.identificador = $idDominio";
        }
        $qr = $this->connClinicas()->select("SELECT A.*,   B.nome
                                                 FROM doutores_grupo_atendimento AS A
                                                LEFT JOIN grupo_atendimento AS B
                                                ON B.id = A.grupo_atendimento_id
                                                WHERE
                                               $sqlDominio AND A.doutores_id = $doutorId AND A.STATUS = 1 ORDER BY B.ordem ASC");
        return $qr;
    }

    public function getDoutoresFiltro($idDominio, $idsDoutores, $agruparIdsGrupoAtendimento = false) {

        $campos = '*';
        $sqlFiltro = "";
        if ($agruparIdsGrupoAtendimento) {
            $campos = 'GROUP_CONCAT(DISTINCT(grupo_atendimento_id)) as idsGruposAtend';
        }
        if (is_array($idsDoutores)) {
            $sqlFiltro = " AND doutores_id IN (" . implode(',', $idsDoutores) . ")";
        } else {
            $sqlFiltro = " AND doutores_id = $idsDoutores";
        }
     
        $qr = $this->connClinicas()->select(" SELECT $campos
                                            FROM doutores_grupo_atendimento
                                               WHERE identificador = $idDominio $sqlFiltro  AND status = 1");
        return $qr;
    }

}
