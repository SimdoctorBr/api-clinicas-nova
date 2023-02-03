<?php

namespace App\Repositories\Clinicas;

use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;

class DoutorFormacaoRepository extends BaseRepository {

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
        $from = " FROM doutores_formacoes as A WHERE $sqlDominio $sqlFiltro GROUP BY nome_formacao";
        $from .= (!empty($sqlFiltro)) ? " WHERE $sqlFiltro" : '';
//            $from .= $orderBy;


        $qr = $this->connClinicas()->select("SELECT $camposSQL $from");
        return $qr;
    }

    public function getDoutoresPorNomeFormacao($idDominio, $nomeFormacao) {

        if (is_array($idDominio)) {
            $sqlDominio = 'A.identificador IN(' . implode(',', $idDominio) . ")";
        } else {
            $sqlDominio = "A.identificador = $idDominio";
        }
        $qr = $this->connClinicas()->select("SELECT A.*,AES_DECRYPT(B.nome_cript, '$this->ENC_CODE')  as nomeDoutor
                                                 FROM doutores_formacoes AS A
                                                LEFT JOIN doutores AS B
                                                ON B.id = A.doutores_id
                                                WHERE
                                                $sqlDominio AND A.nome_formacao = '$nomeFormacao' AND B.status_doutor=1 ");
        return $qr;
    }

    public function getByDoutorId($idDominio, $doutorId) {

        $qr = $this->connClinicas()->select("SELECT A.*
                                                 FROM doutores_formacoes AS A
                                                WHERE
                                                A.identificador = $idDominio AND A.doutores_id = $doutorId  ");
        return $qr;
    }
    
    
     public function getDoutoresFiltro($idDominio, $idsDoutores, $agruparIdsFormacoes = false) {

        $campos = '*';
        $sqlFiltro = "";
        if ($agruparIdsFormacoes) {
            $campos = 'GROUP_CONCAT(DISTINCT(nome_formacao)) as nomesFormacao';
        }
        if (is_array($idsDoutores)) {
            $sqlFiltro = " AND doutores_id IN (" . implode(',', $idsDoutores) . ")";
        } else {
            $sqlFiltro = " AND doutores_id = $idsDoutores";
        }

        $qr = $this->connClinicas()->select(" SELECT $campos
                                            FROM doutores_formacoes
                                               WHERE identificador = $idDominio $sqlFiltro");
        return $qr;
    }

}
