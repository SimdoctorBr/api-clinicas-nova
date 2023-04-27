<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\Clinicas;

use App\Services\BaseService;
use DateTime;
use App\Helpers\Functions;
use App\Services\Clinicas\CalculosService;
use App\Repositories\Clinicas\AgendaRepository;
use App\Repositories\Clinicas\AgendaFilaEsperaRepository;
use App\Repositories\Clinicas\DefinicaoMarcacaoGlobalRepository;
use App\Repositories\Clinicas\DefinicaoHorarioRepository;
use App\Repositories\Clinicas\ConsultaRepository;
use App\Repositories\Clinicas\ConsultaStatusRepository;
use App\Repositories\Clinicas\StatusRefreshRepository;
use App\Services\Clinicas\HorariosService;
use App\Repositories\Clinicas\BloqueiosHorariosRepository;
use App\Repositories\Clinicas\Consulta\ConsultaProcedimentoRepository;
use App\Services\CacheService;

/**
 * Description of Activities
 *
 * @author ander
 */
class AgendaService extends BaseService {

    private $horarioService;

    public function __construct(HorariosService $horServ) {
        $this->horariosService = $horServ;
    }

    public function getResumoDiario($idDominio, $doutorId, $request) {

        $validate = validator($request->query(), [
            'data' => 'required|date',
                ], [
            'data.required' => 'Data não informada',
            'data.date' => 'Data inválida',
        ]);

        if ($validate->fails()) {
            return $this->returnError($validate->errors(), $validate->errors()->all());
        }

        $AgendaRep = new AgendaRepository();
//        $retorno = $AgendaRep->getResumo($idDominio, $doutorId, $request->query('data'));

        $ConsultaRepository = new ConsultaRepository;
        $dadosFiltro['doutoresId'] = $doutorId;
        $dadosFiltro['dataInicio'] = $request->query('data');
        $dadosFiltro['orderBy'] = 'data_consulta, hora_consulta ASC';
        $qrConsulta = $ConsultaRepository->getAll($idDominio, $dadosFiltro);

        $horaAnt = null;
        $horaAbertura = $ultimoHorario = null;
        $qntConsultas = $qntProcedimentos = 0;

//        if(auth('clinicas')->user()->id == 4055){
//            dd($qrConsulta);
//        }
        foreach ($qrConsulta as $rowConsulta) {


            if (!empty($horaAbertura)) {
                if (strtotime($horaAnt) < strtotime($horaAbertura)) {
                    $horaAbertura = $rowConsulta->hora_consulta;
                }
            } else {
                $horaAbertura = $rowConsulta->hora_consulta;
            }
            if (!empty($ultimoHorario)) {
                if (strtotime($horaAnt) >= strtotime($ultimoHorario)) {
                    $ultimoHorario = $rowConsulta->hora_consulta;
                }
            } else {
                $ultimoHorario = $rowConsulta->hora_consulta;
            }


            $ConsultaProcedimentoRepository = new ConsultaProcedimentoRepository;
            $qrProc = $ConsultaProcedimentoRepository->getByConsultaId($idDominio, $rowConsulta->id);

            foreach ($qrProc as $proc) {
                $qntProcConsulta = Functions::qntProcedimentosDocBizz(utf8_decode($proc->nome_proc), $proc->qnt, $proc->convenios_id, count($qrProc));
                $qntConsultas += $qntProcConsulta['consulta'];
                $qntProcedimentos += $qntProcConsulta['procedimento'];
            }
            $horaAnt = $rowConsulta->hora_consulta;
        }





        $retorno[] = ['horaPrimeiroAtendimento' => substr($horaAbertura, 0, 5),
            'horaUltimoAtendimento' => substr($ultimoHorario, 0, 5),
            'totalProcedimentos' => $qntProcedimentos,
            'totalConsultas' => $qntConsultas];

        return $this->returnSuccess($retorno);
    }

