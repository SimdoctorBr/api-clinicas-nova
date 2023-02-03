<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\TuoTempo\Clinicas;

use App\Services\BaseService;
use App\Repositories\Gerenciamento\DominioRepository;
use App\Services\TuoTempo\Clinicas\DefinicaoHorarioService;
use App\Repositories\Clinicas\DoutorRepository;
use App\Helpers\Functions;
use App\Services\TuoTempo\Clinicas\BloqueioAgendaService;
use App\Services\TuoTempo\Clinicas\ConsultaService;
use App\Repositories\Clinicas\ConsultaRepository;
use App\Repositories\Clinicas\ConsultaStatusRepository;
use App\Repositories\Clinicas\Paciente\PacienteRepository;
use App\Repositories\Clinicas\ConvenioRepository;
use App\Repositories\Clinicas\ProcedimentosRepository;
use App\Repositories\Clinicas\StatusRefreshRepository;
use App\Repositories\Clinicas\RecebimentosRepository;
use App\Repositories\Clinicas\FinanceiroFornecedorRepository;
use App\Repositories\Clinicas\AgendaFilaEsperaRepository;
use DateTime;
use App\Services\Clinicas\CalculosService;

/**
 * Description of Activities
 *
 * @author ander
 */
class AgendaService extends BaseService {

    private $agendaRepository;
    private $dominioRepository;
    private $definicaoHorarioService;
    private $doutorRepository;
    private $bloqueioAgendaService;
    private $consultaRepository;
    private $pacienteRepository;
    private $convenioRepository;
    private $consultaService;
    private $procedimentosRepositoy;
    private $statusRefreshRepository;
    private $msgErroStatus = [
        'jaFoiAtendido' => 'The patient has already been seen',
        'estaSendoAtendido' => 'The patient is being treated',
        'desmarcado' => 'The appointment has already been unmark',
        'faltou' => 'The patient was absent',
        'jaSeEncontra' => 'Patient is already in the clinic'];

    public function __construct(DominioRepository $domRep, DefinicaoHorarioService $defHorarioserv, DoutorRepository $doutorRepository, BloqueioAgendaService $bloqAgServ, ConsultaService $consServ, PacienteRepository $pacRep) {

        $this->dominioRepository = $domRep;
        $this->definicaoHorarioService = $defHorarioserv;
        $this->doutorRepository = $doutorRepository;
        $this->bloqueioAgendaService = $bloqAgServ;
        $this->consultaService = $consServ;
        $this->pacienteRepository = $pacRep;
        $this->convenioRepository = new ConvenioRepository;
        $this->consultaRepository = new ConsultaRepository;
        $this->procedimentosRepositoy = new ProcedimentosRepository;
        $this->statusRefreshRepository = new StatusRefreshRepository;
    }

    private function dadosFiltroHorariosDisponivel($request) {
        $dadosFiltro['dataInicio'] = date('Y-m-d');
        $dadosFiltro['dataFim'] = date('Y-m-d', strtotime($dadosFiltro['dataInicio'] . " +7 days"));
        $dadosFiltro['horaInicio'] = '00:00';
        $dadosFiltro['horaFim'] = null;
        $dadosFiltro['minTime'] = null;
        $dadosFiltro['maxTime'] = null;
        $dadosFiltro['resultsNumber'] = 1000;
        $dadosFiltro['idDominio'] = null;


        if ($request->has('AVA_START_DAY') and ! empty($request->get('AVA_START_DAY'))) {
            $dadosFiltro['dataInicio'] = Functions::dateBrToDB($request->get('AVA_START_DAY'));
            $dadosFiltro['dataFim'] = date('Y-m-d', strtotime($dadosFiltro['dataInicio'] . " +7 days"));
        }
        if ($request->has('AVA_END_DAY') and ! empty($request->get('AVA_END_DAY'))) {
            $dadosFiltro['dataFim'] = Functions::dateBrToDB($request->get('AVA_END_DAY'));
        }
        if ($request->has('AVA_START_TIME') and ! empty($request->get('AVA_START_TIME'))) {
            $dadosFiltro['horaInicio'] = $request->get('AVA_START_TIME');
        }
        if ($request->has('AVA_END_TIME') and ! empty($request->get('AVA_END_TIME'))) {
            $dadosFiltro['horaFim'] = $request->get('AVA_END_TIME');
        }
        if ($request->has('AVA_END_TIME') and ! empty($request->get('AVA_END_TIME'))) {
            $dadosFiltro['horaFim'] = $request->get('AVA_END_TIME');
        }
        if ($request->has('AVA_MIN_TIME') and ! empty($request->get('AVA_MIN_TIME'))) {
            $dadosFiltro['minTime'] = $request->get('AVA_MIN_TIME');
        }
        if ($request->has('AVA_MAX_TIME') and ! empty($request->get('AVA_MAX_TIME'))) {
            $dadosFiltro['maxTime'] = $request->get('AVA_MAX_TIME');
        }
        if ($request->has('AVA_RESULTS_NUMBER') and ! empty($request->get('AVA_RESULTS_NUMBER'))) {
            $dadosFiltro['resultsNumber'] = $request->get('AVA_RESULTS_NUMBER');
        }
        if ($request->has('LOCATION_LID') and ! empty($request->get('LOCATION_LID'))) {
            $dadosFiltro['idDominio'] = $request->get('LOCATION_LID');
        }


        $dataIni = new \DateTime($dadosFiltro['dataInicio']);
        $dataFim = new \DateTime($dadosFiltro['dataFim']);
        $diff = $dataFim->diff($dataIni);
        $dadosFiltro['qntDias'] = $diff->days;


        return $dadosFiltro;
    }

