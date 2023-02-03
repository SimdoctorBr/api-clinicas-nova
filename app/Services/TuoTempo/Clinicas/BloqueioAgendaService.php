<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\TuoTempo\Clinicas;

use App\Services\BaseService;
use App\Repositories\Gerenciamento\DominioRepository;
use App\Repositories\Clinicas\DiasHorariosBloqueadosRepository;

/**
 * Description of Activities
 *
 * @author ander
 */
class BloqueioAgendaService extends BaseService {

    private $diasHorariosBloqueadosRepository;

    public function __construct(DiasHorariosBloqueadosRepository $diasHorRep, DominioRepository $domRep) {
        $this->diasHorariosBloqueadosRepository = $diasHorRep;
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
                                $retorno[$row->data][substr($row->horario, 0, 5)]['minisite'] = $row->status_bloqueio;
                                $retorno[$row->data][substr($row->horario, 0, 5)]['videoconf_desabilitado'] = $row->videoconf_desabilitado;
                                $retorno[$row->data][substr($row->horario, 0, 5)]['agenda'] = $row->status_bloqueio;
                                $retorno[$row->data][substr($row->horario, 0, 5)]['hora_final'] = substr($row->hora_final, 0, 5);
                                $retorno[$row->data][substr($row->motivo_bloqueio, 0, 5)]['motivo_bloqueio'] = $row->motivo_bloqueio;
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

}
