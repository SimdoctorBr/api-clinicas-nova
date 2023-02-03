<?php

namespace App\Repositories\Clinicas;

use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;
use stdClass;

class DefinicaoHorariosAdicionaisRepository extends BaseRepository {

    /**
     * verificando as configuraçcoes de horários do DOutor(a)
     * @param type $identificador
     * @param type $doutor_id
     * @param type $data
     * @return \App\Repositories\Clinicas\stdClass
     */
    public function getByIdDefinicoHorario($idDefinicaoHorario) {

        $qr = $this->connClinicas()->select("SELECT A.dias_da_semana_id,horario, status,video_desabilitado FROM definicoes_horarios_adicionais as A  
                                                            INNER JOIN dias_da_semana as B
                                                             ON B.idDia = A.dias_da_semana_id
                                                             WHERE  definicoes_horarios_id = '$idDefinicaoHorario'  ");
        return $qr;
    }

}