    /**
     * 
     * @param type $idDominio
     * @param type $doutor_id
     * @param type $datetimeInicio
     * @param type $datetimeFim
     */
    private function listHorariosDisponiveis($idDominio, $doutor_id, $dataInicio, $horaInicio = null, $dataFim = null, $horaFim = null, $minTime = null, $maxTime = null) {

        $qntDias = 0;
        if (!empty($dataFim)) {
            $dataIniClass = new \DateTime($dataInicio);
            $dataFimClass = new \DateTime($dataFim);
            $diff = $dataFimClass->diff($dataIniClass);
            $qntDias = $diff->days;
        }


        $dataAg = $dataInicio;
        $timeInicio = strtotime($dataInicio . ' ' . $horaInicio);
        $timeFim = strtotime($dataFim . ' ' . $horaFim);

        $VerificaBloqueioHorarios = $this->bloqueioAgendaService->verificaBloqueio($idDominio, $dataInicio, null, $doutor_id, 1, true, $dataFim);


        $RETORNO = null;
        $contResult1 = 0;
        for ($i = 0; $i <= $qntDias; $i++) {


            $contResult = 0;

///bloqueio dia inteiro
//                      var_dump($VerificaBloqueioHorarios);
//                var_dump($dadosFiltro['dataFim']);
            if (isset($VerificaBloqueioHorarios[$dataAg]['dia_inteiro']) and $VerificaBloqueioHorarios[$dataAg]['dia_status_bloqueio'] == 1) {
                $dataAg = date('Y-m-d', strtotime($dataAg . " +1 days"));
                continue;
            }


            $qrHorarios = $this->definicaoHorarioService->getNovoHorarios($idDominio, $doutor_id, 1, $dataAg);


            $RETORNO[$contResult1] = $qrHorarios;

            if (isset($qrHorarios['dias_da_semana_id']) > 0) {
                $BloqueioPeriodoHora = null;


                foreach ($qrHorarios['horarios'] as $rowHorarios) {

                    $horario = $rowHorarios['horario'];
                    $statusHorario = $rowHorarios['status'];


/////////////////////////////////////////
//verificando bloqueios na agenda ///////
/////////////////////////////////////////
//verifica se existe bloqueio para um período de horas; Ex.: 11:00 as 14:00
                    if (isset($VerificaBloqueioHorarios[$dataAg][$horario]) and isset($VerificaBloqueioHorarios[$dataAg][$horario]['hora_final'])) {//                                
                        $BloqueioPeriodoHora['inicio'] = $horario;
                        $BloqueioPeriodoHora['final'] = $VerificaBloqueioHorarios[$dataAg][$horario]['hora_final'];
                    }

//                            $VerificaBloqueioMinisite = (isset($VerificaBloqueioHorarios[$dataProxBanco][$horario]['minisite'])) ? $VerificaBloqueioHorarios[$dataProxBanco][$horario]['minisite'] : '';
                    $VerificaBloqueioAgenda = (isset($VerificaBloqueioHorarios[$dataAg][$horario]['agenda'])) ? $VerificaBloqueioHorarios[$dataAg][$horario]['agenda'] : '';

//                            if ($VerificaBloqueioMinisite == 1
//                                    or ( empty($VerificaBloqueioMinisite)
//                                    and (
//                                    (isset($BloqueioPeriodoHora['inicio']) and $BloqueioPeriodoHora['inicio'] <= $horario)
//                                    and
//                                    (isset($BloqueioPeriodoHora['inicio']) and $BloqueioPeriodoHora['final'] >= $horario)
//                                    )
//                                    )) {
//                                $bloqHorarioMInisite = 1;
//                            } else {
//
//                                $bloqHorarioMInisite = 0;
//                            }


                    if ($statusHorario == 2 and ( empty($VerificaBloqueioAgenda) )or ( $VerificaBloqueioAgenda == 1)) {
                        $horarioBloqueado = true;
                    } else {
                        $horarioBloqueado = false;
                    }


                    if ($horarioBloqueado == true or $VerificaBloqueioAgenda == 1) {
                        continue;
                    }


                    if ($VerificaBloqueioAgenda == 1
                            or ( empty($VerificaBloqueioAgenda) and (
                            (isset($BloqueioPeriodoHora['inicio']) and $BloqueioPeriodoHora['inicio'] <= $horario)
                            and ( isset($BloqueioPeriodoHora['final']) and $BloqueioPeriodoHora['final'] >= $horario)
                            ))
                    ) {
                        continue;
                    }

                    $verificaDisponibilidade = $this->consultaService->verificaDisponibilidadeConsultasHorario($idDominio, $doutor_id, $dataAg, $horario, true, false);
//                            var_dump($verificaDisponibilidade);

                    if (!$verificaDisponibilidade) {
                        continue;
                    }
//////////////////////////////////////////// Fim Bloqueios
//VErifica Data e hora de inicio e termino
                    $timeAg = strtotime($dataAg . ' ' . $rowHorarios['horario']);
                    $minTimeHour = (!empty($minTime)) ? strtotime($dataAg . ' ' . $minTime) : null;

                    $maxTimeHour = (!empty($maxTime)) ? strtotime($dataAg . ' ' . $maxTime) : null;

//                     var_dump(!empty($maxTimeHour) and $timeAg > $maxTimeHour);
                    if ($timeAg < $timeInicio or ( !empty($horaFim) and $timeAg > $timeFim)
                            or ( !empty($minTimeHour) and $timeAg < $minTimeHour)
                            or ( !empty($maxTimeHour) and $timeAg > $maxTimeHour)
                    ) {
                        continue;
                    }
//                    var_dump(date('Y-m-d H:i',$minTimeHour).' -' .date('Y-m-d H:i',$timeAg));
//   dd(date('Y-m-d H:i',$minTimeHour).' -' .date('Y-m-d H:i',$timeAg));

                    $dataTerminoAVA = null;
                    $dataTerminoAVA = date('H:i', strtotime($dataAg . ' ' . $rowHorarios['horario'] . "+{$qrHorarios['intervalo']} minutes "));



                    $RETORNO[$contResult1]['date'] = Functions::dateDbToBr($dataAg);
                    unset($RETORNO[$contResult1]['horarios']);

//                    $RETORNO[$dataAg]['horarios_disp'][$contResult]['date'] = Functions::dateDbToBr($dataAg);
                    $RETORNO[$contResult1]['horarios_disp'][$contResult]['horario_ini'] = $rowHorarios['horario'];
                    $RETORNO[$contResult1]['horarios_disp'][$contResult]['horario_fim'] = $dataTerminoAVA;
                    $contResult++;
                }
            }
            $dataAg = date('Y-m-d', strtotime($dataAg . " +1 days"));
            $contResult1++;
        };

//        dd($RETORNO);
        return $RETORNO;
    }

