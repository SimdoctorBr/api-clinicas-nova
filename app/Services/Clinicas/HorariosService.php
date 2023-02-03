<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\Clinicas;

use App\Services\BaseService;
use DateTime;
use App\Repositories\Clinicas\FeriasRepository;
use App\Repositories\Clinicas\FeriadoRepository;
use App\Repositories\Clinicas\DefinicaoMarcacaoConsultaRepository;
use App\Services\Clinicas\BloqueioAgendaService;
use App\Services\Clinicas\DefinicaoHorarioService;
use App\Services\Clinicas\ConsultaService;
use App\Repositories\Clinicas\ConsultaRepository;
use App\Services\Clinicas\Consulta\ConsultaProcedimentoService;
use App\Services\Clinicas\Paciente\ProntuarioService;
use App\Services\Clinicas\CompromissoService;
use App\Helpers\Functions;
use App\Repositories\Clinicas\ConsultaStatusRepository;
use App\Repositories\Clinicas\ConvenioRepository;

/**
 * Description of Activities
 *
 * @author ander
 */
class HorariosService extends BaseService {

    private $bloqueioAgendaService;
    private $definicaoHorarioService;
    private $consultaService;
    private $feriasRepository;
    private $feriadoRepository;
    private $consultaProcedimentoService;

    public function __construct() {
        $this->bloqueioAgendaService = new BloqueioAgendaService;
        $this->definicaoHorarioService = new DefinicaoHorarioService;
        $this->consultaService = new ConsultaService;
        $this->feriasRepository = new FeriasRepository;
        $this->feriadoRepository = new FeriadoRepository;
        $this->consultaProcedimentoService = new ConsultaProcedimentoService;
    }

