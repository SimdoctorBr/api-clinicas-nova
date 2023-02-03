<?php

namespace App\Repositories\Clinicas;

use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;
use App\Helpers\Functions;

class FeriadoRepository extends BaseRepository { 

    /**
     * 
     * @param type $idDominio
     * @param type $dataIni
     * @param type $dataFim
     * @param type $tipoRetorno 1- Normal, 2 - Por array de datas
     * @return type
     */
    public function getByPeriodo($idDominio, $dataIni, $dataFim = null, $tipoRetorno = 1) {

      
        $sql = '';
        if (!empty($dataFim)) {
            $sql = "STR_TO_DATE(TRIM(A.data),'%d/%m/%Y') >= '$dataIni'  AND STR_TO_DATE(TRIM(A.data),'%d/%m/%Y') <= '$dataFim'";
        } else {
            $sql = "STR_TO_DATE(TRIM(A.data),'%d/%m/%Y') = '$dataIni'";
        }

        $retorno = null;
        $buscaFeriado = $this->connClinicas()->select("SELECT razao, data, B.id as idFeriadoDesativado
                                    FROM feriados AS A
                                    LEFT JOIN feriados_desativados AS B
                                     ON A.id = B.feriado_id
                                    WHERE $sql AND (A.identificador = '$idDominio' /*OR identificador IS NULL */)");
        if (count($buscaFeriado) > 0) {

            if ($tipoRetorno == 1) {
                $retorno = $buscaFeriado;
            } elseif ($tipoRetorno == 2) {
                foreach ($buscaFeriado as $rowFeriado) {
                    $retorno[Functions::dateBrToDB($rowFeriado->data)]['razao'] = $rowFeriado->razao;
                    $retorno[Functions::dateBrToDB($rowFeriado->data)]['desativado'] = (!empty($rowFeriado->idFeriadoDesativado)) ? true : false;
                }
            }
        }
        return $retorno;
    }

}