    public function getHorariosDisponiveis($request) {

        $idDoutor = (!empty($request->get('RESOURCE_LID'))) ? str_replace('dout', '', $request->get('RESOURCE_LID')) : null;
        $convenioId = (!empty($request->get('INSURANCE_LID'))) ? $request->get('INSURANCE_LID') : null;
        $arrayConvenioId = explode('-', $convenioId);

        $procedimentoID = (!empty($request->get('ACTIVITY_LID'))) ? $request->get('ACTIVITY_LID') : null;
//        $arrayProcedimentoID = explode('_', $procedimentoID);

        $dadosFiltro = $this->dadosFiltroHorariosDisponivel($request);

        $qrDominios = $this->dominioRepository->getAllByUser(auth()->user()->id);
        if ($request->has('LOCATION_LID') and ! empty($request->get('LOCATION_LID'))) {
            $idsDominio = $request->get('LOCATION_LID');
        } else {
            $idsDominio = array_map(function($item) {
                return $item->id;
            }, $qrDominios);
        }
//
//        if (empty($idDoutor) and count($arrayProcedimentoID) == 3) {
//            $idDoutor = $arrayProcedimentoID[0];
//        }


        $RESULT = null;
        $RESULT2 = null;
        $precoProc = null;
        $contResult = 0;
        $contResult2 = 0;
        $idConvenioFiltro = null;
        $qrDoutores = $this->doutorRepository->getAllById($idsDominio, $idDoutor);

        if (count($qrDoutores) > 0) {
            foreach ($qrDoutores as $rowDoutor) {

                if (!empty($arrayConvenioId) and count($arrayConvenioId) == 2) {
                    $qrConv = $this->convenioRepository->getAllConveniosDoutores($rowDoutor->identificador, $rowDoutor->id);
                    $arrayConveniosSQL = array_map(function($item) {
                        return $item->convenios_id;
                    }, $qrConv);
                    if (!in_array($arrayConvenioId[1], $arrayConveniosSQL)) {
                        continue;
                    }

                    $idConvenioFiltro = $arrayConvenioId[1];
                }
//                if (!empty($arrayProcedimentoID) and count($arrayProcedimentoID) == 3) {
                if (!empty($procedimentoID)) {

                    $qrProcedimentos = $this->procedimentosRepositoy->getProcedimentoPorDoutor($idsDominio, $idDoutor, $idConvenioFiltro, $procedimentoID, null, null);

//                    dd($qrProcedimentos);
                    if (
                            count($qrProcedimentos) == 0
                            or ( count($arrayConvenioId) == 2 AND $arrayConvenioId[1] != $qrProcedimentos[0]->proc_convenios_id)
//                            or ( $rowDoutor->id != $arrayProcedimentoID[0])
                    ) {
                        continue;
                    }

                    $precoProc = (!empty($idConvenioFiltro) and $idConvenioFiltro == 41) ? $qrProcedimentos[0]->valor_proc : null;
//                    if (!empty() ) {
//                        continue;
//                    }
//                    $qrConv = $this->convenioRepository->getAllConveniosDoutores($rowDoutor->identificador, $rowDoutor->id);
//                    $arrayConveniosSQL = array_map(function($item) {
//                        return $item->convenios_id;
//                    }, $qrConv);
//                    if (!in_array($arrayProcedimentoID[1], $arrayConveniosSQL)) {
//                        continue;
//                    }
                }

//                dd($dadosFiltro);
                $horariosLIst = $this->listHorariosDisponiveis($rowDoutor->identificador, $rowDoutor->id, $dadosFiltro['dataInicio'], $dadosFiltro['horaInicio'], $dadosFiltro['dataFim'], $dadosFiltro['horaFim'], $dadosFiltro['minTime'], $dadosFiltro['maxTime'], $dadosFiltro['resultsNumber']);


//                dd($horariosLIst);
                if (count($horariosLIst) > 0) {
                    foreach ($horariosLIst as $rowListHorario) {

                        if (!empty($rowListHorario['horarios_disp'])) {
                            foreach ($rowListHorario['horarios_disp'] as $rowHorarioDisp) {


//limite de resultados
                                if ($contResult2 >= $dadosFiltro['resultsNumber']) {
                                    continue;
                                }
                                $RESULT2[$contResult2]['RESOURCE_LID'] = 'dout' . $rowDoutor->id;
                                $RESULT2[$contResult2]['ACTIVITY_LID'] = $procedimentoID;
                                $RESULT2[$contResult2]['LOCATION_LID'] = $rowDoutor->identificador;
                                $RESULT2[$contResult2]['INSURANCE_LID'] = $convenioId;
                                $RESULT2[$contResult2]['AVA_DATE'] = Functions::dateDbToBr($rowListHorario['date']);
                                $RESULT2[$contResult2]['AVA_START_TIME'] = $rowHorarioDisp['horario_ini'];
                                $RESULT2[$contResult2]['AVA_END_TIME'] = $rowHorarioDisp['horario_fim'];
                                $RESULT2[$contResult2]['AVAILABILITY_LID'] = $RESULT2[$contResult2]['RESOURCE_LID'] . '_' . $RESULT2[$contResult2]['AVA_DATE'] . '_' . $RESULT2[$contResult2]['AVA_START_TIME'];
                                $RESULT2[$contResult2]['AVA_PRICE'] = $precoProc;
                                $contResult2++;
                            }
                        }
                    }
                }
            }

//                dd($RESULT2);
            return $this->returnSuccess($RESULT2);
        } else {
            return $this->returnError(null, "Doctor's not found");
        }
    }

