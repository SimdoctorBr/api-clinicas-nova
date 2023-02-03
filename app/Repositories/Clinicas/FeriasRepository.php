<?php

namespace App\Repositories\Clinicas;

use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;


class FeriasRepository extends BaseRepository {

    public function verificaFerias($idDominio, $idDoutor, $data) {

        $qrFerias = "SELECT * FROM ferias WHERE identificador = '$idDominio' 
                                    AND  doutores_id = '$idDoutor'
                                    AND  '$data' >=  STR_TO_DATE(inicio,'%d/%m/%Y') 
                                    AND  '$data' <=  STR_TO_DATE(fim,'%d/%m/%Y') ORDER BY id DESC ";
        $qrFerias = $this->connClinicas()->select($qrFerias);
        if (count($qrFerias) > 0) {

            $row = $qrFerias[0];
            $retorno['ferias'] = true;
            $retorno['inicio'] = $row->inicio;
            $retorno['fim'] = $row->fim;
        } else {
            $retorno['ferias'] = false;
        }
        return $retorno;
    }

}
