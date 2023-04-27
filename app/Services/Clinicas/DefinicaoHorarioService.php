<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\Clinicas;

use App\Services\BaseService;
use App\Repositories\Clinicas\DefinicaoHorarioRepository;
use App\Repositories\Clinicas\DefinicaoHorariosAdicionaisRepository;
use App\Repositories\Clinicas\DiasAtendimentoRepository;

/**
 * Description of Activities
 *
 * @author ander
 */
class DefinicaoHorarioService extends BaseService {

    private $definicaoHorarioRepository;
    private $definicaoHorarioAdicionalRepository;
    private $diasAtendimentoRepository;

    public function __construct() {
        $this->definicaoHorarioRepository = new DefinicaoHorarioRepository;
        $this->definicaoHorarioAdicionalRepository = new DefinicaoHorariosAdicionaisRepository;
        $this->diasAtendimentoRepository = new DiasAtendimentoRepository;
    }

    /**
     * 
     * @param type $identificador
     * @param type $doutor_id
     * @param type $tipoConsulta 1 -'data', 2 - 'id_definicao'
     * @param type $valorTipoConsulta  Valor da consulta, caso o campo '$tipoConsulta' seja igual a 'data', este campo deve ser uma data no formato YYYY-MM-DD,
     *                                 Caso caso o campo '$tipoConsulta' seja igual a 'id_definicao',este campo de ser o id da definição de horário
     * @param type $todosDiasDisponiveis  - Trazer todos os dias da semana disponíveis para esta configuração
     * @param type $diaSemanaId - Caso a variavel $todosDiasDisponiveis seja 'false', informar o id do dia da semana desejado
     * @return type
     */
    public function getNovoHorarios($idDominio, $doutor_id, $tipoConsulta, $valorTipoConsulta, $todosDiasDisponiveis = false, $diaSemanaId = null) {

        $DIAS_HORARIOS = null;
        $HORARIOS_ADICIONAR = $HORARIOS_REMOVER = array();

        //PEgando as configurações de horarios de acordo com o tipo
        if ($tipoConsulta == 1) {
            $data = $valorTipoConsulta;
            $diaSemanaId = date('w', strtotime($data));
            $diaSemanaId = ($diaSemanaId == 0) ? 7 : $diaSemanaId;
            $verificaHorario = $this->definicaoHorarioRepository->verificaDefinicoesHorariosGerenciamento($idDominio, $doutor_id, $data);
        } elseif ($tipoConsulta == 2) {
            //Pegando os horarios da configuração padrao
            $defHorarioId = $valorTipoConsulta;
//            $verificaHorario = $this->getConfiguracoesHorariosById($idDominio, $defHorarioId);
        }


        if ($verificaHorario == null) {
            return null;
        }
        if (!$todosDiasDisponiveis) { //Pegando somente um dia
            $rowDiaAtendimento = $this->diasAtendimentoRepository->getByDoutores($idDominio, $doutor_id, $diaSemanaId, $verificaHorario->id);
            if (count($rowDiaAtendimento) == 0) {
                return null;
            }
        }
        $horariosPadrao = $this->gerarHorariosAgenda($verificaHorario->abertura, $verificaHorario->fechamento, $verificaHorario->intervalo);

        //horarios adicionais se o status for 1 adiciona se for 0 remover
        //No gerenciamneto de horarios, quando excluir um horário, adicionar, caso não exista, na tabela horarios_adicionais, com o status =0
        //VErificando horarios adicionados e/ou removidos
        $qrHOrariosAdicionais = $this->definicaoHorarioAdicionalRepository->getByIdDefinicoHorario($verificaHorario->id);
        if (count($qrHOrariosAdicionais) > 0) {
            $contAdd = $contRemove = 0;
            foreach ($qrHOrariosAdicionais as $rowHorarioAdiconal) {
                $hr = substr($rowHorarioAdiconal->horario, 0, 5);
                if (in_array($rowHorarioAdiconal->status, array(1, 2))) {
                    $HORARIOS_ADICIONAR[$rowHorarioAdiconal->dias_da_semana_id][$contAdd]['horario'] = $hr;
                    $HORARIOS_ADICIONAR[$rowHorarioAdiconal->dias_da_semana_id][$contAdd]['status'] = $rowHorarioAdiconal->status;
                    $HORARIOS_ADICIONAR[$rowHorarioAdiconal->dias_da_semana_id][$contAdd]['video_desabilitado'] = $rowHorarioAdiconal->video_desabilitado;
                    $contAdd++;
                } else {
                    $HORARIOS_REMOVER[$rowHorarioAdiconal->dias_da_semana_id][$hr]['horario'] = $hr;
                    $HORARIOS_REMOVER[$rowHorarioAdiconal->dias_da_semana_id][$hr]['status'] = $rowHorarioAdiconal->status;
                    $HORARIOS_REMOVER[$rowHorarioAdiconal->dias_da_semana_id][$hr]['video_desabilitado'] = $rowHorarioAdiconal->video_desabilitado;
                }
            }
        }


        if (!$todosDiasDisponiveis) { //Pegando somente um dia
//              sort($HORARIOS_ADICIONAR[$rowDiaAtendimento->dias_da_semana_id]);//orcdenando horários
//            sort($HORARIOS_REMOVER[$rowDiaAtendimento->dias_da_semana_id]); //orcdenando horários


            if (count($rowDiaAtendimento) > 0) {


                $arrayAdicionar = (isset($HORARIOS_ADICIONAR[$rowDiaAtendimento[0]->dias_da_semana_id])) ? $HORARIOS_ADICIONAR[$rowDiaAtendimento[0]->dias_da_semana_id] : [];
                $arrayRemover = (isset($HORARIOS_REMOVER[$rowDiaAtendimento[0]->dias_da_semana_id])) ? $HORARIOS_REMOVER[$rowDiaAtendimento[0]->dias_da_semana_id] : [];
                $horarioDia = $this->addRemoverHorarioPadrao($horariosPadrao, $arrayAdicionar, $arrayRemover);

                $DIAS_HORARIOS['abertura'] = $verificaHorario->abertura;
                $DIAS_HORARIOS['fechamento'] = $verificaHorario->fechamento;
                $DIAS_HORARIOS['sala'] = $verificaHorario->sala;
                $DIAS_HORARIOS['intervalo'] = $verificaHorario->intervalo;
                $DIAS_HORARIOS['dia_em_php'] = $rowDiaAtendimento[0]->dia_em_php;
                $DIAS_HORARIOS['dias_da_semana_id'] = $rowDiaAtendimento[0]->dias_da_semana_id;
                $DIAS_HORARIOS['nomeDia'] = html_entity_decode($rowDiaAtendimento[0]->nomeDia);
                $DIAS_HORARIOS['definicao_horario_id'] = $verificaHorario->id;
                $DIAS_HORARIOS['horarios'] = $horarioDia;
                $DIAS_HORARIOS['almocoDe'] = $verificaHorario->almocoDe;
                $DIAS_HORARIOS['almocoAte'] = $verificaHorario->almocoAte;
                $DIAS_HORARIOS['possui_almoco'] = $verificaHorario->possui_almoco;
            }
        } else {
            //Pegando todos os dia disponiveis
            //pegando dias que o doutor atende;
            $qrDiasAtendimento = $objDiasAtendimento->getByDoutoresId($idDominio, $doutor_id);

            //Criar o array de saida com os dias e horarios
            if ($qrDiasAtendimento->numero_linhas > 0) {
                $cont = 0;
                foreach ($qrDiasAtendimento->rows as $rowDiaAtendimento) {


//                    sort($HORARIOS_ADICIONAR[$rowDiaAtendimento->dias_da_semana_id]);//orcdenando horários
//                    sort($HORARIOS_REMOVER[$rowDiaAtendimento->dias_da_semana_id]);//orcdenando horários

                    $DIAS_HORARIOS[$cont]['abertura'] = $verificaHorario->abertura;
                    $DIAS_HORARIOS[$cont]['fechamento'] = $verificaHorario->fechamento;
                    $DIAS_HORARIOS[$cont]['intervalo'] = $verificaHorario->intervalo;
                    $DIAS_HORARIOS[$cont]['dia_em_php'] = $rowDiaAtendimento->dia_em_php;
                    $DIAS_HORARIOS[$cont]['dias_da_semana_id'] = $rowDiaAtendimento->dias_da_semana_id;
                    $DIAS_HORARIOS[$cont]['definicao_horario_id'] = $verificaHorario->id;
                    $DIAS_HORARIOS[$cont]['almocoDe'] = $verificaHorario->almocoDe;
                    $DIAS_HORARIOS[$cont]['almocoAte'] = $verificaHorario->almocoAte;
                    $DIAS_HORARIOS[$cont]['possui_almoco'] = $verificaHorario->possui_almoco;

//  print_r($this->addRemoverHorarioPadrao($horariosPadrao, $HORARIOS_ADICIONAR[$rowDiaAtendimento->dias_da_semana_id], $HORARIOS_REMOVER[$rowDiaAtendimento->dias_da_semana_id]));
                    $horarioDia = $this->addRemoverHorarioPadrao($horariosPadrao, $HORARIOS_ADICIONAR[$rowDiaAtendimento->dias_da_semana_id], $HORARIOS_REMOVER[$rowDiaAtendimento->dias_da_semana_id]);

//                    if ($rowDiaAtendimento->dias_da_semana_id == 1) {
//                        echo '<pre>';
//                        print_r($HORARIOS_ADICIONAR[$rowDiaAtendimento->dias_da_semana_id]);
//
//                        echo '</pre>';
//                        echo '<pre>';
//                        print_r($horarioDia);
//                        echo '</pre>';
//                    }

                    $DIAS_HORARIOS[$cont]['horarios'] = $horarioDia;
                    $cont++;
                }
            }
        }


        return $DIAS_HORARIOS;
    }