    public function salvarConsulta($request) {
        date_default_timezone_set('America/Sao_Paulo');

        if (!$request->has('LOCATION_LID') or empty($request->get('LOCATION_LID'))) {
            $RESULTS['ADD_RESULT'] = 'ERROR';
            $RESULTS['ERROR_MESSAGE'] = 'LOCATION_LID not informed';
            return $this->returnError($RESULTS, '');
        }

        $idDominio = $request->get('LOCATION_LID');

        $qrDominios = $this->dominioRepository->getAllByUser(auth()->user()->id);
        $IDsDominio = array_map(function($item) {
            return $item->id;
        }, $qrDominios);

        if (!in_array($idDominio, $IDsDominio)) {
            $RESULTS['ADD_RESULT'] = 'ERROR';
            $RESULTS['ERROR_MESSAGE'] = 'LOCATION_LID not found';
            return $this->returnError($RESULTS, '');
        }

        if (!$request->has('APP_DATE') or empty($request->get('APP_DATE'))) {
            $RESULTS['ADD_RESULT'] = 'ERROR';
            $RESULTS['ERROR_MESSAGE'] = 'APP_DATE  empty or not informed ';
            return $this->returnError($RESULTS, '');
        }
        if (!$request->has('APP_START_TIME') or empty($request->get('APP_START_TIME'))) {
            $RESULTS['ADD_RESULT'] = 'ERROR';
            $RESULTS['ERROR_MESSAGE'] = 'APP_START_TIME  empty or not informed ';
            return $this->returnError($RESULTS, '');
        }

        if (!$request->has('RESOURCE_LID') or empty($request->get('RESOURCE_LID'))) {
            $RESULTS['ADD_RESULT'] = 'ERROR';
            $RESULTS['ERROR_MESSAGE'] = 'RESOURCE_LID  empty or not informed ';
            return $this->returnError($RESULTS, '');
        }
        if (!$request->has('ACTIVITY_LID') or empty($request->get('ACTIVITY_LID'))) {
            $RESULTS['ADD_RESULT'] = 'ERROR';
            $RESULTS['ERROR_MESSAGE'] = 'ACTIVITY_LID  empty or not informed ';
            return $this->returnError($RESULTS, '');
        }
        if (!$request->has('INSURANCE_LID') or empty($request->get('INSURANCE_LID'))) {
            $RESULTS['ADD_RESULT'] = 'ERROR';
            $RESULTS['ERROR_MESSAGE'] = 'INSURANCE_LID  empty or not informed ';
            return $this->returnError($RESULTS, '');
        }
        if (empty($request->get('USER_LID')) and empty($request->get('USER_FIRST_NAME'))) {
            $RESULTS['ADD_RESULT'] = 'ERROR';
            $RESULTS['ERROR_MESSAGE'] = 'USER_FIRST_NAME  empty or not informed ';
            return $this->returnError($RESULTS, '');
        }

        $arraySexo = array('F' => 'Feminino', 'M' => 'Masculino');

        $dataAg = Functions::dateBrToDB($request->get('APP_DATE'));
        $horaIniAg = $request->get('APP_START_TIME');
        $doutorId = str_replace('dout', '', $request->get('RESOURCE_LID'));

        $rowDoutor = $this->doutorRepository->getAllById($idDominio, $doutorId);
        $rowDoutor = $rowDoutor[0];


        $ActivityId = $request->get('ACTIVITY_LID');

//        $dadosActivity = explode('_', $ActivityId);
//
//        $dadosActivity = array_filter($dadosActivity);
        $insuranceId = $request->get('INSURANCE_LID');
        $insuranceId = explode('-', $insuranceId);

        if (count($insuranceId) == 2) {
            $insuranceId = $insuranceId[1];
        }



        //verificando procedimento
        $qrProcedimentos = $this->procedimentosRepositoy->getByActivity($idDominio, $doutorId, $ActivityId, $insuranceId);

        if (count($qrProcedimentos) == 0) {
            $RESULTS['ADD_RESULT'] = 'ERROR';
            $RESULTS['ERROR_MESSAGE'] = 'The doctor does not accept this Activity or Insurance';
            return $this->returnError($RESULTS, '');
        }




        $pacienteId = $request->get('USER_LID');
        $observacaoConsulta = $request->get('APP_NOTES');

        //verificando bloqueios  e horários
        $qrHorarios = $this->listHorariosDisponiveis($idDominio, $doutorId, $dataAg);
        $intervalo = $qrHorarios[0]['intervalo'];

        $horarioDisponivel = $this->consultaService->verificaDisponibilidadeConsultasHorario($idDominio, $doutorId, $dataAg, $horaIniAg, true, false);




        if ($horarioDisponivel and ! empty($qrHorarios)
                and ( isset($qrHorarios[0]['horarios_disp']) and ! empty($qrHorarios[0]['horarios_disp']))
        ) {

            $HorariosDiaExiste = false;
            foreach ($qrHorarios[0]['horarios_disp'] as $rowHorario) {

                if ($horaIniAg == $rowHorario['horario_ini']) {
                    $HorariosDiaExiste = true;
                }
            }

            if (!$HorariosDiaExiste) {
                $RESULTS['ADD_RESULT'] = 'ERROR';
                $RESULTS['ERROR_MESSAGE'] = 'Not available';
                return $this->returnError($RESULTS);
            }


            $pacienteSecondName = (!empty($request->get('USER_THIRD_NAME'))) ? $request->get('USER_SECOND_NAME') . ' ' . $request->get('USER_THIRD_NAME') : $request->get('USER_SECOND_NAME');
            $numeroCarteriaConvenio = $PacienteCarteiraNumber = $request->get('USER_SUBSCRIPTION');
            switch ($request->get('USER_ID_TYPE')) {
                case 'CPF': $dadosPaciente['cpf_cript'] = $request->get('USER_ID_NUMBER');
                    break;
                case 'RG': $dadosPaciente['rg_cript'] = $request->get('USER_ID_NUMBER');
                    break;
            }

            $dadosPaciente['nome_cript'] = $request->get('USER_FIRST_NAME');
            $dadosPaciente['sobrenome_cript'] = $pacienteSecondName;
            $dadosPaciente['telefone_cript'] = $request->get('USER_LANDLINE_PHONE');
            $dadosPaciente['celular_cript'] = $request->get('USER_MOBILE_PHONE');
            $dadosPaciente['email_cript'] = $request->get('USER_EMAIL');
            $dadosPaciente['data_nascimento'] = $request->get('USER_DATE_OF_BIRTH');
            $dadosPaciente['sexo'] = isset($arraySexo[$request->get('USER_GENDER')]) ? $arraySexo[$request->get('USER_GENDER')] : '';
            $dadosPaciente['identificador'] = $idDominio;
            $dadosPaciente['logradouro'] = $request->get('USER_ADDRESS');
            $dadosPaciente['cep'] = $request->get('USER_ZIP_CODE');
            $dadosPaciente['cidade'] = $request->get('USER_CITY');
            $dadosPaciente['estado'] = $request->get('USER_PROVINCE');
            $dadosPaciente['permitir_dados_tuotempo'] = $request->get('USER_PRIVACY');
            $dadosPaciente['envia_email'] = $request->get('USER_PRIVACY_PROMOTIONS');



            if (empty($pacienteId)) {

                $verificaExistePaciente = $this->pacienteRepository->verificaPacienteExisteTuotempo($idDominio, $request->get('USER_FIRST_NAME'), $pacienteSecondName, $request->get('USER_LANDLINE_PHONE'), $request->get('USER_MOBILE_PHONE'), $request->get('USER_ID_NUMBER'), $request->get('USER_DATE_OF_BIRTH'));
                if (count($verificaExistePaciente) > 0) {
                    $pacienteId = $verificaExistePaciente[0]->id;
                    $this->pacienteRepository->update($idDominio, $pacienteId, $dadosPaciente);
                } else {
                    $dadosPaciente['matricula'] = $this->pacienteRepository->getUltimaMatricula($idDominio);
                    $dadosPaciente['data_cadastro'] = time();
                    $dadosPaciente['data_cad_pac'] = date('Y-m-d H:i:s');
                    $pacienteId = $this->pacienteRepository->insert($idDominio, $dadosPaciente);
                }

                $qrPaciente = $this->pacienteRepository->getById($idDominio, $pacienteId);
                $rowPaciente = $qrPaciente[0];
            } else {
                $qrPaciente = $this->pacienteRepository->getById($idDominio, $pacienteId);
                $rowPaciente = $qrPaciente[0];

                if (count($qrPaciente) > 0) {
                    $dadosPaciente['data_alter_pac'] = date('Y-m-d H:i:s');
                    $this->pacienteRepository->update($idDominio, $rowPaciente->id, $dadosPaciente);
                    $qrPaciente = $this->pacienteRepository->getById($idDominio, $rowPaciente->id);
                    $rowPaciente = $qrPaciente[0];
                }
            }


//adicionando os dados de convênio para o pacientes caso seja novo
            if (!empty($insuranceId)) {
                $verificaConvenio = $this->convenioRepository->getConveniosPacientes($idDominio, $pacienteId, $doutorId);
                if (count($verificaConvenio) == 0) {
                    $dadosConvenioPaciente['identificador'] = $idDominio;
                    $dadosConvenioPaciente['numero_carteira'] = $numeroCarteriaConvenio;
                    $dadosConvenioPaciente['validade_carteira'] = null;
                    $dadosConvenioPaciente['convenios_id'] = $insuranceId;
                    $dadosConvenioPaciente['doutores_id'] = $doutorId;
                    $dadosConvenioPaciente['pacientes_id'] = $pacienteId;
                    $this->convenioRepository->conveniosPacientesInsert($idDominio, $dadosConvenioPaciente);
                }

                $rowConvenio = $this->convenioRepository->getById($idDominio, $insuranceId);

                if (count($rowConvenio) > 0) {
                    $dadosConsulta['convenios_id'] = $rowConvenio[0]->convenios_id;

                    $dadosConsulta['convenio_numero_carteira'] = $numeroCarteriaConvenio;

//                    $dadosConsulta['convenio_validade_carteira'] = $rowConvenio[0]->validade_carteira;
                }
            }

//              dd($rowConvenio);
/////////////// fim paciente
//
//
///////CONSULTAS
            $dadosConsulta['identificador'] = $idDominio;
            $dadosConsulta['pacientes_id'] = $pacienteId;
            $dadosConsulta['doutores_id'] = $doutorId;
            $dadosConsulta['data_consulta'] = $dataAg;
            $dadosConsulta['data'] = $request->get('APP_DATE');
            $dadosConsulta['hora_consulta'] = $horaIniAg . ':00';
            $dadosConsulta['data_cad_consulta'] = date('Y-m-d H:i:s');
            $dadosConsulta['marcado_tuotempo'] = 1;
            $dadosConsulta['hora_consulta_fim'] = date('H:i', strtotime($dataAg . ' ' . $horaIniAg . " +{$intervalo} minutes"));
//        $dadosConsulta['tipo_contato_id'] = $tipo_contato;

            $verificaRetorno = $this->consultaService->verificaConsultaRetorno($idDominio, $dataAg, $pacienteId, $doutorId);
            if ($verificaRetorno['isRetorno']) {
                $dadosConsulta['retorno'] = 1;
            }

//inserindo consulta
            $idConsulta = $this->consultaRepository->insertConsulta($idDominio, $dadosConsulta);

            $arraySexo = array('Masculino' => 'M', 'Feminino' => 'F');
            $RESULTS['ADD_RESULT'] = 'OK';
            $RESULTS['ERROR_MESSAGE'] = '';
            $RESULTS['APP_LID'] = $idConsulta;
            $RESULTS['USER_LID'] = $pacienteId;
            $RESULTS['USER_FIRST_NAME'] = $rowPaciente->nome;
            $RESULTS['USER_SECOND_NAME'] = $rowPaciente->sobrenome;
            $RESULTS['USER_THIRD_NAME'] = null;
            $RESULTS['USER_DATE_OF_BIRTH'] = (!empty($rowPaciente->data_nascimento)) ? $rowPaciente->data_nascimento : null;
            $RESULTS['USER_PLACE_OF_BIRTH'] = null;
            $RESULTS['USER_ID_NUMBER'] = (!empty($rowPaciente->cpf)) ? $rowPaciente->cpf : null;
            $RESULTS['USER_ID_TYPE'] = 'CPF';
            $RESULTS['USER_GENDER'] = (!empty($rowPaciente->sexo)) ? $arraySexo[$rowPaciente->sexo] : null;
            $RESULTS['USER_ZIP_CODE'] = (!empty($rowPaciente->cep)) ? $rowPaciente->cep : null;
            $RESULTS['USER_MOBILE_PHONE'] = (!empty($rowPaciente->celular)) ? $rowPaciente->celular : null;
            $RESULTS['USER_LANDLINE_PHONE'] = (!empty($rowPaciente->telefone)) ? $rowPaciente->telefone : null;
            $RESULTS['USER_WORK_PHONE'] = null;
            $RESULTS['USER_EMAIL'] = $rowPaciente->email;
            $RESULTS['USER_PRIVACY_ACCEPTED'] = $rowPaciente->permitir_dados_tuotempo;
            $RESULTS['USER_PRIVACY_PROMOTIONS_ACCEPTED'] = $rowPaciente->envia_sms;
            $RESULTS['COMMUNICATION_PREFERENCES'] = null;



///procedimento
            if (count($qrProcedimentos) > 0) {

                $rowProc = $qrProcedimentos[0];

                if ((!empty($rowProc->impostoConv))) {
                    $valorLiquidoItem = $rowProc->valor_proc - CalculosService::calcularDescontoPercentual($rowProc->valor_proc, $rowProc->impostoConv); //com desconto de IR do convênio 
                } else {
                    $valorLiquidoItem = $rowProc->valor_proc;
                }

                $dadosProcConsulta['identificador'] = $idDominio;
                $dadosProcConsulta['consultas_id'] = $idConsulta;
                $dadosProcConsulta['procedimentos_id'] = $rowProc->procedimentos_id;
                $dadosProcConsulta['convenios_id'] = $rowProc->proc_convenios_id;
                $dadosProcConsulta['nome_convenio'] = $rowProc->nomeConvenioProc;
                $dadosProcConsulta['nome_proc'] = $rowProc->nomeProcedimento;
                $dadosProcConsulta['valor_proc'] = $rowProc->valor_proc;
                $dadosProcConsulta['data_cad'] = date('Y-m-d H:i:s');
                $dadosProcConsulta['procedimentos_cat_id'] = $rowProc->procedimento_categoria_id;
                $dadosProcConsulta['procedimentos_cat_nome'] = $rowProc->proc_cat_nome;
                $dadosProcConsulta['qnt'] = 1;
                $dadosProcConsulta['data_cad'] = date('Y-m-d H:i:s');
                $dadosProcConsulta['duracao'] = (!empty($rowProc->duracao)) ? $rowProc->duracao : null;
                $dadosProcConsulta['imposto_renda_convenio'] = (!empty($rowProc->impostoConv)) ? $rowProc->impostoConv : null;
                $dadosProcConsulta['valor_liquido_proc'] = $valorLiquidoItem;

//
//                if ($rowProc->possui_repasse) {
//                    $dadosProcConsulta['valor_repasse'] = $rowProc->valorRepasse;
//                    $dadosProcConsulta['tipo_repasse'] = $rowProc->tipo_repasse;
//                    $dadosProcConsulta['origem_repasse'] = $rowProc->tipo_repasse;
//                }
//                
           
                if ($rowProc->possui_parceiro == 1  ) {

                    $rowDoutorParceiro = $this->doutorRepository->getAllById($idDominio,$rowProc->doutor_parceiro_id);
                    
                    
//                dd($rowDoutorParceiro);
                    $ExecutanteRepasseID = $rowProc->doutor_parceiro_id;
                    $ExecutanteRepasseNome = $rowProc->nomeDoutorParceiro;

                    $possuiRepasseDoutor = $rowDoutorParceiro->possui_repasse;
                    $tipoRepasseDoutor = $rowDoutorParceiro->tipo_repasse;
                    $valorRepasseDoutor = $rowDoutorParceiro->valor_repasse;
                    
                } else {

                    $ExecutanteRepasseID = $doutorId;
                    $ExecutanteRepasseNome = $rowDoutor->nome;
                    $possuiRepasseDoutor = $rowDoutor->possui_repasse;
                    $tipoRepasseDoutor = $rowDoutor->tipo_repasse;
                    $valorRepasseDoutor = $rowDoutor->valor_repasse;
                }

             

                $totalProc = (1 * $rowProc->valor_proc);
                if ($possuiRepasseDoutor == 1) { //Repasse fixo no médico
                    if ($tipoRepasseDoutor == 1) {
                        $dadosProcConsulta['valor_repasse'] = $rowProc->valorRepasse;
                        $dadosProcConsulta['tipo_repasse'] = $rowProc->tipo_repasse;
                        $dadosProcConsulta['origem_repasse'] = 1;
                        $dadosProcConsulta['valor_repasse'] = CalculosService::calcularDescontoPercentual($totalProc, $valorRepasseDoutor);
                    } else {
                        $dadosProcConsulta['origem_repasse'] = 2; //Repasse por procedimento
                        $dadosProcConsulta['tipo_repasse'] = $rowProc->tipo_repasse;

                        if (!empty($rowProc->possui_repasse) and $rowProc->possui_repasse == 1) {
                            if ($rowProc->tipo_repasse == 1) {
                                $dadosProcConsulta['valor_repasse'] = CalculosService::calcularDescontoPercentual($totalProc, $rowProc->valor_percentual);
                            } else {
                                $dadosProcConsulta['valor_repasse'] = 1 * $rowProc->valor_real;
                            }
                        }
                    }
                }




                $dadosProcConsulta['executante_doutores_id'] = $ExecutanteRepasseID;
                $dadosProcConsulta['executante_nome_cript'] = $ExecutanteRepasseNome;
                $dadosProcConsulta['retorno_proc'] = ($rowProc->retorno == 1) ? 1 : null;

                $dadosProcConsulta['dicom'] = (!empty($rowProc->utiliza_dicom)) ? 1 : null;
//                $dadosProcConsulta['dicom_code'] = (!empty($rowProc->dicom_code)) ? $rowProc->dicom_code : null;
                $dadosProcConsulta['dicom_modality_id'] = (!empty($rowProc->dicom_modality_id)) ? $rowProc->dicom_modality_id : null;

//   dd($dadosProcConsulta);
                $this->consultaRepository->salvarConsultaProcedimentos($idDominio, $idConsulta, $dadosProcConsulta);
            }



            $this->statusRefreshRepository->insertAgenda($idDominio, $doutorId);

            return $this->returnError($RESULTS, '');
        } else {
            $RESULTS['ADD_RESULT'] = 'ERROR';
            $RESULTS['ERROR_MESSAGE'] = 'Not available';
            return $this->returnError($RESULTS, '');
        }
    }

