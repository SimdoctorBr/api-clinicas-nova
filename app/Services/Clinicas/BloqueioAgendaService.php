<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\Clinicas;

use App\Services\BaseService;
use App\Repositories\Clinicas\DiasHorariosBloqueadosRepository;
use App\Repositories\Clinicas\StatusRefreshRepository;
use App\Repositories\Clinicas\ConsultaRepository;
use App\Services\Clinicas\ConsultaService;
use App\Services\Clinicas\DefinicaoHorarioService;

/**
 * Description of Activities
 *
 * @author ander
 */
class BloqueioAgendaService extends BaseService {

    private $diasHorariosBloqueadosRepository;

    public function __construct() {
        $this->diasHorariosBloqueadosRepository = new DiasHorariosBloqueadosRepository;
    }

    /**
     * Verifica bolqueios na agenda ou minisite
     * @param type $data Formato: AAAA-MM-DD
     * @param type $horario  Formato: 00:00
     * @param type $doutores_id
     * @param type $area_bloqueio 1- Minisite, 2 - Agenda
     * @return type
     */
    public function verificaBloqueio($idDominio, $data, $horario, $doutores_id, $area_bloqueio, $retornaArray = false, $dataTermino = null) {

        if ($retornaArray) {
            $qr = $this->diasHorariosBloqueadosRepository->getByDoutores($idDominio, $doutores_id, $data, $dataTermino);
            $retorno = null;
            if (count($qr) > 0) {

                $contConfiglancado = 0;
                foreach ($qr as $row) {

                    switch ($row->area_bloqueio) {
                        case 1: $area_bloqueio = 'minisite';
                            break;
                        case 2: $area_bloqueio = 'agenda';
                            break;
                    }

                    ///VErifica bloqueios lanacdos pela agenda
                    if ($row->lancado_config_bloqueio == 1) {

                        if ($row->dia_inteiro == 1) {

                            //// Caso o bloqueio seja por periodo
                            if (!empty($row->data_final)) {
                                $data = strtotime($row->data);
                                for ($i = 0; $i <= $row->dataDiferenca; $i++) {
                                    $dataFormat = date('Y-m-d', $data);
                                    $retorno[$dataFormat]['id'] = $row->id;
                                    $retorno[$dataFormat]['dia_inteiro'] = $row->dia_inteiro;
                                    $retorno[$dataFormat]['data_ini'] = $row->data;
                                    $retorno[$dataFormat]['data_final'] = $row->data_final;
                                    $retorno[$dataFormat]['dia_status_bloqueio'] = $row->status_bloqueio;
                                    $retorno[$dataFormat]['videoconf_desabilitado'] = (int) $row->videoconf_desabilitado;
                                    $retorno[$dataFormat]['motivo_bloqueio'] = $row->motivo_bloqueio;
                                    $data += 86400;
                                }
                            } else {
                                $retorno[$row->data]['id'] = $row->id;
                                $retorno[$row->data]['dia_inteiro'] = $row->dia_inteiro;
                                $retorno[$row->data]['dia_status_bloqueio'] = $row->status_bloqueio;
                                $retorno[$row->data]['motivo_bloqueio'] = $row->motivo_bloqueio;
                            }
                            ///////////////////////

                            $contConfiglancado++;
                        } else {

                            //Caso exista uma hora fila
                            if (!empty($row->hora_final)) {

                                $data = strtotime($row->data);
                                for ($i = 0; $i <= $row->dataDiferenca; $i++) {
                                    $dataFormat = date('Y-m-d', $data);
                                    $retorno[$dataFormat][substr($row->horario, 0, 5)]['minisite'] = $row->status_bloqueio;
                                    $retorno[$dataFormat][substr($row->horario, 0, 5)]['videoconf_desabilitado'] = $row->videoconf_desabilitado;
                                    $retorno[$dataFormat][substr($row->horario, 0, 5)]['agenda'] = $row->status_bloqueio;
                                    $retorno[$dataFormat][substr($row->horario, 0, 5)]['hora_final'] = substr($row->hora_final, 0, 5);
                                   $retorno[$dataFormat][substr($row->horario, 0, 5)]['motivo_bloqueio'] = $row->motivo_bloqueio;

                                    $data += 86400;
                                }
                            } else {
                                $retorno[$row->data][substr($row->horario, 0, 5)]['minisite'] = $row->status_bloqueio;
                                $retorno[$row->data][substr($row->horario, 0, 5)]['videoconf_desabilitado'] = (int) $row->videoconf_desabilitado;
                                $retorno[$row->data][substr($row->horario, 0, 5)]['agenda'] = $row->status_bloqueio;
                                $retorno[$row->data][substr($row->horario, 0, 5)]['motivo_bloqueio'] = $row->motivo_bloqueio;
                            }
                            //////
                        }
                    } else {
                        $retorno[$row->data][substr($row->horario, 0, 5)][$area_bloqueio] = $row->status_bloqueio;
                        $retorno[$row->data][substr($row->horario, 0, 5)]['videoconf_desabilitado'] = (int) $row->videoconf_desabilitado;
                        if ($row->status_bloqueio == 2) {
                            $retorno['desbloqueados'][$row->data][substr($row->horario, 0, 5)][$area_bloqueio] = $row->status_bloqueio;
                            $retorno['desbloqueados'][$row->data][substr($row->horario, 0, 5)]['videoconf_desabilitado'] = (int) $row->videoconf_desabilitado;
                        }
                    }
                }
            }

            return $retorno;
        } else {

            ///VErifica se a data e o horário esta bloqueado
            $qrDiasHorariosBloqueio = $this->diasHorariosBloqueadosRepository->getByDoutores($idDominio, $doutores_id, $data, null, $horario, $area_bloqueio);

            return $qrDiasHorariosBloqueio;
        }
    }