    /**
     * 
     * @param type $idDominio
     * @param type $doutorId
     * @param type $datetimeInicio
     * @param type $datetimeFim
     */
    public function listHorarios($idDominio, $doutorId, $dataInicio, $horaInicio = null, $dataFim = null, $horaFim = null, $minTime = null, $maxTime = null, $percentualOcupacao = false, $exibeConsultas = false, $dadosFiltro = null) {

        $qntDias = 0;

        $ConsultaRepository = new ConsultaRepository;

        if (!empty($dataFim)) {
            $dataIniClass = new \DateTime($dataInicio);
            $dataFimClass = new \DateTime($dataFim);
            $diff = $dataFimClass->diff($dataIniClass);
            $qntDias = $diff->days;
        }

        $dataAg = $dataInicio;
        $timeInicio = strtotime($dataInicio . ' ' . $horaInicio);
        $timeFim = strtotime($dataFim . ' ' . $horaFim);

        $CompromissoService = new CompromissoService;
        $qrCompromissos = $CompromissoService->getAll($idDominio, $doutorId, ['data' => $dataInicio, 'dataFim' => $dataFim], 1);

        $VerificaBloqueioHorarios = $this->bloqueioAgendaService->verificaBloqueio($idDominio, $dataInicio, null, $doutorId, 1, true, $dataFim);
        $FERIADOS = $this->feriadoRepository->getByPeriodo($idDominio, $dataInicio, $dataFim, 2);

        if ($percentualOcupacao) {
            $DefinicaoMarcacaoDoutor = new DefinicaoMarcacaoConsultaRepository;
            $rowDefinicoesMarcDoutor = $DefinicaoMarcacaoDoutor->getByDoutoresId($idDominio, $doutorId);
        }

        if ($percentualOcupacao or $exibeConsultas) {
            $CONSULTAS = $this->consultaService->getAllArrayData($idDominio, $doutorId, $dataInicio, $dataFim);
        }


//      

        $RETORNO = null;
        $contResult1 = 0;

        for ($i = 0; $i <= $qntDias; $i++) {

            $contResult = 0;
            $msgNaoDisponivel = '';

            ///bloqueio dia inteiro
            if (isset($VerificaBloqueioHorarios[$dataAg]['dia_inteiro']) and $VerificaBloqueioHorarios[$dataAg]['dia_status_bloqueio'] == 1) {
                $msgNaoDisponivel = 'Bloqueado o dia inteiro';
            }

            $verificaFerias = $this->feriasRepository->verificaFerias($idDominio, $doutorId, $dataAg);

            if ($verificaFerias['ferias']) {
                $msgNaoDisponivel = 'Férias no período:' . $verificaFerias['inicio'] . ' até ' . $verificaFerias['fim'];
            }


            //FERIADOS
            if (isset($FERIADOS[$dataAg]) and!empty($FERIADOS[$dataAg])) {
                if (empty($FERIADOS[$dataAg]['desativado'])) {
                    $msgNaoDisponivel = $FERIADOS[$dataAg]['razao'];
                }
            }



            $qrHorarios = $this->definicaoHorarioService->getNovoHorarios($idDominio, $doutorId, 1, $dataAg);

            $RETORNO[$contResult1] = $qrHorarios;
            $RETORNO[$contResult1]['dataDisponivel'] = false;
            $RETORNO[$contResult1]['totalHorariosDisponivel'] = 0;
            $RETORNO[$contResult1]['msgErro'] = null;
            $RETORNO[$contResult1]['data'] = ($dataAg);

            if (!empty($msgNaoDisponivel)) {
                $RETORNO[$contResult1]['msgErro'] = $msgNaoDisponivel;

                unset($RETORNO[$contResult1]['horarios']);
                $dataAg = date('Y-m-d', strtotime($dataAg . " +1 days"));
                $contResult1++;
                continue;
            }



            $countHorariosOcup = 0;
            $totalHorariosDisponiveis = 0;
            $totalHorariosAgendaveis = 0;
            if (isset($qrHorarios['dias_da_semana_id']) > 0) {
                $BloqueioPeriodoHora = null;

                $RETORNO[$contResult1]['dataDisponivel'] = true;

                if ($qrHorarios['horarios'] != null) {
                    foreach ($qrHorarios['horarios'] as $rowHorarios) {

                        $horario = $rowHorarios['horario'];
                        $statusHorario = $rowHorarios['status'];
                        $RETORNO[$contResult1]['horariosList'][$contResult]['disponivel'] = true;
                        $RETORNO[$contResult1]['horariosList'][$contResult]['descricao'] = 'Livre';
                        $RETORNO[$contResult1]['horariosList'][$contResult]['compromissos'] = null;

                        /////////////////////////////////////////
                        //verificando bloqueios na agenda ///////
                        /////////////////////////////////////////
                        //verifica se existe bloqueio para um período de horas; Ex.: 11:00 as 14:00
//                    if (isset($VerificaBloqueioHorarios[$dataAg][$horario]) and isset($VerificaBloqueioHorarios[$dataAg][$horario]['hora_final'])) {//                                
//                        $BloqueioPeriodoHora['inicio'] = $horario;
//                        $BloqueioPeriodoHora['final'] = $VerificaBloqueioHorarios[$dataAg][$horario]['hora_final'];
//                    }

                        if (isset($VerificaBloqueioHorarios[$dataAg])) {
                            foreach ($VerificaBloqueioHorarios[$dataAg] as $horarioIni => $rowDados) {
                                if (isset($rowDados['hora_final']) and strtotime($horario) >= strtotime($horarioIni) and strtotime($horario) <= strtotime($rowDados['hora_final']) and!empty($rowDados['hora_final'])) {
                                    $BloqueioPeriodoHora['inicio'] = $horarioIni;
                                    $BloqueioPeriodoHora['final'] = $rowDados['hora_final'];
                                    break;
                                }
                            }
                        }


                        $VerificaBloqueioAgenda = (isset($VerificaBloqueioHorarios[$dataAg][$horario]['agenda'])) ? $VerificaBloqueioHorarios[$dataAg][$horario]['agenda'] : '';

                        $RETORNO[$contResult1]['horariosList'][$contResult]['disponivelMinisite'] = true;

                        if ($statusHorario == 2 and ( empty($VerificaBloqueioAgenda) )or ( $VerificaBloqueioAgenda == 1)) {
                            $horarioBloqueado = true;
                        } else {
                            $horarioBloqueado = false;
                        }

                        ////////////////////
                        ///MINISITE //
                        ////////////////////
                        $VerificaBloqueioMinisite = (isset($VerificaBloqueioHorarios[$dataAg][$horario]['minisite'])) ? $VerificaBloqueioHorarios[$dataAg][$horario]['minisite'] : '';

                        if ($VerificaBloqueioMinisite == 1
                                or ( empty($VerificaBloqueioMinisite) and (
                                (isset($BloqueioPeriodoHora['inicio']) and $BloqueioPeriodoHora['inicio'] <= $horario)
                                and ( isset($BloqueioPeriodoHora['final']) and $BloqueioPeriodoHora['final'] > $horario)
                                ))) {
                            $RETORNO[$contResult1]['horariosList'][$contResult]['disponivelMinisite'] = false;
                        }
                        if (($horarioBloqueado == true or $VerificaBloqueioAgenda == 1) and!isset($VerificaBloqueioHorarios['desbloqueados'][$dataAg][$horario]['minisite'])) {
                            $RETORNO[$contResult1]['horariosList'][$contResult]['disponivelMinisite'] = false;
                        }
                        ////////////////////
                        ////FIM MINISITE //
                        ////////////////////
                        ////////////////////////
                        ///?VIDEO CONSULTA ///
                        ////////////////////////
                        $videoConfDesabilitado = isset($VerificaBloqueioHorarios[$dataAg][$horario]['videoconf_desabilitado']) ? $VerificaBloqueioHorarios[$dataAg][$horario]['videoconf_desabilitado'] : '';

//   if($horario == '13:30'){
//                        var_Dump($horario);
//                    var_dump(($videoConfDesabilitado == 1 )
//                                or ( (empty($videoConfDesabilitado) and $videoConfDesabilitado !== 0) and $rowHorarios['video_desabilitado'] == 1 )
//                        );
//                }

                        $RETORNO[$contResult1]['horariosList'][$contResult]['disponivelVideo'] = true;

                        if (($videoConfDesabilitado == 1 )
                                or ( (empty($videoConfDesabilitado) and $videoConfDesabilitado !== 0) and $rowHorarios['video_desabilitado'] == 1 )
                        ) {
                            $RETORNO[$contResult1]['horariosList'][$contResult]['disponivelVideo'] = false;
                        }




                        //
//                    if (auth('clinicas')->user()->id == 4051) {
//                        if ($horario == '13:50') {
//                            dd($VerificaBloqueioAgenda);
////                    var_dump($BloqueioPeriodoHora);
////                    var_dump($VerificaBloqueioHorarios['desbloqueados'][$dataProxBanco][$horario]['agenda']);
//                        } 
//                    }

                        if (($horarioBloqueado == true or $VerificaBloqueioAgenda == 1) and!isset($VerificaBloqueioHorarios['desbloqueados'][$dataAg][$horario]['agenda'])) {
                            //                        continue;
                            $RETORNO[$contResult1]['horariosList'][$contResult]['disponivel'] = false;
                            $RETORNO[$contResult1]['horariosList'][$contResult]['descricao'] = (!empty($VerificaBloqueioHorarios[$dataAg][$horario]['motivo_bloqueio'])) ? $VerificaBloqueioHorarios[$dataAg][$horario]['motivo_bloqueio'] : 'Bloqueado';
                        }





                        if (($VerificaBloqueioAgenda == 1
                                or ( empty($VerificaBloqueioAgenda) and (
                                (isset($BloqueioPeriodoHora['inicio']) and $BloqueioPeriodoHora['inicio'] <= $horario)
                                and ( isset($BloqueioPeriodoHora['final']) and $BloqueioPeriodoHora['final'] > $horario)
                                )))
                                and!isset($VerificaBloqueioHorarios['desbloqueados'][$dataAg][$horario]['agenda'])
                        ) {
                            $RETORNO[$contResult1]['horariosList'][$contResult]['disponivel'] = false;

                            if (isset($BloqueioPeriodoHora['final']) and $BloqueioPeriodoHora['final'] > $horario) {
                                $RETORNO[$contResult1]['horariosList'][$contResult]['descricao'] = (!empty($VerificaBloqueioHorarios[$dataAg][$BloqueioPeriodoHora['inicio']]['motivo_bloqueio'])) ? $VerificaBloqueioHorarios[$dataAg][$BloqueioPeriodoHora['inicio']]['motivo_bloqueio'] : 'Bloqueado';
                            } else {
                                $RETORNO[$contResult1]['horariosList'][$contResult]['descricao'] = (!empty($VerificaBloqueioHorarios[$dataAg][$horario]['motivo_bloqueio'])) ? $VerificaBloqueioHorarios[$dataAg][$horario]['motivo_bloqueio'] : 'Bloqueado';
                            }
                        }

                        $limiteEncaixe = (isset($dadosFiltro['encaixe']) and $dadosFiltro['encaixe'] == true) ? true : false;
                        $verificaDisponibilidade = $this->consultaService->verificaDisponibilidadeConsultasHorario($idDominio, $doutorId, $dataAg, $horario, true, $limiteEncaixe);

//                            var_dump($verificaDisponibilidade);
                        //////////////////////////////////////////// Fim Bloqueios
                        //
                        //VErifica Data e hora de inicio e termino
                        $timeAg = strtotime($dataAg . ' ' . $rowHorarios['horario']);
                        $minTimeHour = (!empty($minTime)) ? strtotime($dataAg . ' ' . $minTime) : null;

                        $maxTimeHour = (!empty($maxTime)) ? strtotime($dataAg . ' ' . $maxTime) : null;

                        if ($timeAg < $timeInicio or (!empty($horaFim) and $timeAg > $timeFim)
                                or (!empty($minTimeHour) and $timeAg < $minTimeHour)
                                or (!empty($maxTimeHour) and $timeAg > $maxTimeHour)
                        ) {
                            $RETORNO[$contResult1]['horariosList'][$contResult]['disponivel'] = false;
                            $RETORNO[$contResult1]['horariosList'][$contResult]['descricao'] = 'Bloqueado';
                        }

                        //total horario agendavel
                        if ($RETORNO[$contResult1]['horariosList'][$contResult]['disponivel']) {
                            $totalHorariosAgendaveis++;
                        }

                        if (!$verificaDisponibilidade) {
//                        continue;
                            $RETORNO[$contResult1]['horariosList'][$contResult]['disponivel'] = false;
                            $RETORNO[$contResult1]['horariosList'][$contResult]['descricao'] = 'Bloqueado';
                        }

                        if (isset($COMPROMISSOS_BLOQUEIO[$dataAg]) and in_array($horario, $COMPROMISSOS_BLOQUEIO[$dataAg])) {
                            $RETORNO[$contResult1]['horariosList'][$contResult]['disponivel'] = false;
                            $RETORNO[$contResult1]['horariosList'][$contResult]['descricao'] = 'Bloqueado';
                        }


                        $bloqueiahorarioComp = false;

                        if ($qrCompromissos != null) {
                            foreach ($qrCompromissos as $rowComp) {

                                if ($rowComp['data'] == $dataAg
                                        and (
                                        ( $horario >= substr($rowComp['hora'], 0, 5)
                                        and!empty($rowComp['horaFim']) and $rowComp['horaFim'] != '00:00:00' and $horario < substr($rowComp['horaFim'], 0, 5) )

                                        or ( empty($rowComp['horaFim']) and $horario == substr($rowComp['hora'], 0, 5))
                                        )
                                ) {
//                                    if (substr($rowComp['hora'], 0, 5) == $horario) {
                                    $RETORNO[$contResult1]['horariosList'][$contResult]['compromissos'][] = $rowComp;
//                                    }
                                    if (!$limiteEncaixe) {
                                        $RETORNO[$contResult1]['horariosList'][$contResult]['disponivel'] = false;
                                        $RETORNO[$contResult1]['horariosList'][$contResult]['descricao'] = 'Bloqueado por compromisso';
                                    }
                                    break;
                                }
                            }
                        }

                        //total horario agendavel
                        if ($RETORNO[$contResult1]['horariosList'][$contResult]['disponivel']) {
                            $totalHorariosDisponiveis++;
                        }



                        $arrayConsultasHR = (isset($CONSULTAS['data'][$dataAg][$rowHorarios['horario']]) ) ?
                                $CONSULTAS['data'][$dataAg][$rowHorarios['horario']] : null;

                        //exibe a consulta no horário de término
                        if (isset($dadosFiltro['exibeConsultaTermino']) and $dadosFiltro['exibeConsultaTermino']) {
                            if (isset($CONSULTAS['data'][$dataAg])) {
                                foreach ($CONSULTAS['data'][$dataAg] as $listConsultaHorarios) {
//                               var_dump($consultaHorarios);
                                    foreach ($listConsultaHorarios as $consultaHorarios) {
//                                var_dump($consultaHorarios->hora_consulta);
//                                var_dump($consultaHorarios->hora_consulta_fim   );

                                        if (strtotime($rowHorarios['horario']) >= strtotime(substr($consultaHorarios->hora_consulta, 0, 5)) and strtotime($rowHorarios['horario']) < strtotime(substr($consultaHorarios->hora_consulta_fim, 0, 5))) {
                                            $arrayConsultasHR[] = $consultaHorarios;
                                        }
                                    }
                                }
                            }
                        }


//                    var_Dump($rowHorarios['horario']);
//                    var_Dump($qrHorarios['intervalo']);
//                    var_Dump($arrayConsultasHR);
//                    var_Dump(substr($arrayConsultasHR[0]->hora_consulta_fim, 0, 5));;



                        if ($qrHorarios['almocoDe'] <= $horario and $qrHorarios['almocoAte'] >= $horario and $qrHorarios['almocoAte']
                                and $qrHorarios['possui_almoco'] == 1
                                and $RETORNO[$contResult1]['horariosList'][$contResult]['disponivel'] == false
                        ) {
                            $RETORNO[$contResult1]['horariosList'][$contResult]['descricao'] = 'Almoço';
                        }



                        //listando consultas
                        if ($arrayConsultasHR != null) {
                            $arrayConsultasHR = array_values($arrayConsultasHR);
                            $tempArray = array_unique(array_column($arrayConsultasHR, 'id'));
                            $arrayConsultasHR = array_intersect_key($arrayConsultasHR, $tempArray);
                            foreach ($arrayConsultasHR as $rowConsulta) {
//                               var_Dump($rowConsulta);


                                $qrProcedimentos = $this->consultaProcedimentoService->getByConsultaId($idDominio, $rowConsulta->id);
                                $dadosProcedimento = $qrProcedimentos;
                                $arrayProcConv = [];
                               if (!empty($dadosProcedimento)) {
                                    foreach ($dadosProcedimento as $rowProc) {
                                        $arrayProcConv[$rowProc['convenioId']] = [
                                            'id' => $rowProc['convenioId'],
                                            'nome' => $rowProc['convenioNome'],
                                        ];
                                    }
                                }
                                $arrayProcConv = array_values($arrayProcConv);

                                $desmarcadoPor = null;
                                $razaoDesmarcacao = null;
                                if (!empty($rowConsulta->statusConsulta)) {

                                    $statusDados = explode('_', $rowConsulta->statusConsulta);
                                    $statusConsulta = $statusDados[0];
                                    $statusConsultaInicio = (!empty($statusDados[1])) ? date('Y-m-d H:i:s', $statusDados[1]) : null;

                                    if ($statusDados[0] == 'desmarcado') {
                                        $ConsultaStatusRepository = new ConsultaStatusRepository;
                                        $rowStatusConsulta = $ConsultaStatusRepository->getById($rowConsulta->identificador, $statusDados[2]);

                                        $desmarcadoPor = ($rowStatusConsulta->desmarcado_por == 1) ? 'paciente' : 'doutor';
                                        $razaoDesmarcacao = (!empty($rowStatusConsulta->razao_desmarcacao)) ? $rowStatusConsulta->razao_desmarcacao : null;

//                dd($rowStatusCOnsulta);
                                    }
                                } else {
                                    $statusConsulta = null;
                                    $statusConsultaInicio = null;
                                }


                                $dataNascimento = (!empty($rowConsulta->dataNascPaciente)) ? Functions::dateBrToDB($rowConsulta->dataNascPaciente) : null;
                                $idade = (!empty($dataNascimento) and Functions::validateDate($dataNascimento)) ? (int) Functions::calculaIdade($dataNascimento) : null;
                                $rowUltimaConsulta = $ConsultaRepository->getUltimaConsultaPaciente($rowConsulta->identificador, $rowConsulta->doutores_id, $rowConsulta->pacientes_id, $rowConsulta->id);
                                $dadosUltConsulta = null;

                                if ($rowUltimaConsulta) {
                                    $dadosUltConsulta['data'] = $rowUltimaConsulta->data_consulta;
                                    $dadosUltConsulta['hora'] = $rowUltimaConsulta->hora_consulta;
                                }
                                //convenio do paciente
                                $convenioPaciente = [];
                                $ConvenioRepository = new ConvenioRepository();
                                $qrConv = $ConvenioRepository->getConveniosPacientes($rowConsulta->identificador, $rowConsulta->pacientes_id);

                                if (count($qrConv) > 0) {
                                    foreach ($qrConv as $rowConv) {
                                        $convenioPaciente[] = [
                                            'convenioId' => $rowConv->convenios_id,
                                            'convenioNome' => $rowConv->nomeConvenio,
                                            'numeroCarteira' => $rowConv->numero_carteira,
                                            'validadeCarteira' => $rowConv->validade_carteira,
                                            'doutoId' => $rowConv->doutores_id,
                                            'doutoNome' => $rowConv->nomeDoutor,
                                        ];
                                    }
                                }

                                $consultaPaga = (!empty($rowConsulta->idRecebimento)) ? true : false;

                                $dadosConsulta = array(
                                    'id' => $rowConsulta->id,
                                    'data' => $rowConsulta->data_consulta,
                                    'horario' => $rowConsulta->hora_consulta,
                                    'horarioFim' => (!empty($rowConsulta->hora_consulta_fim)) ? $rowConsulta->hora_consulta_fim : null,
                                    'confirmacao' => (!empty($rowConsulta->confirmacao) and $rowConsulta->confirmacao != 'nao') ? true : false,
                                    'statusConsulta' => $statusConsulta,
                                    'statusConsultaInicio' => $statusConsultaInicio,
                                    'desmarcadoPor' => $desmarcadoPor,
                                    'razaoDesmarcacao' => $razaoDesmarcacao,
                                    'observacoes' => Functions::utf8ToAccentsConvert($rowConsulta->dados_consulta),
                                    'doutor' => [
                                        'id' => $rowConsulta->doutores_id,
                                        'nome' => $rowConsulta->nomeDoutor
                                    ],
                                    'paciente' => [
                                        'id' => $rowConsulta->pacientes_id,
                                        'nome' => ($rowConsulta->nomePaciente),
                                        'sobrenome' => $rowConsulta->sobrenomePaciente,
                                        'nomeCompleto' => $rowConsulta->nomePaciente . ' ' . $rowConsulta->sobrenomePaciente,
                                        'telefonePaciente' => trim(str_replace('(', '', str_replace(')', '', str_replace(' ', '', str_replace('-', '', $rowConsulta->telefonePaciente))))),
                                        'celularPaciente' => trim(str_replace('(', '', str_replace(')', '', str_replace(' ', '', str_replace('-', '', $rowConsulta->celularPaciente))))),
                                        'dataNascPaciente' => $dataNascimento,
                                        'idade' => $idade,
                                        'sexoPaciente' => $rowConsulta->sexoPaciente,
                                        'ultimaConsulta' => $dadosUltConsulta,
                                        'convenios' => (count($convenioPaciente) > 0) ? $convenioPaciente : null,
                                    ],
                                    'procedimentos' => $dadosProcedimento,
                                    'convenio' => (isset($arrayProcConv[0])) ? $arrayProcConv[0] : [
                                            'id' => $rowConsulta->convenios_id,
                                            'nome' => Functions::correcaoUTF8Decode($rowConsulta->nomeConvenio),
                                    ],
                                    'encaixe' => ($rowConsulta->encaixe == 1) ? true : false,
                                    'pago' => $consultaPaga,
//                                    'encaixeObservacao' =>$rowConsulta->encaixe_observacao,
//                                    'encaixeAutorizado_por' =>$rowConsulta->encaixe_autorizado_por,
                                );

                                //prontuarios
                                if (isset($dadosFiltro['showProntuarios']) and $dadosFiltro['showProntuarios'] == true) {
                                    $ProntuarioService = new ProntuarioService;
                                    $qrProntuarios = $ProntuarioService->getByConsultaId($idDominio, $rowConsulta->id);
                                    if ($qrProntuarios != null) {
                                        $dadosConsulta['prontuarios'] = $qrProntuarios;
                                    }
                                }



                                $RETORNO[$contResult1]['horariosList'][$contResult]['consultas'][] = $dadosConsulta;
                            }

                            //ordenando o array por encaixe
                            if (isset($RETORNO[$contResult1]['horariosList'][$contResult]['consultas'])) {
                                $tempArray = array_column($RETORNO[$contResult1]['horariosList'][$contResult]['consultas'], 'encaixe');
                                array_multisort($tempArray, SORT_ASC, $RETORNO[$contResult1]['horariosList'][$contResult]['consultas']);
                            }
                        }




                        //Verifica Consulas agendadas para caluclar o percentual de ocupçap



                        if ($percentualOcupacao) {
                            if ($arrayConsultasHR != null) {
                                $horarioFimMaior = strtotime($rowHorarios['horario']);
                                foreach ($CONSULTAS['data'][$dataAg][$rowHorarios['horario']] as $rowConsulta) {
                                    if (!empty($rowConsulta->hora_consulta_fim) and strtotime($rowConsulta->hora_consulta_fim) > $horarioFimMaior) {
                                        $horarioFimMaior = strtotime($rowConsulta->hora_consulta_fim);
                                    }
                                }
                                $dataConsIni = strtotime($rowHorarios['horario']);
                                $dataDiffCons = $horarioFimMaior - $dataConsIni;
                                $dataDiffCons = ($dataDiffCons / 60 / $qrHorarios['intervalo']);
                                $countHorariosOcup += ($dataDiffCons == 0) ? 1 : $dataDiffCons;
//                    print_r($rowHorario['horario'] . ' - ' . date('H:i', $horarioFimMaior) . ' - ' . $dataDiffCons . '<br>');
                                unset($CONSULTAS['data'][$dataAg][$rowHorarios['horario']]);
                            }
                        }







                        $dataTerminoAVA = null;
                        $dataTerminoAVA = date('H:i', strtotime($dataAg . ' ' . $rowHorarios['horario'] . "+{$qrHorarios['intervalo']} minutes "));

//                    $RETORNO[$dataAg]['horariosList'][$contResult]['date'] = Functions::dateDbToBr($dataAg);
                        $RETORNO[$contResult1]['horariosList'][$contResult]['inicio'] = $rowHorarios['horario'];
                        $RETORNO[$contResult1]['horariosList'][$contResult]['fim'] = $dataTerminoAVA;
//                    unset($RETORNO[$contResult1]['horariosList']);
                        $contResult++;
                    }
                }
            }
            $RETORNO[$contResult1]['totalHorariosDisponivel'] = $totalHorariosDisponiveis;

            if ($percentualOcupacao) {
                unset($RETORNO[$contResult1]['horariosList']);

                $totalConsultas = ($countHorariosOcup);
                $totalMarcacoes = ($totalHorariosAgendaveis) * $rowDefinicoesMarcDoutor->limite_consultas;

                $valorPercentualOcup = ($totalMarcacoes == 0 ) ? 0 : Functions::calculaPorcertagemParcial($totalConsultas, $totalMarcacoes);
                $RETORNO[$contResult1]['percentualOcupacao'] = number_format($valorPercentualOcup, 2);
            }

            unset($RETORNO[$contResult1]['horarios']);

            $dataAg = date('Y-m-d', strtotime($dataAg . " +1 days"));
            $contResult1++;
        };

//        dd($RETORNO);
        return $RETORNO;
    }