    public function getFilaEspera($idDominio, $doutorId, $request) {


        $DefinicaoMarcacaoGlobalRepository = new DefinicaoMarcacaoGlobalRepository();
        $rowDefGlobal = $DefinicaoMarcacaoGlobalRepository->getDadosDefinicao($idDominio, ['somente_pago_fila']);

        $dadosFiltro['somente_pago_fila'] = $rowDefGlobal->somente_pago_fila;

        $AgendaFilaEsperaRep = new AgendaFilaEsperaRepository();
        $qr = $AgendaFilaEsperaRep->getFila($idDominio, $doutorId, $dadosFiltro);

        $DefinicaoHorarioRepository = new DefinicaoHorarioRepository();
        $rowVerificaHorario = $DefinicaoHorarioRepository->verificaDefinicoesHorariosGerenciamento($idDominio, $doutorId, date('Y-m-d'));

        $retorno = [];

        if (count($qr) > 0) {
            foreach ($qr as $row) {


//                dd($rowVerificaHorario);
                $duracaoConsulta = '';
                $horaConsultaFim = $row->hora_consulta_fim;
                if (!empty($row->hora_consulta_fim)) {
                    $duracaoConsulta = $row->qntHorasAtendimento;
                } else {
                    $duracaoConsulta = '00:' . sprintf('%02s', $rowVerificaHorario->intervalo) . ':00';
                    $horaConsultaFim = date('H:i:s', strtotime("$row->hora_consulta + " . $rowVerificaHorario->intervalo . " minutes"));
                }





                $retorno[] = array(
                    'id' => $row->id,
                    'consultaId' => $row->consultas_id,
                    'ordem' => $row->ordem,
                    'dataCad' => $row->data_cad,
                    'doutorId' => $row->doutores_id,
                    'dataConsulta' => $row->data_consulta,
                    'horaConsulta' => $row->hora_consulta,
                    'horaConsultaFim' => $horaConsultaFim,
                    'duracaoConsulta' => $duracaoConsulta,
                    'nomeDoutor' => $row->nomeDoutor,
                    'statusConsulta' => $row->status_consulta,
                    'horaStatus' => $row->horaStatus,
                    'observacoes' => $row->dados_consulta,
                    'videoconferencia' => $row->videoconferencia,
                    'pausada' => $row->pausada,
                    'inicioAtendimento' => $row->inicio_atendimento,
                    'paciente' => [
                        'id' => $row->pacientes_id,
                        'nome' => $row->nomePaciente,
                        'sobrenome' => $row->sobrenomePaciente,
                    ]
                );
            }
        }
//        else{
//            return $this->returnError(null, ['Sem agendamentos na fila de espera']);
//        }




        return $this->returnSuccess($retorno);
    }

    /**
     * Inicia, pausa e finaliza o atendimento
     * @param type $idDominio
     * @param type $consultaId
     * @param type $status
     */
    public function alteraAtendimentoStatus($idDominio, $request, $consultaId) {

        $validate = validator($request->input(), [
            'status' => 'required'
                ], [
            'status.required' => 'Informe o status da consulta'
        ]);

        if ($validate->fails()) {
            return $this->returnError($validate->errors(), $validate->errors()->all());
        } else {
            if (!in_array($request->input('status'), array(1, 2, 3))) {
                return $this->returnError(null, array('Status inválido'));
            }

            $StatusRefreshRepository = new StatusRefreshRepository();
            $ConsultaRepository = new ConsultaRepository;
            $ConsultaStatusRepository = new ConsultaStatusRepository;

            $rowConsulta = $ConsultaRepository->getById($idDominio, $consultaId);
//            dd($rowConsulta);
            if ($rowConsulta->statusConsulta != 'jaFoiAtendido') {
                switch ($request->input('status')) {
                    case 1:
                        if ($rowConsulta->statusConsulta != 'estaSendoAtendido') {
                            if (empty($rowConsulta->pausada)) {
                                $dados['consulta_id'] = $consultaId;
                                $dados['identificador'] = $idDominio;
                                $dados['status'] = 'estaSendoAtendido';
                                $dados['hora'] = time();
//                                $dados['administrador_id'] = auth('clinicas')->user()->id;
//                                $dados['nome_administrador'] = auth('clinicas')->user()->nome;
                                $ConsultaStatusRepository->alteraStatus($idDominio, $consultaId, $dados);
                                $StatusRefreshRepository->insertAgenda($idDominio, $rowConsulta->doutores_id);
                            }
                        } else {
                            $dadosUpdate['pausada'] = null;
                            $ConsultaRepository->updateConsulta($idDominio, $consultaId, $dadosUpdate);
                        }
                        break;
                    case 2:if ($rowConsulta->statusConsulta == 'estaSendoAtendido') {
                            $dadosUpdate['pausada'] = 1;
//                            $dadosUpdate['pausa_tempo'] = $duracaoAtual;
                            $ConsultaRepository->updateConsulta($idDominio, $consultaId, $dadosUpdate);
                        }
                        break;
                    case 3:
                        if ($rowConsulta->statusConsulta != 'estaSendoAtendido' and $rowConsulta->statusConsulta != 'faltou'
                                and $rowConsulta->statusConsulta != 'desmarcado') {
                            $dados['consulta_id'] = $consultaId;
                            $dados['identificador'] = $idDominio;
                            $dados['status'] = 'jaFoiAtendido';
                            $dados['hora'] = time();
//                                $dados['administrador_id'] = auth('clinicas')->user()->id;
//                                $dados['nome_administrador'] = auth('clinicas')->user()->nome;
                            $ConsultaStatusRepository->alteraStatus($idDominio, $consultaId, $dados);
                            $ConsultaRepository->updateConsulta($idDominio, $consultaId, $dadosUpdate);
                        }
                        break;
                }
            } else {
                return $this->returnError(null, array('A consulta está finalizada'));
            }
        }
    }