    public function bloqueioRapidoAgenda($idDominio, $doutorId, Array $dadosInput) {

        $DefinicaoHorarioService = new DefinicaoHorarioService();
        $StatusRefreshRepository = new StatusRefreshRepository();
        $ConsultaService = new ConsultaService;
        $ConsultaRepository = new ConsultaRepository();
        $DiasHorariosBloqueadosRepository = new DiasHorariosBloqueadosRepository;


        $dadosBuscaConsulta = null;
        $dadosBuscaConsulta['dataInicio'] = $dadosInput['data'];
        $dadosBuscaConsulta['doutoresId'] = $doutorId;

        $dataFinal = null;
        $doutorDestinoId = (isset($dadosInput['doutorIdDestino']) and ! empty($dadosInput['doutorIdDestino'])) ? $dadosInput['doutorIdDestino'] : null;
        $dadosBloqueio['doutores_id'] = $doutorId;
        $dadosBloqueio['data'] = $dadosInput['data'];
        $dadosBloqueio['motivo_bloqueio'] = (isset($dadosInput['motivoBloqueio']) and ! empty($dadosInput['motivoBloqueio'])) ? $dadosInput['motivoBloqueio'] : null;

        $dadosBloqueio['area_bloqueio'] = (isset($dadosBloqueio['areaBloqueio']) and ! !empty($dadosBloqueio['areaBloqueio'])) ? $dadosBloqueio['areaBloqueio'] : 2;



        if (isset($dadosInput['dataFim']) and ! empty($dadosInput['dataFim'])) {
            $dadosBloqueio['data_final'] = $dadosInput['dataFim'];
            $dataFinal = $dadosBloqueio['data_final'];
            $dadosBuscaConsulta['dataFim'] = $dadosInput['dataFim'];
        }

        if (isset($dadosInput['diaInteiro']) and $dadosInput['diaInteiro'] == 1) {
            $dadosBloqueio['dia_inteiro'] = $dadosInput['diaInteiro'];
        } else {

            $dadosBuscaConsulta['hora'] = $dadosInput['hora'];

            $dadosBloqueio['dia_inteiro'] = 0;
            $dadosBloqueio['horario'] = $dadosInput['hora'];
            if (isset($dadosInput['horaFim']) and ! empty($dadosInput['horaFim'])) {
                $dadosBloqueio['hora_final'] = $dadosInput['horaFim'];
                $dadosBuscaConsulta['horaFim'] = $dadosInput['horaFim'];
            }
        }


        $verificaBloqueios = $this->verificaBloqueio($idDominio, $dadosBloqueio['data'], null, $dadosBloqueio['doutores_id'], 2, true, $dataFinal);

        if (isset($verificaBloqueios[$dadosBloqueio['data']])
                and ( isset($verificaBloqueios[$dadosBloqueio['data']]['dia_inteiro']) and $verificaBloqueios[$dadosBloqueio['data']]['dia_inteiro'] == 1)
                and ( empty($dadosBloqueio['dataFim'])
                )
        ) {
            return $this->returnError(null, 'Já existe um bloqueio para este dia inteiro');
        }

        $idBloqueio = $DiasHorariosBloqueadosRepository->bloqueioRapidoAgenda($idDominio, $dadosBloqueio);


//        if(auth('clinicas')->user()->id = 4055){
//            dd($idBloqueio);
//        }
//        $idBloqueio = true;
        if ($idBloqueio) {


            //transferindo consultas
            if (!empty($doutorDestinoId)) {


                $qrConsultas = $ConsultaRepository->getAll($idDominio, $dadosBuscaConsulta);


                if (count($qrConsultas) > 0) {
                    foreach ($qrConsultas as $rowConsulta) {

                        $hora_consulta = substr($rowConsulta->hora_consulta, 0, 5);

                        $qrHorariosAgenda = $DefinicaoHorarioService->getNovoHorarios($idDominio, $doutorDestinoId, 1, $rowConsulta->data_consulta);

                        if (isset($qrHorariosAgenda['horarios']) and count($qrHorariosAgenda['horarios']) > 0) {

                            //verificando se existe o horario da consulta na agenda do doutor de destino
                            $arrayHorarios = array_column($qrHorariosAgenda['horarios'], 'horario');
                            $indiceArrayHorarios = array_search($hora_consulta, $arrayHorarios);

                            if ($indiceArrayHorarios) {

                                $AR_horaAgenda = $qrHorariosAgenda['horarios'][$indiceArrayHorarios];
                                $statusHorario = $AR_horaAgenda['status'];
                                $horaAgenda = $AR_horaAgenda['horario'];
                                $VerificaBloqueioHorarios = $this->verificaBloqueio($rowConsulta->data_consulta, $hora_consulta, $doutorDestinoId, 2, true);

                                if (isset($VerificaBloqueioHorarios[$rowConsulta->data_consulta])) {
                                    ///verifica Bloqueio dia inteiro
                                    if (
                                            $VerificaBloqueioHorarios[$rowConsulta->data_consulta]['dia_inteiro'] == 1 and $VerificaBloqueioHorarios[$rowConsulta->data_consulta]['diaStatusBloqueio'] == 1) {
//                                    $retorno['bloqueado'] = true;
                                        continue;
                                    }

                                    //verificando hora
                                    $bloqueadoHora = false;

                                    foreach ($VerificaBloqueioHorarios[$rowConsulta->data_consulta] as $chaveHora => $dadosHorario) {
                                        //se o bloqueio for um intervalo de hora
                                        if (!empty($dadosHorario['hora_final']) and $horaAgenda >= $chaveHora and $horaAgenda <= $dadosHorario['hora_final']) {
                                            $bloqueadoHora = true;
                                        } elseif ($chaveHora == $horaAgenda and $dadosHorario['agenda'] == 1) {
                                            $bloqueadoHora = true;
                                        }
                                    }

                                    if ($bloqueadoHora) {
                                        continue;
                                    }
                                }


                                $VerificaBloqueioAgenda = (isset($VerificaBloqueioHorarios[$rowConsulta->data_consulta][$horaAgenda]['agenda'])) ? $VerificaBloqueioHorarios[$rowConsulta->data_consulta][$horaAgenda]['agenda'] : '';
                                $horarioDisponivel = $ConsultaService->verificaDisponibilidadeConsultasHorario($idDominio, $doutorDestinoId, $rowConsulta->data_consulta, $hora_consulta);


                                if ($horarioDisponivel and $statusHorario == 1
                                        and ( (empty($VerificaBloqueioAgenda))or $VerificaBloqueioAgenda == 2)
                                ) {
                                    $ConsultaService->transferirConsultaDoutor($idDominio, $idBloqueio, $rowConsulta->id, $doutorDestinoId, $rowConsulta->data_consulta, $rowConsulta->hora_consulta);
                                }

//                         echo $horabloq;
                            }
                        }
                    }
                }


                $qrConsultas = $ConsultaRepository->getAll($idDominio, $dadosBuscaConsulta);

//                dd($dadosBuscaConsulta);
//                dd($qrConsultas);
                if (count($qrConsultas) > 0) {
                    return $this->returnSuccess(null, "Bloqueado com sucesso, porém não foi possível transferir algumas consultas por motivo de limite de consulta atigindo ou horário bloqueado.");
                }


                $StatusRefreshRepository->insertAgenda($idDominio, $doutorDestinoId);
            }
        } else {
            return $this->returnError(null, 'Ocorreu um erro ao bloquear a agenda');
        }

        $StatusRefreshRepository->insertAgenda($idDominio, $doutorId);
        return $this->returnSuccess('', 'Bloqueado com sucesso');
    }

