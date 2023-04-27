<?php

namespace App\Repositories\Clinicas;

use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;
use stdClass;

class DiasAtendimentoRepository extends BaseRepository {

    public function getByDoutores($idDominio, $doutor_id, $diaSemanaId = null) {


        $order = " ORDER BY B.idDia ASC";

        $sql = '';
        if (!empty($diaSemanaId)) {
            $sql = " AND A.dias_da_semana_id = '$diaSemanaId' ";
            $order = null;
        }
        if (!empty($definicoesHorariosId)) {
            $sql .= " AND A.definicoes_horarios_id = $definicoesHorariosId";
        }



        $qr = $this->connClinicas()->select("SELECT A.*, B.nome as nomeDia,B.dia_em_php FROM dias_atendimento as A
                                    INNER JOIN dias_da_semana as B
                                    ON A.dias_da_semana_id = B.idDia
                                     WHERE A.identificador = '$idDominio' AND A.doutores_id = '$doutor_id' AND A.status = 1  $sql  $order");
        return $qr;
    }

}