    public function remarcarConsulta($request) {

        $msgError = null;

        $RESULTS['UPDATE_RESULT'] = 'ERROR';
        $RESULTS['APP_LID'] = null;
        $qrDominios = $this->dominioRepository->getAllByUser(auth()->user()->id);
        $IDsDominio = array_map(function($item) {
            return $item->id;
        }, $qrDominios);




        if (!($request->has('APP_LID')) or empty($request->get('APP_LID'))) {
            $RESULTS['ERROR_MESSAGE'] = 'APP_LID not informed';
            return $this->returnError($RESULTS);
        }
        if (!($request->has('NEW_APP_DATE')) or empty($request->get('NEW_APP_DATE'))) {
            $RESULTS['ERROR_MESSAGE'] = 'NEW_APP_DATE not informed';
            return $this->returnError($RESULTS);
        }
        if (!($request->has('NEW_APP_START_TIME')) or empty($request->get('NEW_APP_START_TIME'))) {
            $RESULTS['ERROR_MESSAGE'] = 'NEW_APP_START_TIME not informed';
            return $this->returnError($RESULTS);
        }
        if (!($request->has('RESOURCE_LID')) or empty($request->get('RESOURCE_LID'))) {
            $RESULTS['ERROR_MESSAGE'] = 'RESOURCE_LID not informed';
            return $this->returnError($RESULTS);
        } else {

            $idDoutor = str_replace('dout', '', $request->get('RESOURCE_LID'));
            $qrDoutor = $this->doutorRepository->getAllById($IDsDominio, $idDoutor);
            if (count($qrDoutor) == 0) {
                $RESULTS['ERROR_MESSAGE'] = 'RESOURCE_LID not found';
                return $this->returnError($RESULTS);
            } else {
                $idDominio = $qrDoutor[0]->identificador;
                $doutorId = $idDoutor;
            }
        }

        $idConsulta = $request->get('APP_LID');
        $dataAg = Functions::dateBrToDB($request->get('NEW_APP_DATE'));
        $horaIniAg = $request->get('NEW_APP_START_TIME');
        $rowConsulta = $this->consultaRepository->getById($IDsDominio, $idConsulta);
        if (!$rowConsulta) {
            $RESULTS['ERROR_MESSAGE'] = 'APP_LID not found';
            return $this->returnError($RESULTS);
        }


        $RecebimentosRepository = new RecebimentosRepository;
        $qrVErifcaPAg = $RecebimentosRepository->getByConsultaId($rowConsulta->identificador, $idConsulta);

        if (count($qrVErifcaPAg) > 0) {
            $RESULTS['ERROR_MESSAGE'] = 'Appointment already paid';
            return $this->returnError($RESULTS);
        }

//        $qrHorarios = $this->definicaoHorarioService->getNovoHorarios($idDominio, $doutorId, 1, $dataAg);
        $horarioDisponivel = $this->consultaService->verificaDisponibilidadeConsultasHorario($idDominio, $doutorId, $dataAg, $horaIniAg, null, true, false);

        $qrHorarios = $this->listHorariosDisponiveis($idDominio, $doutorId, $dataAg);

        $HorariosDiaExiste = false;





        if ($horarioDisponivel and ! empty($qrHorarios)
                and ( isset($qrHorarios[0]['horarios_disp']) and ! empty($qrHorarios[0]['horarios_disp']))
        ) {

            $HorariosDiaExiste = false;
            foreach ($qrHorarios[0]['horarios_disp'] as $rowHorario) {

                if ($horaIniAg == $rowHorario['horario_ini']) {
                    $HorariosDiaExiste = true;
                }
            }

            if (!$HorariosDiaExiste) {
                $RESULTS['UPDATE_RESULT'] = 'ERROR';
                $RESULTS['APP_LID'] = null;
                $RESULTS['ERROR_MESSAGE'] = 'Not available';
                return $this->returnError($RESULTS);
            }

//             dd($qrHorarios);


            if (!empty($rowConsulta->statusConsulta)) {
                $RESULTS['UPDATE_RESULT'] = 'ERROR';
                $RESULTS['APP_LID'] = null;
                $RESULTS['ERROR_MESSAGE'] = "Appointment in progress";
                return $this->returnError($RESULTS);
            }

            $intervalo = $qrHorarios[0]['intervalo'];
            $dadosAtualizaConsulta['hora_consulta_fim'] = date('H:i', strtotime($dataAg . ' ' . $horaIniAg . " +{$intervalo} minutes"));
//            $dadosAtualizaConsulta['hora_consulta_fim'] = null;
            $dadosAtualizaConsulta['data_consulta'] = $dataAg;
            $dadosAtualizaConsulta['hora_consulta'] = $horaIniAg;
            $dadosAtualizaConsulta['doutores_id'] = $doutorId;

            $this->consultaRepository->updateConsulta($idDominio, $idConsulta, $dadosAtualizaConsulta);
            $this->statusRefreshRepository->insertAgenda($idDominio, $rowConsulta->doutores_id);

            $RESULTS['UPDATE_RESULT'] = 'OK';
            $RESULTS['APP_LID'] = $idConsulta;
            $RESULTS['ERROR_MESSAGE'] = null;
            return $this->returnSuccess($RESULTS);
        } else {
            $RESULTS['UPDATE_RESULT'] = 'ERROR';
            $RESULTS['APP_LID'] = null;
            $RESULTS['ERROR_MESSAGE'] = 'Not available';
            return $this->returnError($RESULTS);
        }
    }