    public function desbloqueiaHorario($idDominio, $doutorId, $dadosInput) {

        $StatusRefreshRepository = new StatusRefreshRepository();
        $DefinicaoHorarioService = new DefinicaoHorarioService;
        $DiasHorariosBloqueadosRepository = new DiasHorariosBloqueadosRepository;



        if (isset($dadosInput['horaFim']) and ! empty($dadosInput['horaFim'])) {

            $qrHorariosAgenda = $DefinicaoHorarioService->getNovoHorarios($idDominio, $doutorId, 1, $dadosInput['data']);

            if ($qrHorariosAgenda) {
                foreach ($qrHorariosAgenda['horarios'] as $rowHorario) {
                    if (strtotime($rowHorario['horario']) >= strtotime($dadosInput['hora']) and strtotime($rowHorario['horario']) <= strtotime($dadosInput['horaFim'])) {
                        $qntHorariosDesbloq[] = $rowHorario['horario'];
                    }
                }
            } else {
                return $this->returnError(null, 'Horário inexistente');
            }
        } else {
            $qntHorariosDesbloq[] = $dadosInput['hora'];
        }

//        dd($qntHorariosDesbloq);
        //verificar se o hoário  existe
        foreach ($qntHorariosDesbloq as $horario) {

            $rowVerifica = $DiasHorariosBloqueadosRepository->verificaHoraioBloqueio($idDominio, $dadosInput['data'], $horario, $doutorId, $dadosInput['areaBloqueio']);
            if (!$rowVerifica) {
                $DiasHorariosBloqueadosRepository->insertBloqueio($idDominio, $dadosInput['data'], $horario, $doutorId, $dadosInput['areaBloqueio'], 2);
            } else {
                $DiasHorariosBloqueadosRepository->updateDiasHorariosBloqueados($idDominio, $rowVerifica->id, 2, $dadosInput['areaBloqueio'], $doutorId);
            }
        }

        $StatusRefreshRepository->insertAgenda($idDominio, $doutorId);
        return $this->returnSuccess('', 'Desbloqueado com sucesso');
    }

}
