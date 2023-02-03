<?php

namespace App\Repositories\Clinicas;

use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;
use stdClass;

class DefinicaoHorarioRepository extends BaseRepository {

    /**
     * verificando as configuraçcoes de horários do DOutor(a)
     * @param type $identificador
     * @param type $doutor_id
     * @param type $data
     * @return \App\Repositories\Clinicas\stdClass
     */
    public function verificaDefinicoesHorariosGerenciamento($identificador, $doutor_id = NULL, $data = NULL, $idSala = null) {

        $qTeste = $this->connClinicas()->select("SELECT A.*, B.sala_nome
                                            FROM definicoes_horarios AS A
                                            LEFT JOIN salas as B
                                            ON A.salas_id = B.idSala
                                            WHERE A.identificador = $identificador AND A.doutores_id = $doutor_id AND
                                             (
                                             	(A.data_alter_horario <= '$data' and A.data_termino_horario is null OR A.data_alter_horario IS NULL)
                                            	  OR
                                            	  (A.data_alter_horario <= '$data' AND A.data_termino_horario >= '$data'))
                                            ORDER BY A.data_alter_horario DESC");
        $objNovo = new stdClass();

        $DadosDefinicoes = null;
        $exitLoop = false;

        foreach ($qTeste as $row) {
            if ($data == $row->data_alter_horario and empty($row->data_termino_horario)
                    or ( $data == $row->data_alter_horario and $row->data_termino_horario == $data )
                    and ( empty($idSala) or ( !empty($idSala) and $idSala == $row->salas_id))
            ) {
                $DadosDefinicoes = $row;
                $exitLoop = true;
                break;
            } else if ($data >= $row->data_alter_horario and $data <= $row->data_termino_horario and ! empty($row->data_termino_horario)
                    and ( empty($idSala) or ( !empty($idSala) and $idSala == $row->salas_id))
            ) {
                $DadosDefinicoes = $row;
                $exitLoop = true;
                break;
            } elseif ($row->data_alter_horario <= $data and empty($row->data_termino_horario)
                    and ( empty($idSala) or ( !empty($idSala) and $idSala == $row->salas_id))
            ) {
                $DadosDefinicoes = $row;
                $qrVerficaPeriodo = $this->connClinicas()->select("SELECT *   FROM definicoes_horarios AS A WHERE
                                            identificador = $identificador AND doutores_id = $doutor_id AND
                                             (                            
                                                  (A.data_alter_horario <= '$data'  AND A.data_termino_horario >= '$data')
                                                )
                                             ORDER BY data_alter_horario DESC LIMIT 1 ;");
                if (count($qrVerficaPeriodo) == 0) {
                    $exitLoop = true;
                }
            }

            if ($exitLoop) {
                break;
            }
        }


        if (count($qTeste) > 0) {
            $rowDEFNovo = $qTeste[0];
            if (empty($rowDEFNovo->data_alter_horario)) {
                $objNovo->sqlDefHorario = "AND (horarios.definicoes_horarios_id IS NULL  OR  horarios.definicoes_horarios_id = '{$DadosDefinicoes->id}') ";
            } else {
                $objNovo->sqlDefHorario = "AND  horarios.definicoes_horarios_id = '{$DadosDefinicoes->id}' ";
            }


            $objNovo->id = $DadosDefinicoes->id;
            $objNovo->abertura = $DadosDefinicoes->abertura;
            $objNovo->sala = (!empty($DadosDefinicoes->salas_id)) ? ['id' => $DadosDefinicoes->salas_id, 'nome' => $DadosDefinicoes->sala_nome] : '';
            $objNovo->fechamento = $DadosDefinicoes->fechamento;
            $objNovo->intervalo = $DadosDefinicoes->intervalo;
            $objNovo->data_alter_horario = $DadosDefinicoes->data_alter_horario;
            $objNovo->horario_unico = true;
            $objNovo->almocoDe = $DadosDefinicoes->almocoDe;
            $objNovo->almocoAte = $DadosDefinicoes->almocoAte;
            $objNovo->possui_almoco = $DadosDefinicoes->possui_almoco;

            return $objNovo;
        } else {
            return null;
        }
    }

}
