<?php

namespace App\Repositories\Clinicas;

use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;

class CompromissoRepository extends BaseRepository {

    public function getAll($idDominio, $idDoutor, $dadosFiltro = null) {

        $sqlData = '';
        if (isset($dadosFiltro['data']) and ! empty($dadosFiltro['data'])) {

            if (isset($dadosFiltro['dataFim']) and ! empty($dadosFiltro['dataFim'])) {
                $sqlData = " AND  A.data_compromisso >='{$dadosFiltro['data']}' AND A.data_compromisso <= '{$dadosFiltro['dataFim']}' ";
            } else {
                $sqlData = "AND A.data_compromisso = '{$dadosFiltro['data']}'";
            }
        }

        $qr = $this->connClinicas()->select("SELECT A.*      FROM	compromissos as A
                                         WHERE 
                                        identificador = $idDominio AND A.doutores_id = '$idDoutor' 
                                             $sqlData
                                        ORDER BY hora_agendamento");

        return $qr;
    }

       public function getById($idDominio, $idCompromisso) {

        $qr = $this->connClinicas()->select("SELECT A.*, AES_DECRYPT(B.nome_cript, '$this->ENC_CODE') as nomeDoutor FROM compromissos as A 
            LEFT JOIN doutores as B
            ON A.doutores_id = B.id
            WHERE A.identificador = $idDominio AND A.id = '$idCompromisso' ");

        if (count($qr) > 0) {
            return $qr[0];
        } else {
            return false;
        }
    }


    public function store($idDominio, $campos) {

        $campos['identificador'] = $idDominio;
        $qr = $this->insertDB('compromissos', $campos, null, 'clinicas');
        return $qr;
    }

    public function delete($idDominio, $compromissoId) {
        $qr = $this->connClinicas()->select("DELETE FROM compromissos WHERE identificador = $idDominio AND id =$compromissoId  LIMIT 1");
        return $qr;
    }
    public function alterarStatus($idDominio, $compromissoId,$status) {
        $dados['realizado'] = $status;
        $qr = $this->updateDB('compromissos', $dados, " identificador = $idDominio AND id =$compromissoId  LIMIT 1", null, 'clinicas');
        return $qr;
    }

}
