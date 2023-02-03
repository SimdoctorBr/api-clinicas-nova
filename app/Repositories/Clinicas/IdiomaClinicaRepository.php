<?php

namespace App\Repositories\Clinicas;

use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;

class IdiomaClinicaRepository extends BaseRepository {

    public function getAll($idDominio, $dadosFiltro = null) {


        $qr = $this->connClinicas()->select("SELECT * FROM idiomas ");
        return $qr;
    }

    public function getDoutoresPorIdiomaId($idDominio, $grupoAtendId) {
        $sql = '';
        if (is_array($idDominio)) {
            $sql = "    A.identificador in(" . implode(',', $idDominio) . ")";
        } else {
            $sql = "  A.identificador = $idDominio";
        }

        $qr = $this->connClinicas()->select("SELECT A.*,   AES_DECRYPT(B.nome_cript, '$this->ENC_CODE')  as nomeDoutor
                                                 FROM doutores_idiomas AS A
                                                LEFT JOIN doutores AS B
                                                ON B.id = A.doutores_id
                                                WHERE
                                              $sql AND A.idiomas_id = $grupoAtendId AND A.STATUS = 1 AND B.status_doutor=1");
        return $qr;
    }

    public function getByDoutorId($idDominio, $doutorId) {

        $sql = '';
        if (is_array($idDominio)) {
            $sql = "    A.identificador in(" . implode(',', $idDominio) . ")";
        } else {
            $sql = "  A.identificador = $idDominio";
        }
        $qr = $this->connClinicas()->select("SELECT A.*,   B.nome
                                                 FROM doutores_idiomas AS A
                                                LEFT JOIN idiomas AS B
                                                ON B.id = A.idiomas_id
                                                WHERE
                                             $sql AND A.doutores_id = $doutorId AND A.STATUS = 1");
        return $qr;
    }

    public function getDoutoresFiltro($idDominio, $idsDoutores, $agruparIdsIdiomas = false) {

        $campos = '*';
        $sqlFiltro = "";
        if ($agruparIdsIdiomas) {
            $campos = 'GROUP_CONCAT(DISTINCT(idiomas_id)) as idsIdiomas';
        }
        if (is_array($idsDoutores)) {
            $sqlFiltro = " AND doutores_id IN (" . implode(',', $idsDoutores) . ")";
        } else {
            $sqlFiltro = " AND doutores_id = $idsDoutores";
        }

        $qr = $this->connClinicas()->select(" SELECT $campos
                                            FROM doutores_idiomas
                                               WHERE identificador = $idDominio $sqlFiltro  AND status = 1");
        return $qr;
    }

}