    public function atualizarStatus($request) {

        $arrayStatus = array('Aprovado' => 0, 'Confirmado' => 2, 'Desmarcado_paciente' => 3, 'Desmarcado_clinica' => 4, 'Atendido' => 5, 'Faltou' => 6);

        $RESULTS = array(
            'UPDATE_RESULT' => null,
            'UPDATE_MSG' => null,
            'ERROR_MESSAGE' => null,
        );
        if (!$request->has('APP_LID') or empty($request->get('APP_LID'))) {
            $RESULTS['UPDATE_RESULT'] = 'ERROR';
            $RESULTS['ERROR_MESSAGE'] = 'APP_LID uninformed';
            return $this->returnError($RESULTS, null);
        }

        $qrDominios = $this->dominioRepository->getAllByUser(auth()->user()->id);
        $IDsDominio = array_map(function($item) {
            return $item->id;
        }, $qrDominios);

        $idConsulta = $request->get('APP_LID');
        $status = $request->get('STATUS');
        $resourceNotes = $request->get('RESOURCE_NOTES');
        $urlVideoDoutor = $request->get('MEETING_RESOURCE_URL');
        $urlPacienteDoutor = $request->get('MEETING_PATIENT_URL');

        $rowConsulta = $this->consultaRepository->getById($IDsDominio, $idConsulta);
        if (!$rowConsulta) {
            $RESULTS['UPDATE_RESULT'] = 'ERROR';
            $RESULTS['ERROR_MESSAGE'] = 'APP_LID not found';
            return $this->returnError($RESULTS, null);
        }



        $ConsultaStatusRepository = new ConsultaStatusRepository;
        $AgendaFilaEsperaRepository = new AgendaFilaEsperaRepository;
        date_default_timezone_set('America/Sao_Paulo');
        $horaAtual = strtotime(date("Y-m-d H:i"));

        $dadosStatus ['consulta_id'] = $idConsulta;



        switch ($status) {
            case 0: ///AProvado

                if ($rowConsulta->statusConsulta != 'jaFoiAtendido' and $rowConsulta->statusConsulta != 'estaSendoAtendido') {
                    $ConsultaStatusRepository->limpaStatus($rowConsulta->identificador, $idConsulta);
                    $RESULTS['UPDATE_RESULT'] = 'OK';
                    $RESULTS['UPDATE_MSG'] = 'Status updated';
                } else {
                    $RESULTS['UPDATE_RESULT'] = 'ERROR';
                    $RESULTS['UPDATE_MSG'] = $this->msgErroStatus[$rowConsulta->statusConsulta];
                }
                break;
            case 2: //COnfirmado
                $dadosUpdateCons['confirmacao'] = 'Tuotempo';
                $this->consultaRepository->updateConsulta($rowConsulta->identificador, $idConsulta, $dadosUpdateCons);
                $RESULTS['UPDATE_RESULT'] = 'OK';
                $RESULTS['UPDATE_MSG'] = 'Status updated';
                break;

            case 3: //Desmarcado_paciente
            case 4: //Desmarcado_clinica

                if ($rowConsulta->statusConsulta != 'jaFoiAtendido' and $rowConsulta->statusConsulta != 'estaSendoAtendido'
                        and $rowConsulta->statusConsulta != 'desmarcado') {
                    $dadosStatus ['status'] = 'desmarcado';
                    $dadosStatus ['nome_administrador'] = 'Tuotempo';
                    $dadosStatus ['razao_desmarcacao'] = 'Desmarcado pelo Tuotempo';
                    $dadosStatus ['identificador'] = $rowConsulta->identificador;
                    $dadosStatus ['desmarcado_por'] = ($status == 3) ? 1 : 2;

                    $ConsultaStatusRepository->insereStatus($rowConsulta->identificador, $idConsulta, $dadosStatus);
                    $AgendaFilaEsperaRepository->excluirPorConsultaId($rowConsulta->identificador, $idConsulta);
                    $RESULTS['UPDATE_RESULT'] = 'OK';
                    $RESULTS['UPDATE_MSG'] = 'Status updated';
                } else {
                    $RESULTS['UPDATE_RESULT'] = 'ERROR';
                    $RESULTS['UPDATE_MSG'] = $this->msgErroStatus[$rowConsulta->statusConsulta];
                }


                break;
            case 5: //Atendido,
                if ($rowConsulta->statusConsulta != 'jaFoiAtendido' and $rowConsulta->statusConsulta != 'desmarcado' and $rowConsulta->statusConsulta != 'faltou') {
                    $dadosStatus ['status'] = 'jaFoiAtendido';
                    $dadosStatus ['nome_administrador'] = 'Tuotempo';
                    $dadosStatus ['hora'] = $horaAtual;
                    $dadosStatus ['identificador'] = $rowConsulta->identificador;

                    $ConsultaStatusRepository->insereStatus($rowConsulta->identificador, $idConsulta, $dadosStatus);
                    $AgendaFilaEsperaRepository->excluirPorConsultaId($rowConsulta->identificador, $idConsulta);
                    $RESULTS['UPDATE_RESULT'] = 'OK';
                    $RESULTS['UPDATE_MSG'] = 'Status updated';
                } else {
                    $RESULTS['UPDATE_RESULT'] = 'ERROR';
                    $RESULTS['UPDATE_MSG'] = $this->msgErroStatus[$rowConsulta->statusConsulta];
                }


                break;
            case 6: //Faltou
                if ($rowConsulta->statusConsulta != 'jaFoiAtendido' and $rowConsulta->statusConsulta != 'estaSendoAtendido'
                        and $rowConsulta->statusConsulta != 'desmarcado' and $rowConsulta->statusConsulta != 'faltou') {
                    $dadosStatus ['status'] = 'faltou';
                    $dadosStatus ['nome_administrador'] = 'Tuotempo';
                    $dadosStatus ['obs_falta'] = 'Desmarcado pelo Tuotempo';
                    $dadosStatus ['identificador'] = $rowConsulta->identificador;

                    $ConsultaStatusRepository->insereStatus($rowConsulta->identificador, $idConsulta, $dadosStatus);
                    $AgendaFilaEsperaRepository->excluirPorConsultaId($rowConsulta->identificador, $idConsulta);
                    $RESULTS['UPDATE_RESULT'] = 'OK';
                    $RESULTS['UPDATE_MSG'] = 'Status updated';
                } else {
                    $RESULTS['UPDATE_RESULT'] = 'ERROR';
                    $RESULTS['UPDATE_MSG'] = $this->msgErroStatus[$rowConsulta->statusConsulta];
                }
                break;
        }



        return $this->returnSuccess($RESULTS, null);
    }

