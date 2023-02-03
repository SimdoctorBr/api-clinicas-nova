<?php

namespace App\Repositories\Clinicas;

use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;
use stdClass;

class DiasHorariosBloqueadosRepository extends BaseRepository {

    public function getByDoutores($idDominio, $doutores_id, $data, $dataTermino = null, $horario = null, $area_bloqueio = null) {
        $sql = '';
        if (empty($dataTermino)) {
            $dataTermino = $data;
            $sql = "(
                                ( data >= '$data' AND data <='$data' AND data_final IS NULL)
                                OR
                                (data <= '$data'   AND data_final >='$data')
                                )";
        } else {
            $sql = "     (  ( data >= '$data' AND data <='$dataTermino' AND data_final IS NULL)
                                                            OR
                                                            (data >= '$data' AND data <='$dataTermino'  OR data_final >= '$data' AND data_final <='$dataTermino')
                                                            )";
        }


        if (!empty($horario)) {
            $sql .= "   AND horario = '$horario:00'";
        }
        if (!empty($area_bloqueio)) {
            $sql .= "    AND area_bloqueio = '$area_bloqueio'";
        }

        $qr = $this->connClinicas()->select("SELECT id,area_bloqueio,status_bloqueio, data, horario,lancado_config_bloqueio,dia_inteiro,data_final, DATEDIFF(data_final,data) as dataDiferenca,hora_final,
                videoconf_desabilitado,motivo_bloqueio
                FROM dias_horarios_bloqueados  WHERE $sql   AND doutores_id = '$doutores_id'  ORDER BY id ASC");
        return $qr;
    }

    public function bloqueioRapidoAgenda($idDominio, $dadosBloqueio) {
        $dadosBloqueio['identificador'] = $idDominio;
        $dadosBloqueio['lancado_config_bloqueio'] = 1;
        $dadosBloqueio['administradores_id_cad'] = auth('clinicas')->user()->id;
        $dadosBloqueio['data_cad'] = date('Y-m-d H:i');

//        if (auth('clinicas')->user()->id == 4055) {
//            dd($dadosBloqueio);
//        }
        ////Bloqueio novamente os horários desbloqueados dentro do período
        if (!isset($dadosBloqueio['dia_inteiro']) or empty($dadosBloqueio['dia_inteiro'])) {


            $camposUpdate['status_bloqueio'] = 1;
            $whereUPdate = '';
            if (isset($dadosBloqueio['dataFim']) and ! empty($dadosBloqueio['dataFim'])) {
                $whereUPdate = " AND data >= '{$dadosBloqueio['data']}'  AND data <= '{$dadosBloqueio['dataFim']}' ";
            } else {
                $whereUPdate = " AND data = '{$dadosBloqueio['data']}' ";
            }

            if (isset($dadosBloqueio['hora_final']) and ! empty($dadosBloqueio['hora_final'])) {
                $whereUPdate .= " AND horario >= '{$dadosBloqueio['horario']}'  AND horario < '{$dadosBloqueio['hora_final']}' ";
            } else {
                $whereUPdate .= " AND horario >='{$dadosBloqueio['horario']}' AND horario <= '{$dadosBloqueio['horario']}' ";
            }

            $this->updateDB('dias_horarios_bloqueados', $camposUpdate, " identificador = $idDominio    AND doutores_id = " . $dadosBloqueio['doutores_id'] . $whereUPdate, null, 'clinicas');
        }

        $verificaBLoqueio = $this->verificaHoraioBloqueio($idDominio, $dadosBloqueio['data'], $dadosBloqueio['horario'], $dadosBloqueio['doutores_id'],  $dadosBloqueio['area_bloqueio'], $dadosBloqueio['dia_inteiro']);
    
        if ($verificaBLoqueio) {
            $qr = $verificaBLoqueio->id;
            $this->updateDB('dias_horarios_bloqueados', $dadosBloqueio, " identificador =$idDominio AND id = $verificaBLoqueio->id LIMIT 1  ", null, 'clinicas');
        } else {
            $qr = $this->insertDB('dias_horarios_bloqueados', $dadosBloqueio, null, 'clinicas');
        }

//           $qr = $this->insertDB('dias_horarios_bloqueados', $dadosBloqueio, null, 'clinicas');
        return $qr;
    }

    public function insertHistoricoTransferencia($idDominio, Array $dadosInsert) {

        $qr = $this->insertDB('dias_horarios_bloqueados_consultas_transfer', $dadosInsert, null, 'clinicas');
        return $qr;
    }

    public function verificaHoraioBloqueio($idDominio, $data, $horario, $doutores_id, $areaBloqueio, $diaInteiro = null) {

        $sqlDiaInteiro = '';
        if (!empty($diaInteiro)) {
            $sqlDiaInteiro = " AND dia_inteiro = $diaInteiro";
        }
        $qr = $this->connClinicas()->select("SELECT * FROM dias_horarios_bloqueados AS A WHERE A.identificador = $idDominio AND A.data = '$data' AND A.doutores_id= $doutores_id AND A.horario = '$horario'
            AND  A.area_bloqueio = $areaBloqueio AND A.hora_final IS NULL $sqlDiaInteiro
            ");

        if (count($qr) > 0) {
            return $qr[0];
        } else {
            return false;
        }
    }

    public function insertBloqueio($idDominio, $data, $horario, $doutores_id, $areaBloqueio, $statusBloqueio) {

        $campos['data'] = $data;
        $campos['horario'] = $horario;
        $campos['doutores_id'] = $doutores_id;
        $campos['area_bloqueio'] = $areaBloqueio;
        $campos['identificador'] = $idDominio;
        $campos['status_bloqueio'] = $statusBloqueio;
        $qr = $this->insertDB('dias_horarios_bloqueados', $campos, null, 'clinicas');
        return $qr;
    }

    public function updateDiasHorariosBloqueados($idDominio, $idDiasHorariosBloqueados, $statusBloqueio, $areaBloqueio, $doutorId) {

        $dados['status_bloqueio'] = $statusBloqueio;
        $dados['area_bloqueio'] = $areaBloqueio;

//      dd($idDiasHorariosBloqueados);
        $qr = $this->updateDB('dias_horarios_bloqueados', $dados, " identificador = $idDominio AND id = '$idDiasHorariosBloqueados' LIMIT 1", null, 'clinicas');
    }

}