    public function gerarHorariosAgenda($inicio, $termino, $intervalo) {

        $HoraInicio = explode(':', $inicio);
        $HoraTermino = explode(':', $termino);

        $inicio = mktime($HoraInicio[0], $HoraInicio[1], 0);
        $termino = mktime($HoraTermino[0], $HoraTermino[1], 0, date('m'), date('d'), date('Y'));
        $intervalo = $intervalo * 60;
        $cont = 0;
        $contHr = 0;
        $horarioAnt = $inicio;
        $HORARIOS[$contHr]['horario'] = date('H:i', $inicio);
        $HORARIOS[$contHr]['status'] = 1;
        while ($inicio <= $termino) {
            $horario = $horarioAnt + $intervalo;
            $horarioProx = $horario + $intervalo;

            $contHr++;
            $HORARIOS[$contHr]['horario'] = date('H:i', $horario);
            $HORARIOS[$contHr]['status'] = 1;
            $horarioAnt = $horario;

            if ($horario >= $termino) {
                break;
            }

            $cont++;
            if ($cont == 100) {
                break;
                exit;
            }
        }

        return $HORARIOS;
    }

    private function addRemoverHorarioPadrao($horariosPadrao, $HoraStatusAdicionar = [], $horaStatusRemover = []) {
        $HoraStatusAdicionar = (is_array($HoraStatusAdicionar)) ? $HoraStatusAdicionar : [];
        $horaStatusRemover = (is_array($horaStatusRemover)) ? $horaStatusRemover : [];

        $TotalHorarios = count($horariosPadrao);
        $primeiroHorario = $horariosPadrao[0];
        $ultimoHorario = $horariosPadrao[$TotalHorarios - 1];
        $conthrp = 0;
        $horarioDia = null;
        for ($i = 0; $i < $TotalHorarios; $i++) {
            $remover = false;

            if (isset($horaStatusRemover[$horariosPadrao[$i]['horario']])) {
                $remover = true;
            }
            if (!$remover) {
                $horarioDia[$conthrp]['horario'] = $horariosPadrao[$i]['horario'];
                $horarioDia[$conthrp]['status'] = $horariosPadrao[$i]['status'];
                $horarioDia[$conthrp]['video_desabilitado'] = isset($horariosPadrao[$i]['video_desabilitado']) ? $horariosPadrao[$i]['video_desabilitado'] : '';
            } else {
                $conthrp--;
            }

            foreach ($HoraStatusAdicionar as $cha => $rowHrAdd) {
//                
//                if ($horariosPadrao[$i]['horario'] == '17:00') {
//                    var_dump($ultimoHorario['horario'] == $horariosPadrao[$i]['horario'] and $rowHrAdd['horario'] > $ultimoHorario['horario']);
//                }

                if ($i == 0 and $rowHrAdd['horario'] < $horariosPadrao[$i]['horario'] and $rowHrAdd['horario'] != $horariosPadrao[$i]['horario']) {
                    $conthrp++;
                    $horarioDia[$conthrp]['horario'] = $rowHrAdd['horario'];
                    $horarioDia[$conthrp]['status'] = $rowHrAdd['status'];
                    $horarioDia[$conthrp]['video_desabilitado'] = $rowHrAdd['video_desabilitado'];
                    sort($horarioDia);
                }

//                var_dump($i + 1);
//                var_dump($rowHrAdd['horario'] < $horariosPadrao[$i + 1]['horario']);
                if ($rowHrAdd['horario'] > $horariosPadrao[$i]['horario']
                        and ( isset($horariosPadrao[$i + 1]['horario']) and $rowHrAdd['horario'] < $horariosPadrao[$i + 1]['horario'])
                ) {
                    $conthrp++;
                    $horarioDia[$conthrp]['horario'] = $rowHrAdd['horario'];
                    $horarioDia[$conthrp]['status'] = $rowHrAdd['status'];
                    $horarioDia[$conthrp]['video_desabilitado'] = $rowHrAdd['video_desabilitado'];
                } elseif ($rowHrAdd['horario'] == $horariosPadrao[$i]['horario']) {

                    foreach ($horarioDia as $chave => $horaDia) {
                        if ($horaDia['horario'] == $horariosPadrao[$i]['horario']) {
                            $horarioDia[$chave]['horario'] = $rowHrAdd['horario'];
                            $horarioDia[$chave]['status'] = $rowHrAdd['status'];
                            $horarioDia[$chave]['video_desabilitado'] = $rowHrAdd['video_desabilitado'];
                            break;
                        }
                    }
                }
                if ($ultimoHorario['horario'] == $horariosPadrao[$i]['horario'] and $rowHrAdd['horario'] > $ultimoHorario['horario']) {

                    $conthrp++;
                    $horarioDia[$conthrp]['horario'] = $rowHrAdd['horario'];
                    $horarioDia[$conthrp]['status'] = $rowHrAdd['status'];
                    $horarioDia[$conthrp]['video_desabilitado'] = $rowHrAdd['video_desabilitado'];
                }
            }
            $conthrp++;
        }
        if ($horarioDia != null) {
            sort($horarioDia);
        }


        unset($conthrp);
        return $horarioDia;
    }

}