    public function fastCheckin($request) {
        $RESULTS = array(
            'CHECKIN_RESULT' => null,
            'ACCEPTANCE_LID' => null,
            'CHECKIN_INFO' => null,
            'ERROR_MESSAGE' => null,
        );


        if (!$request->has('APP_LID') or empty($request->get('APP_LID'))) {
            $RESULTS['CHECKIN_RESULT'] = 'ERROR';
            $RESULTS['ERROR_MESSAGE'] = 'APP_LID uninformed';
            return $this->returnError($RESULTS, null);
        }
        if (!$request->has('CHECKIN_CODE') or empty($request->get('CHECKIN_CODE'))) {
            $RESULTS['CHECKIN_RESULT'] = 'ERROR';
            $RESULTS['ERROR_MESSAGE'] = 'CHECKIN_CODE uninformed';
            return $this->returnError($RESULTS, null);
        }
        if (!$request->has('CHECKIN_DATE') or empty($request->get('CHECKIN_DATE'))) {
            $RESULTS['CHECKIN_DATE'] = 'ERROR';
            $RESULTS['ERROR_MESSAGE'] = 'CHECKIN_CODE uninformed';
            return $this->returnError($RESULTS, null);
        }
        $qrDominios = $this->dominioRepository->getAllByUser(auth()->user()->id);
        $IDsDominio = array_map(function($item) {
            return $item->id;
        }, $qrDominios);

        $idConsulta = $request->get('APP_LID');
        $checkinCode = $request->get('CHECKIN_CODE');
        $checkinDate = $request->get('CHECKIN_DATE');
        $checkinDate = Functions::dateTuotempoToDB($request->get('CHECKIN_DATE'));

        $rowConsulta = $this->consultaRepository->getById($IDsDominio, $idConsulta);
        date_default_timezone_set('America/Sao_Paulo');
        $horaAtual = strtotime(date("Y-m-d H:i"));

        $ConsultaStatusRepository = new ConsultaStatusRepository;
        $AgendaFilaEsperaRepository = new AgendaFilaEsperaRepository;

        $RecebimentosRepository = new RecebimentosRepository;
        $qrRecebimento = $RecebimentosRepository->getByConsultaId($rowConsulta->identificador, $idConsulta);


        if (count($qrRecebimento) == 0) {
            $RESULTS['CHECKIN_RESULT'] = 'ERROR';
            $RESULTS['ERROR_MESSAGE'] = 'Appointment is still not paid';
            return $this->returnSuccess($RESULTS);
        }

        if ($rowConsulta->statusConsulta != 'jaFoiAtendido' and $rowConsulta->statusConsulta != 'estaSendoAtendido'
                and $rowConsulta->statusConsulta != 'desmarcado' and $rowConsulta->statusConsulta != 'faltou' and $rowConsulta->statusConsulta != 'jaSeEncontra') {


////horaAtraso
            $dataConsulta = explode('-', $rowConsulta->data_consulta);
            $horarioConsulta = explode(':', $rowConsulta->hora_consulta);

            $dataCon = new DateTime($rowConsulta->data_consulta . ' ' . $rowConsulta->hora_consulta);
            $dataHj = new DateTime();
            $diff = $dataHj->diff($dataCon);

            $dadosStatus ['consulta_id'] = $idConsulta;
            $dadosStatus ['status'] = 'jaSeEncontra';
            $dadosStatus ['nome_administrador'] = 'Tuotempo';
            $dadosStatus ['hora'] = $horaAtual;
            $dadosStatus ['hora_atraso'] = $diff->format("%h:%i:%s");
            $dadosStatus ['identificador'] = $rowConsulta->identificador;
            $ConsultaStatusRepository->alteraStatus($rowConsulta->identificador, $idConsulta, $dadosStatus);
            $AgendaFilaEsperaRepository->insert($rowConsulta->identificador, $idConsulta, $rowConsulta->doutores_id);

            $RESULTS['CHECKIN_RESULT'] = 'OK';

            return $this->returnSuccess($RESULTS);
        } else {
            $RESULTS['CHECKIN_RESULT'] = 'ERROR';
            $RESULTS['ERROR_MESSAGE'] = $this->msgErroStatus[$rowConsulta->statusConsulta];
            return $this->returnError($RESULTS);
        }




//                $dadosUpdateCons['tuotempo_url_doctor'] = $urlVideoDoutor;
//        $dadosUpdateCons['tuotempo_url_patient'] = $urlPacienteDoutor;
    }

}
