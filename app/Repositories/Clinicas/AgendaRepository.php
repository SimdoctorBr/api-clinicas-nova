<?php

namespace App\Repositories\Clinicas;

use Illuminate\Support\Facades\DB;
use App\Repositories\BaseRepository;

class AgendaRepository extends BaseRepository {

    public function getResumo($idDominio, $doutoresId, $data) {

        
        
        
        $qr = $this->connClinicas()->select("SELECT TIME_FORMAT(MIN(hora_consulta), '%H:%i') AS horaPrimeiroAtendimento,TIME_FORMAT(MAX(hora_consulta), '%H:%i')  AS horaUltimoAtendimento,
                                        SUM((SELECT COUNT(*) FROM consultas_procedimentos WHERE consultas_id = A.id AND STATUS = 1  AND nome_proc NOT LIKE '%consulta%')) AS totalProcedimentos,
                                            SUM((SELECT COUNT(*) FROM consultas_procedimentos WHERE consultas_id = A.id AND STATUS = 1  AND nome_proc  LIKE '%consulta%')) AS totalConsultas


                                         FROM consultas AS A
                                        WHERE A.identificador = $idDominio AND A.doutores_id = $doutoresId AND A.data_consulta = '$data'
                                           AND ((SELECT status FROM consultas_status WHERE consulta_id = A.id ORDER BY id DESC LIMIT 1) != 'faltou' 
                                                AND (SELECT status FROM consultas_status WHERE consulta_id = A.id ORDER BY id DESC LIMIT 1) != 'desmarcado'
                                                OR (SELECT status FROM consultas_status WHERE consulta_id = A.id ORDER BY id DESC LIMIT 1) IS NULL)
                ");


        return $qr;
    }
    
   

}