    /*     * Verifica se um horário ou intervalo de hora está disponível para agendamento e compromissos
     * 
     * @param type $idDominio
     * @param type $idDoutor
     * @param type $data
     * @param type $horario
     * @param type $horarioFim
     * @return type
     */

    public function verificaHorarioDisponivel($idDominio, $idDoutor, $data, $horario, $horarioFim = null) {


        $qrHorarios = $this->listHorarios($idDominio, $idDoutor, $data, $horario, $data, $horarioFim);

        if (!isset($qrHorarios[0]['horariosList'])) {
            $this->returnError('', 'Horário indisponível');
        }

        $verficaHorario = false;
        $erroHorafim = null;
        foreach ($qrHorarios[0]['horariosList'] as $rowHorario) {

            if ($horario == $rowHorario['inicio'] and $rowHorario['disponivel'] == true) {
                $verficaHorario = true;
            }

            //Verificando os horáios entre o incio e termino se estão disponiveis
            if (!empty($horarioFim)) {
                if (strtotime($rowHorario['inicio']) > strtotime($horario) and strtotime($horarioFim) > strtotime($rowHorario['inicio']) and $rowHorario['disponivel'] == false) {
                    $erroHorafim = $rowHorario;
                    break;
                }
            }
        }

        if (!$verficaHorario) {
            return $this->returnError('', 'Horário indisponível');
        }

        if (isset($erroHorafim) and $erroHorafim !== null) {
            return $this->returnError('', 'Horário de término indisponível. O horário ' . $erroHorafim['inicio'] . ' não está disponível');
        }

        return $this->returnSuccess('', '');
    }

}