    public function getHorariosByDoutor($idDominio, $doutorId, $request) {

        $validate = validator($request->query(), [
            'data' => 'required|date',
            'dataFim' => 'date'
                ], [
            'data.required' => 'Data não informada',
            'data.date' => 'Data inválida',
            'dataFim.date' => 'Data inválida',
        ]);

        $filtroWith = ($request->has('with') && !empty($request->query('with'))) ? explode(',', $request->query('with')) : [];
        $exibeConsultas = (in_array('consultas', $filtroWith)) ? true : false;
        $dadosFiltro['showProntuarios'] = ($request->has('showProntuarios') && $request->query('showProntuarios') == 'true') ? true : false;
        $dadosFiltro['exibeConsultaTermino'] = ($request->has('exibeConsultaTermino') && $request->query('exibeConsultaTermino') == 'true') ? true : false;

        if ($validate->fails()) {
            return $this->returnError($validate->errors(), $validate->errors()->all());
        } else {
            $horariosList = $this->horariosService->listHorarios($idDominio, $doutorId, $request->query('data'), null, $request->query('dataFim'), null, null, null, false, $exibeConsultas, $dadosFiltro);
            return $this->returnSuccess($horariosList);
        }
    }

    public function getPercentualCalPreenchido($idDominio, $doutorId, $request) {


        $validate = validator($request->query(), [
            'mes' => 'required|numeric',
            'ano' => 'required|numeric',
                ], [
            'mes.required' => 'Mês não informado',
            'ano.required' => 'Ano não informado',
            'mes.numeric' => 'Mês inválido',
            'ano.numeric' => 'Ano inválido',
        ]);

     if ($validate->fails()) {
            return $this->returnError($validate->errors(), $validate->errors()->all());
        } else {
            $mes = sprintf('%02s', $request->query('mes'));
            $ano = $request->query('ano');
            $dataIni = $ano . '-' . $mes . '-01';
            $dataFim = date('t', strtotime($dataIni));
            $dataFim = $ano . '-' . $mes . '-' . $dataFim;

            $CacheService = new CacheService;
            $keyCache = $idDominio . $doutorId. request()->server('REQUEST_URI');
            $verifyCache = $CacheService->verifyCache($keyCache);
            if ($verifyCache) {
                return $this->returnSuccess($verifyCache);
            } else {

                $horariosList = $this->horariosService->listHorarios($idDominio, $doutorId, $dataIni, null, $dataFim, null, null, null, true);
                $CacheService->createCache($keyCache,$horariosList, 600);
            }


            return $this->returnSuccess($horariosList);
        }
    }

    public function getConsultasAlertas($idDominio, $dadosFiltro = null) {

        $ConsultaRepository = new ConsultaRepository;
        $filtroConsultas['dataInicio'] = date('Y-m-d');
        $filtroConsultas['statusConsulta'] = 'jaFoiAtendido,faltou,desmarcado';
//        $qrConsultas = $ConsultaRepository->getAll($idDominio, $filtroConsultas);
        $qrConsultas = $ConsultaRepository->getAlertas($idDominio);
//        dd($qrConsultas);
        $retorno = [];
        if (count($qrConsultas) > 0) {
            foreach ($qrConsultas as $row) {

                $msgAlerta = null;

                $status = explode('_', $row->statusConsulta);

                if ($status[0] == 'faltou' or $status[0] == 'desmarcado') {
                    $msgAlerta = 'Verifique a agenda do(a) Dr(a). ' . $row->nomeDoutor;
                } else
                if (empty($status[0]) and!empty($row->idRecebimento)) {
                    $msgAlerta = 'Encerrar consulta';
                } else if (empty($row->idRecebimento) and $status[0] == 'jaFoiAtendido') {
                    $msgAlerta = 'Cobrança de valores';
                }


                $retorno[] = [
                    'id' => $row->id,
                    'paciente' => [
                        'id' => $row->pacientes_id,
                        'nome' => $row->nomePaciente,
                        'sobrenome' => $row->sobrenomePaciente,
                    ],
                    'doutor' => [
                        'id' => $row->doutores_id,
                        'nome' => $row->nomeDoutor,
                    ],
                    'statusConsulta' => $status[0],
                    'pago' => (!empty($row->idRecebimento)) ? true : false,
                    'msgAlerta' => $msgAlerta
                ];
            }
        }

        return $this->returnSuccess($retorno);
    }


}
