<?php

namespace App\Repositories\Clinicas\Doutores;

use Illuminate\Support\Facades\DB;
use App\Repositories\BaseRepository;
use App\Helpers\Functions;

class DoutoresFotosRepository extends BaseRepository {

    public function getAll($idDominio, $doutorId = null, Array $dadosFiltro = null, $page = null, $perPage = null) {

        $sqlOrdem = 'ORDER BY A.data_cad';
        if (is_array($idDominio)) {
            $sql = 'A.identificador IN(' . implode(',', $idDominio) . ") ";
        } else {
            $sql = "A.identificador = $idDominio  ";
        }
        if (!empty($doutorId)) {
            $sql .= "AND A.doutores_id = '$doutorId'";
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


        $camposSQL = "A.*";

        $from = "  FROM doutores_cad_fotos as A 
            WHERE $sql $sqlOrdem";


//var_dump("SELECT $camposSQL $from");
        if ($page == null and $perPage == null) {
            $qr = $this->connClinicas()->select("SELECT $camposSQL $from");
            return $qr;
        } else {
            $qr = $this->paginacao($camposSQL, $from, 'clinicas', $page, $perPage, false);
            return $qr;
        }
    }

    public function getById($idDominio, $idFoto) {

        $qr = $this->connClinicas()->select("SELECT * FROM doutores_cad_fotos as A WHERE A.identificador = $idDominio AND id = $idFoto ");

        if (count($qr) > 0) {
            return $qr[0];
        } else {
            return false;
        }
    }

    public function store($idDominio, $doutores_id, $dadosInsert) {

        $dadosInsert['data_cad'] = date('Y-m-d H:i:s');
        $dadosInsert['doutores_id'] = $doutores_id;
        $qr = $this->connClinicas()->table('doutores_cad_fotos')->insertGetId($dadosInsert);
        return $qr;
    }

    public function update($idDominio, $idFoto, $dadosUpdate) {
        $qr = $this->updateDB('doutores_cad_fotos', $dadosUpdate, " identificador = $idDominio AND id = $idFoto LIMIT 1 ", null, 'clinicas');
        return $qr;
    }

    public function delete($idDominio, $idFoto) {
        $qr = $this->connClinicas()->select("DELETE FROM doutores_cad_fotos WHERE identificador  = $idDominio AND id = $idFoto LIMIT 1");
        return $qr;
    }

}
