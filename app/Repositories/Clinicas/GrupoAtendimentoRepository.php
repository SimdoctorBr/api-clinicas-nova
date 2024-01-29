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

    public function getById($idDominio, $grupoAtendId) {

        $qr = $this->connClinicas()->select("SELECT A.*
                                                 FROM grupo_atendimento AS A
                                                WHERE (A.identificador IS NULL OR A.identificador = $idDominio)
                                                    AND A.id = $grupoAtendId");
        if (count($qr) > 0) {
            return $qr[0];
        } else {
            return false;
        }
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

    public function verificaGrupoDoutor($idDominio, $idDoutor, $idGrupo) {
        $qr = $this->connClinicas()->select("SELECT id FROM doutores_grupo_atendimento WHERE identificador = $idDominio AND grupo_atendimento_id= $idGrupo AND doutores_id = $idDoutor");
        if (count($qr) > 0) {
            return $qr[0];
        } else {
            return false;
        }
    }

    public function storeGrupoDoutor($idDominio, $idDoutor, $dados) {
        $dados['identificador'] = $idDominio;
        $dados['doutores_id'] = $idDoutor;
        return $qr = $this->insertDB('doutores_grupo_atendimento', $dados, null, 'clinicas');
    }
    
    public function updateGrupoDoutorByIdDoutoresGrupo($idDominio, $idDoutoresGrupo,$dados) {
        return $qr = $this->updateDB('doutores_grupo_atendimento', $dados, " identificador = $idDominio AND id = $idDoutoresGrupo LIMIT 1",null, 'clinicas');
    }
}
