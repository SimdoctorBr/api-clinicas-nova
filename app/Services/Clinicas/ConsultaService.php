<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\Clinicas;

use App\Services\BaseService;
use DateTime;
use App\Services\Clinicas\CalculosService;
use App\Services\Clinicas\Paciente\ProntuarioService;
use App\Repositories\Clinicas\AgendaRepository;
use App\Repositories\Clinicas\AgendaFilaEsperaRepository;
use App\Repositories\Clinicas\DefinicaoMarcacaoConsultaRepository;
use App\Repositories\Clinicas\DefinicaoMarcacaoGlobalRepository;
use App\Repositories\Clinicas\DefinicaoHorarioRepository;
use App\Repositories\Clinicas\ConsultaRepository;
use App\Repositories\Clinicas\ConsultaStatusRepository;
use App\Repositories\Clinicas\StatusRefreshRepository;
use App\Services\Clinicas\Consulta\ConsultaProcedimentoService;
use App\Helpers\Functions;
use App\Repositories\Clinicas\DiasHorariosBloqueadosRepository;
use App\Services\Clinicas\Doutores\DoutoresService;
use App\Services\Clinicas\PacienteService;
use App\Services\Clinicas\HorariosService;
use App\Services\Gerenciamento\DominioService;
use App\Services\PagSeguroApi\PagSeguroItensAPI;
use App\Services\PagSeguroApi\PagSeguroApiService;
use App\Repositories\Clinicas\PagSeguroConfigRepository;
use App\Services\Clinicas\Emails\EmailAgendamentoService;
use App\Services\Clinicas\Consulta\ConsultaReservadaService;
use App\Services\Clinicas\LogAtividadesService;
use App\Repositories\Clinicas\Financeiro\RecebimentoRepository;
use App\Repositories\Clinicas\ProcedimentosRepository;
use App\Repositories\Clinicas\DoutoresRepository;
use App\Repositories\Clinicas\ConvenioRepository;
use App\Services\Clinicas\Financeiro\GatewayPagamentos\CobrancaPagSeguroService;
use App\Repositories\Clinicas\Asaas\AsaasConfigRepository;
use App\Services\Clinicas\Financeiro\GatewayPagamentos\Asaas\CobrancaAsaasService;
Use App\Services\Clinicas\Financeiro\GatewayPagamentos\Asaas\PacientesAsaasPagamentosService;
use App\Services\Clinicas\EspecialidadeService;

/**
 * Description of Activities
 *
 * @author ander
 */
class ConsultaService extends BaseService {

    private $consultaRepository;
    private $definicaoMarcacaoConsultaRepository;
    private $prontuarioService;
    private $consultaProcedimentoService;

    public function __construct() {
        $this->consultaRepository = new ConsultaRepository;
        $this->definicaoMarcacaoConsultaRepository = new DefinicaoMarcacaoConsultaRepository;
        $this->prontuarioService = new ProntuarioService;
        $this->consultaProcedimentoService = new ConsultaProcedimentoService;
    }

    private function responseFields($rowConsulta, $nomeDominio, $showProcedimentos = false, $showProntuario = false) {



        $dataNascimento = (!empty($rowConsulta->dataNascPaciente)) ? Functions::dateBrToDB($rowConsulta->dataNascPaciente) : null;

        $idade = (!empty($dataNascimento) and Functions::validateDate($dataNascimento)) ? (int) Functions::calculaIdade($dataNascimento) : null;

        $rowUltimaConsulta = $this->consultaRepository->getUltimaConsultaPaciente($rowConsulta->identificador, $rowConsulta->doutores_id, $rowConsulta->pacientes_id, $rowConsulta->id);

        $retorno = array(
            'id' => $rowConsulta->id,
            'paciente' => [
                'id' => $rowConsulta->pacientes_id,
                'nomePaciente' => $rowConsulta->nomePaciente,
                'sobrenomePaciente' => $rowConsulta->sobrenomePaciente,
//                'cartaoNacionalSaude' => $rowConsulta->cartao_nacional_saude,
                'telefonePaciente' => trim(str_replace('(', '', str_replace(')', '', str_replace(' ', '', str_replace('-', '', $rowConsulta->telefonePaciente))))),
                'celularPaciente' => trim(str_replace('(', '', str_replace(')', '', str_replace(' ', '', str_replace('-', '', $rowConsulta->celularPaciente))))),
                'dataNascPaciente' => $dataNascimento,
                'idade' => $idade,
                'sexoPaciente' => $rowConsulta->sexoPaciente,
//                'nomePacienteCompleto' => $rowConsulta->nomePacienteCompleto,
//                'marcadoPor' => $rowConsulta->marcadoPor,
//                'emailPaciente' => $rowConsulta->emailPaciente,
//                'numeroCarteira' => $rowConsulta->numero_carteira,
//                'validadeCarteira' => $rowConsulta->validade_carteira,
                'cpfPaciente' => $rowConsulta->cpfPaciente,
//                'nomeTipoSanguineo' => $rowConsulta->nomeTipoSanguineo,
//                'nomeFatorRh' => $rowConsulta->nomeFatorRh,
            ],
            'doutor' => [
                'id' => $rowConsulta->doutores_id,
                'nomeDoutor' => $rowConsulta->nomeDoutor,
                'urlFoto' => (!empty($rowConsulta->nomeFotoDoutor)) ? env('APP_URL_CLINICAS') . '/' . $nomeDominio . '/arquivos/fotos_doutor/' . $rowConsulta->nomeFotoDoutor : null,
//                'conselhoProfissionalNumero' => $rowConsulta->conselho_profissional_numero,
//                'conselhoUfId' => $rowConsulta->conselho_uf_id,
//                'conselhoUfSigla' => $rowConsulta->siglaUfConselho,
//                'codigoCbo' => $rowConsulta->codigoCbo,
//                'cboId' => $rowConsulta->cbo_s_id,
//                'siglaConselhoProfissional' => $rowConsulta->siglaConselhoProfissional,
//                'conselhoProfissionalId' => $rowConsulta->conselho_profissional_id,
            ],
            'convenio' => ['id' => $rowConsulta->convenios_id,
                'nome' => Functions::correcaoUTF8Decode($rowConsulta->nomeConvenio)]
            ,
            'mensagem' => $rowConsulta->mensagem,
            'pacientesSemCadastroId' => $rowConsulta->pacientes_sem_cadastro_id,
            'confirmacao' => (empty($rowConsulta->confirmacao)) ? 'nao' : $rowConsulta->confirmacao,
//            'emailProximidadeEnviado' => $rowConsulta->email_de_proximidade,
//            'emailProximidadeEnviado2' => $rowConsulta->email_de_proximidade2,
//            'smsProximidadeEnviado' => $rowConsulta->sms_de_proximidade,
//            'smsProximidadeEnviado2' => $rowConsulta->sms_de_proximidade2,
//            'smsProximidadeEnviado2' => $rowConsulta->sms_de_proximidade2,
//                'dataAgendamento' => $rowConsulta->data_agendamento,
//                'mostraHistorico' => $rowConsulta->mostra_historico,
//                'emailsEnviados' => $rowConsulta->emails_enviados,
//                'smsEnviados' => $rowConsulta->sms_enviados,
            'observacoes' => Functions::utf8ToAccentsConvert($rowConsulta->dados_consulta),
//                'numero_tiss' => $rowConsulta->numero_tiss,
            'dataConsulta' => $rowConsulta->data_consulta,
            'horaConsulta' => $rowConsulta->hora_consulta,
            'horaConsultaFim' => $rowConsulta->hora_consulta_fim,
            'valorRecebido' => $rowConsulta->valor_recebido,
            'valorTroco ' => $rowConsulta->valor_troco,
            'encaixe' => $rowConsulta->encaixe,
            'encaixeObservacao' => $rowConsulta->encaixe_observacao,
            'encaixeAutorizadoPor' => $rowConsulta->encaixe_observacao,
        );

        //Especialidades
        $retorno['doutor']['especialidades'] = null;
        if (isset($rowConsulta->nomeEspecialidade) and!empty($rowConsulta->nomeEspecialidade)) {
            $retorno['doutor']['especialidades'][] = array('nome' => utf8_decode($rowConsulta->nomeEspecialidade));
        } elseif (isset($rowConsulta->outra_especialidade) and!empty($rowConsulta->outra_especialidade)) {
            $retorno['doutor']['especialidades'][] = array('nome' => $rowConsulta->outra_especialidade);
        }

        $EspecialidadeService = new EspecialidadeService;
        $qrEspecialidades = $EspecialidadeService->getByDoutorId($rowConsulta->identificador, $rowConsulta->doutores_id);
        if ($qrEspecialidades['success']) {
            $retorno['doutor']['especialidades'] = $qrEspecialidades['data'];
        }
        ////////


        if ($rowUltimaConsulta) {
            $retorno['paciente']['ultimaConsulta']['data'] = $rowUltimaConsulta->data_consulta;
            $retorno['paciente']['ultimaConsulta']['hora'] = $rowUltimaConsulta->hora_consulta;
        }

        if (!empty($rowConsulta->statusConsulta)) {

            $statusDados = explode('_', $rowConsulta->statusConsulta);

            $retorno['statusConsulta'] = $statusDados[0];
            $retorno['horaStatus'] = (!empty($statusDados[1])) ? date('Y-m-d H:i:s', $statusDados[1]) : '';

            if ($statusDados[0] == 'desmarcado') {
                $ConsultaStatusRepository = new ConsultaStatusRepository;
                $rowStatusConsulta = $ConsultaStatusRepository->getById($rowConsulta->identificador, $statusDados[2]);

                $retorno['desmarcadoPor'] = ($rowStatusConsulta->desmarcado_por == 1) ? 'paciente' : 'doutor';
                $retorno['razaoDesmarcacao'] = (!empty($rowStatusConsulta->razao_desmarcacao)) ? utf8_decode($rowStatusConsulta->razao_desmarcacao) : null;

//                dd($rowStatusCOnsulta);
            } else
            if ($statusDados[0] == 'faltou') {
                $ConsultaStatusRepository = new ConsultaStatusRepository;
                $rowStatusConsulta = $ConsultaStatusRepository->getById($rowConsulta->identificador, $statusDados[2]);
                $retorno['motivoFalta'] = (!empty($rowStatusConsulta->obs_falta)) ? utf8_decode($rowStatusConsulta->obs_falta) : null;

//                dd($rowStatusCOnsulta);
            }
        } else {
            $retorno['statusConsulta'] = null;
            $retorno['horaStatus'] = null;
            $retorno['desmarcadoPor'] = null;
            $retorno['razaoDesmarcacao'] = null;
        }

        $retorno['videoconferencia'] = ['status' => false, 'codigo' => null, 'link' => null];

        if ($rowConsulta->videoconferencia == 1) {
            $retorno['videoconferencia'] = [
                'status' => true,
                'codigo' => $rowConsulta->codigo_id_videoconf,
                'videoPago' => $rowConsulta->video_conf_pago,
                'link' => $this->getLinkVideo($rowConsulta->codigo_id_videoconf, $nomeDominio),
            ];
        }

        $retorno['pagSeguro'] = [
            'status' => false,
            'codigoRefPag' => null,
            'link' => null,
            'validadeLink' => null
        ];

        if (isset($rowConsulta->idPacAssasPag) and!empty($rowConsulta->idPacAssasPag)) {
            $PacientesAsaasPagamentosService = new PacientesAsaasPagamentosService;
            $statusAsaas = $PacientesAsaasPagamentosService->statusCobrancaPorId($rowConsulta->statusCobrancaAssas);
            $retorno['linkCobranca'] = [
                'gateway' => 'Asaas',
                'linkCobranca' => $rowConsulta->linkPagAssas,
                'dataVencimento' => $rowConsulta->dtVencimentoAssas,
                'dataPagamento' => $rowConsulta->dtPagamentoAssas,
                'linkComprovante' => $rowConsulta->linkComprovanteAssas,
                'status' => $statusAsaas['status'],
            ];
        }


        if (!empty($rowConsulta->link_pagseguro)) {

            $PagSeguroApiService = new PagSeguroApiService();
            $retorno['linkCobranca'] = [
                'gateway' => 'PagSeguro',
                'linkCobranca' => $rowConsulta->link_pagseguro,
                'dataVencimento' => null,
                'dataPagamento' => null,
                'linkComprovante' => null,
                'status' => $PagSeguroApiService->getStatusCode($rowConsulta->pag_seguro_status),
            ];

            $retorno['pagSeguro'] = [
                'status' => true,
                'codigoRefPag' => $rowConsulta->cod_ref_pagseguro,
                'link' => $rowConsulta->link_pagseguro,
                'validadeLink' => $rowConsulta->pagseguro_validade_link
            ];
        }

        $retorno['pago'] = false;
        if (!empty($rowConsulta->idRecebimento)) {
            $retorno['pago'] = true;
        }

        $retorno['desconto'] = null;
        $retorno['acrescimo'] = null;
        if (!empty($rowConsulta->tipo_desconto) and
                (
                (!empty($rowConsulta->percentual_desconto) and $rowConsulta->percentual_desconto > 0)
                or (!empty($rowConsulta->desconto_reais) and $rowConsulta->desconto_reais > 0)
                )) {

            $retorno['desconto']['tipo'] = $rowConsulta->tipo_desconto;
            $retorno['desconto']['motivo'] = $rowConsulta->motivo_desconto;
            $retorno['desconto']['valor'] = ($rowConsulta->tipo_desconto == 1) ? number_format($rowConsulta->percentual_desconto, 2, '.', '') : number_format($rowConsulta->desconto_reais, 2, '.', '');
        }
        if (!empty($rowConsulta->acrescimo_tipo)
                and (
                (!empty($rowConsulta->acrescimo_percentual) and $rowConsulta->acrescimo_percentual > 0 )
                or (!empty($rowConsulta->acrescimo_valor) and $rowConsulta->acrescimo_valor > 0)
                )
        ) {
            $retorno['acrescimo']['tipo'] = $rowConsulta->acrescimo_tipo;
            $retorno['acrescimo']['motivo'] = $rowConsulta->acrescimo_motivo;
            $retorno['acrescimo']['valor'] = ($rowConsulta->acrescimo_tipo == 1) ? number_format($rowConsulta->acrescimo_percentual, 2, '.', '') : number_format($rowConsulta->acrescimo_valor, 2, '.', '');
        }

        //pag_seguro_status
        //pag_seg_transaction_id
        //pagseguro_validade_link
        //pag_seguro_status
        //cod_ref_pagseguro
        //link_pagseguro
        //cod_pagseguro
        //lista procedimentos
        if ($showProcedimentos) {

            $retorno['procedimentos'] = null;
            $qrProcedimentos = $this->consultaProcedimentoService->getByConsultaId($rowConsulta->identificador, $rowConsulta->id);
//                        dd($qrProcedimentos);
            $retorno['procedimentos'] = $qrProcedimentos;
        }

        //listar prontuarios
        if ($showProntuario) {

            $retorno['prontuarios'] = null;
            $qrProntuario = $this->prontuarioService->getByConsultaId($rowConsulta->identificador, $rowConsulta->id);
            $retorno['prontuarios'] = $qrProntuario;
        }

        $retorno['procLancadoDoutor'] = null;
        if (isset($rowConsulta->consAtendAbertoId) and!empty($rowConsulta->consAtendAbertoId)) {
            $retorno['procLancadoDoutor']['id'] = $rowConsulta->consAtendAbertoId;
            $retorno['procLancadoDoutor']['dataCad'] = $rowConsulta->consAtendAbertoDtCad;
        }


        return $retorno;
    }

    private function validateStatusConsulta($statusConsulta) {

        $listStatusConsultas = Functions::statusConsultas();

        if (!is_array($statusConsulta)) {
            $statusConsulta = explode(',', $statusConsulta);
        }


        foreach ($statusConsulta as $status) {
            if (!in_array($status, $listStatusConsultas)) {
                return false;
            }
        }
        return true;
    }

    private function getLinkVideo($CodVideo, $nomeDominio) {
        return env('APP_URL_CLINICAS') . $nomeDominio . '/videoconf?c=' . $CodVideo;
    }

    public function getAll($idDominio, $request, $dadosQuery = null) {



        $validate = validator($dadosQuery, [
            'doutorId' => 'numeric',
            'data' => 'date',
            'dataFim' => 'date',
            'horaInicio' => 'date_format:H:i',
            'horaFim' => 'date_format:H:i',
                ], [
            'doutorId.required' => 'Doutor(a) não informado',
            'doutorId.numeric' => 'Doutor(a) não informado',
            'data.required' => 'Data não informada',
            'horaInicio.date_format' => 'Hora de início inválida',
            'horaFim.date_format' => 'Hora de término inválida',
        ]);

        $DominioService = new DominioService;
        $rowDominio = $DominioService->getById($idDominio);
        $rowDominio = $rowDominio['data'];

        $dadosFiltro = null;
        $dadosFiltro['doutoresId'] = (isset($dadosQuery['doutorId']) and!empty($dadosQuery['doutorId'])) ? $dadosQuery['doutorId'] : null;
        $arrayCamposFiltro = ['dataConsulta' => 'A.data_consulta', 'horaConsulta' => 'A.hora_consulta'];

        if ($validate->fails()) {
            return $this->returnError($validate->errors(), $validate->errors()->all());
        } else {



            //Ordenação
            if (isset($dadosQuery['orderBy']) and!empty($dadosQuery["orderBy"])) {
                $dadosFiltro['orderBy'] = $this->urlOrderByToFields($arrayCamposFiltro, $dadosQuery["orderBy"]);
            }

            //
            if (isset($dadosQuery['horaInicio']) and!empty($dadosQuery["horaInicio"])) {
                $dadosFiltro['horaInicio'] = $dadosQuery["horaInicio"];
            }
            if (isset($dadosQuery['horaFim']) and!empty($dadosQuery["horaFim"])) {
                $dadosFiltro['horaFim'] = $dadosQuery["horaFim"];
            }



            $dadosFiltro['dataInicio'] = (isset($dadosQuery['data']) and!empty($dadosQuery['data'])) ? $dadosQuery['data'] : null;
            $dadosFiltro['dataFim'] = (isset($dadosQuery['dataFim']) and!empty($dadosQuery['dataFim'])) ? $dadosQuery['dataFim'] : null;

            if (isset($dadosQuery['buscaPaciente']) and!empty($dadosQuery['buscaPaciente'])) {
                $dadosFiltro['buscaPaciente'] = $dadosQuery['buscaPaciente'];
            }

            if (isset($dadosQuery['statusConsulta']) and!empty($dadosQuery['statusConsulta'])) {
                if ($this->validateStatusConsulta($dadosQuery['statusConsulta'])) {
                    $dadosFiltro['statusConsulta'] = $dadosQuery['statusConsulta'];
                } else {
                    return $this->returnError(null, ['Um ou mais status da consulta inválidos']);
                }
            }

            if (isset($dadosQuery['pacienteId']) and!empty($dadosQuery['pacienteId'])) {
                $dadosFiltro['pacienteId'] = trim($dadosQuery['pacienteId']);
            }

            if (isset($dadosQuery['dataHoraLimite']) and!empty($dadosQuery['dataHoraLimite'])) {
                $dadosFiltro['dataHoraLimite'] = trim($dadosQuery['dataHoraLimite']);
            }

            if (isset($dadosQuery['statusSomenteAgendado']) and $dadosQuery['statusSomenteAgendado'] == true) {
                $dadosFiltro['statusSomenteAgendado'] = true;
            }

            if (isset($dadosQuery['dataHoraApartirDe']) and!empty($dadosQuery['dataHoraApartirDe'])) {
                $dadosFiltro['dataHoraApartirDe'] = $dadosQuery['dataHoraApartirDe'];
            }

            if ($rowDominio->habilita_assas == 1) {
                $dadosFiltro['asaasHabilitado'] = 1;
            }



            $page = (isset($dadosQuery['page']) and $dadosQuery['page'] > 0 ) ? $dadosQuery['page'] : 1;
            $perPage = (isset($dadosQuery['perPage']) and $dadosQuery['perPage'] > 0 and $dadosQuery['perPage'] <= 1000) ? $dadosQuery['perPage'] : 1000;

            $qrConsultas = $this->consultaRepository->getAll($idDominio, $dadosFiltro, $page, $perPage);

            $showProcedimentos = (isset($dadosQuery['showProcedimentos']) and $dadosQuery['showProcedimentos'] == 'true') ? true : false;
            $showProntuarios = (isset($dadosQuery['showProntuarios']) and $dadosQuery['showProntuarios'] == 'true') ? true : false;

            $retornoResult = [];
            if (count($qrConsultas['results']) > 0) {
                foreach ($qrConsultas['results'] as $chave => $row) {
                    $retornoResult[$chave] = $this->responseFields($row, $rowDominio->dominio, $showProcedimentos, $showProntuarios);
                }
            }

//            var_dump($retornoResult);
            $qrConsultas['results'] = $retornoResult;
            return $this->returnSuccess($qrConsultas);
        }
    }

    /**
     * Retorna as consulta em um array por data e hora  Ex. CONSULTAS[dataCOnsullta][horarios]
     * @param type $idDominio
     * @param type $data
     * @param type $dataFim
     * @param type $doutorId
     */
    public function getAllArrayData($idDominio, $doutorId, $data, $dataFim = null) {

        $dadosFiltro['dataInicio'] = $data;
        $dadosFiltro['dataFim'] = $dataFim;
        $dadosFiltro['doutoresId'] = $doutorId;
        $qrConsultas = $this->consultaRepository->getAll($idDominio, $dadosFiltro);
        $retorno = null;
        if (count($qrConsultas) > 0) {

            $i = 0;
            $horarioAnt = '';
            foreach ($qrConsultas as $row) {
                $horario = substr($row->hora_consulta, 0, 5);
                if ($horario != $horarioAnt) {
                    $i = 0;
                }
                $retorno[$row->data_consulta][$horario][$i] = $row;
                $horarioAnt = substr($row->hora_consulta, 0, 5);
                $i++;
            }
        }
        return $this->returnSuccess($retorno);
    }

    public function getById($idDominio, $consultaId, $dadosFiltro = null) {

        $DominioService = new DominioService;
        $rowDominio = $DominioService->getById($idDominio);
        $rowDominio = $rowDominio['data'];

        $ConsultaRep = new ConsultaRepository;

        $rowConsulta = $ConsultaRep->getById($idDominio, $consultaId);

        $showProcedimentos = (isset($dadosFiltro['showProcedimentos']) and $dadosFiltro['showProcedimentos'] == 'true') ? true : false;
        $showProntuarios = (isset($dadosFiltro['showProntuarios']) and $dadosFiltro['showProntuarios'] == 'true') ? true : false;
        if ($rowConsulta) {
            $retorno = $this->responseFields($rowConsulta, $rowDominio->dominio, $showProcedimentos, $showProntuarios);

            return $this->returnSuccess($retorno);
        } else {
            return $this->returnError(null, 'Consulta não encontrada');
        }
    }

    public function verificaDisponibilidadeConsultasHorario($idDominio, $doutorId, $data, $horario, $verificaConsultaNormal = true, $limiteEncaixe = true) {

        $qrConsultasMarcadas = $this->consultaRepository->getConsultasMarcadasHorario($idDominio, $doutorId, $data, $horario, $verificaConsultaNormal);

        $statusDisponivel = false;

        $rowDefinicoesConsultas = $this->definicaoMarcacaoConsultaRepository->getByDoutoresId($idDominio, $doutorId);

        $limite_consulta = $rowDefinicoesConsultas->limite_consultas;
        if ($limiteEncaixe) {
            $limite_consulta = $rowDefinicoesConsultas->limite_consultas + $rowDefinicoesConsultas->limite_encaixe_consulta;
        }




        $cont = 0;
        $consultaEstendida = false;
        if (count($qrConsultasMarcadas) > 0) {
            foreach ($qrConsultasMarcadas as $row) {

                $idConsulta = $row->id;
                if (!empty($row->hora_consulta_fim) and$row->hora_consulta_fim != '00:00:00') {
                    $consultaEstendida = true;
                }
                $cont++;
                if (!empty($row->statusConsulta) and $row->statusConsulta == 'desmarcado') {
                    $cont--;
                }
            }
        }



        $statusDisponivel = false;
        if ($cont >= $limite_consulta) {
            $statusDisponivel = false;
        } else {
            $statusDisponivel = TRUE;
        }

//        if ($consultaEstendida == false) {
//
//            if ($cont >= $limite_consulta) {
//                $statusDisponivel = false;
//            } else {
//                $statusDisponivel = TRUE;
//            }
//        } else {
//            
//            
//            $statusDisponivel = false;
//        }


        return $statusDisponivel;
    }

    public function transferirConsultaDoutor($idDominio, $idBloqueio = null, $consultaId, $doutorId, $data, $hora) {

        $DiasHorariosBloqueadosRepository = new DiasHorariosBloqueadosRepository;

        $campos['doutores_id'] = $doutorId;
        $campos['data_consulta'] = $data;
        $campos['hora_consulta'] = $hora;

        if (!empty($idBloqueio)) {
            $camposHIst['dias_horarios_bloqueados_id'] = $idBloqueio;
        }

        $rowConsultaOrigem = $this->consultaRepository->getById($idDominio, $consultaId);

        if ($rowConsultaOrigem) {

            $camposHIst['consultas_id'] = $consultaId;
            $camposHIst['data_origem'] = $rowConsultaOrigem->data_consulta;
            $camposHIst['hora_origem'] = $rowConsultaOrigem->hora_consulta;
            $camposHIst['doutor_id_origem'] = $rowConsultaOrigem->doutores_id;
            $camposHIst['data_destino'] = $data;
            $camposHIst['hora_destino'] = $hora;
            $camposHIst['doutor_id_destino'] = $doutorId;
            $camposHIst['identificador'] = $idDominio;
            $DiasHorariosBloqueadosRepository->insertHistoricoTransferencia($idDominio, $camposHIst);
        }


        $this->consultaRepository->updateConsulta($idDominio, $consultaId, $campos);
    }

    public function store($idDominio, $dadosInput, $dadosPac = null) {

        $dadosFiltroConsulta = null;
        $pacienteId = (isset($dadosInput['pacienteId']) and!empty($dadosInput['pacienteId'])) ? $dadosInput['pacienteId'] : null;
        $doutorId = $dadosInput['doutorId'];
        $data = $dadosInput['data'];
        $horario = $dadosInput['horario'];
        $horarioFim = (isset($dadosInput['horarioFim']) and!empty($dadosInput['horarioFim'])) ? substr($dadosInput['horarioFim'], 0, 5) : null;
        $dados_consulta = (isset($dadosInput['observacoes']) and!empty($dadosInput['observacoes'])) ? Functions::accentsToUtf8Convert($dadosInput['observacoes']) : null;

        $DominioService = new DominioService;
        $rowDominio = $DominioService->getById($idDominio);
        $rowDominio = $rowDominio['data'];

        if (
                isset($dadosInput['linkPagSeguro']) and $dadosInput['linkPagSeguro'] == true
                and isset($dadosInput['linkAsaas']) and $dadosInput['linkAsaas'] == true
        ) {
            return $this->returnError(null, 'Somente um dos gateway de pagamento pode se usado ');
        }


        $AsaasConfigRepository = new AsaasConfigRepository;
        if (isset($dadosInput['linkAsaas']) and $dadosInput['linkAsaas'] == true
        ) {
            $rowConfigAsaas = $AsaasConfigRepository->getConfig($idDominio);
            if (!$rowConfigAsaas) {
                return $this->returnError(null, 'Este perfil não possui o Asaas configurado.');
//            } elseif (!isset($dadosInput['formaPagAsaas']) or empty($dadosInput['formaPagAsaas'])) {
//                return $this->returnError(null, 'Forma de pagamento não informada');
            } elseif (isset($dadosInput['formaPagAsaas']) and!empty($dadosInput['formaPagAsaas']) and $dadosInput['formaPagAsaas'] != 'pix' and $dadosInput['formaPagAsaas'] != 'cartao') {
                return $this->returnError(null, 'Forma de pagamento  inválida.');
            } elseif (isset($dadosInput['formaPagAsaas']) and!empty($dadosInput['formaPagAsaas']) and $dadosInput['formaPagAsaas'] != 'pix' and $dadosInput['formaPagAsaas'] != 'cartao') {
                return $this->returnError(null, 'Forma de pagamento  inválida.');
            } elseif (!isset($dadosInput['paciente']['email']) or empty($dadosInput['paciente']['email'])) {
                return $this->returnError(null, 'Infome o e-mail do paciente');
            } elseif (!filter_var($dadosInput['paciente']['email'], FILTER_VALIDATE_EMAIL)) {
                return $this->returnError(null, 'E-mail do paciente inválido');
            } elseif (!isset($dadosInput['paciente']['cpf']) or empty($dadosInput['paciente']['cpf'])) {
                return $this->returnError(null, 'Informe o CPF do paciente');
            } elseif (!Functions::validateCPF($dadosInput['paciente']['cpf'])) {
                return $this->returnError(null, 'CPF do paciente inválido.');
            }
        }







        $ConsultaProcedimentoService = new ConsultaProcedimentoService();
        $PagSeguroConfigRepository = new PagSeguroConfigRepository();

        $rowPAgSeguroCOnfig = $PagSeguroConfigRepository->getDados($rowDominio->id);

        $ConsultaReservadaService = new ConsultaReservadaService;
        $HorariosService = new HorariosService;

        $DoutoresRepository = new DoutoresRepository();
        $qrDoutor = $DoutoresRepository->getById($idDominio, $doutorId);

        if (!$qrDoutor) {
            return $this->returnError('', 'Doutor(a) não encontrado');
        }

        $rowDoutor = $qrDoutor;

        $ProcedimentosRepository = new ProcedimentosRepository;
        $qrProcVideoPadrao = $ProcedimentosRepository->getAllProcedimentosVinculados($idDominio, null, null, $rowDoutor->proc_doutor_id_video);

        $Convenios = new ConvenioRepository;
        $rowConvenio = $Convenios->getConveniosPacientes($idDominio, $pacienteId, $doutorId);

        if (isset($dadosInput['tipoAtendimento']) and $dadosInput['tipoAtendimento'] == 'video' and!$rowDoutor->possui_videoconf) {
            return $this->returnError('', 'Este profissional não aceita consultas por vídeo');
        }


        //encaixe
        if (isset($dadosInput['encaixe']) and!empty($dadosInput['encaixe'])
                and $dadosInput['encaixe'] != 'true' and $dadosInput['encaixe'] != 'false') {
            return $this->returnError('', "O encaixe deve ser 'true' ou 'false'");
        }
        $dadosFiltroConsulta['encaixe'] = (isset($dadosInput['encaixe']) and!empty($dadosInput['encaixe'])) ? $dadosInput['encaixe'] : false;

        /////Verifica  horários
        if (!empty($dadosInput['horarioFim']) and ( strtotime($horarioFim) < strtotime($horario))) {
            return $this->returnError('', 'Horário de término deve ser maior que o de início');
        }

        $horaTermino = (!empty($horarioFim)) ? $horarioFim : $horario;
        $qrHorarios = $HorariosService->listHorarios($idDominio, $doutorId, $data, $horario, $data, $horaTermino, null, null, false, false, $dadosFiltroConsulta);

        if (!isset($qrHorarios[0]['horariosList'])) {
            return $this->returnError('', 'Horário indisponível');
        }

        //Verifica vinculo dos procimentos
        if (isset($dadosInput['procedimentos']) and count($dadosInput['procedimentos']) > 0) {
            $ProcedimentosRepository = new ProcedimentosRepository;

            foreach ($dadosInput['procedimentos'] as $chave => $rowProcV) {
                $qrVerificaVinculo = $ProcedimentosRepository->getByVinculoConvenioDoutor($idDominio, $rowProcV['id'], $rowProcV['convenioId'], $doutorId, 2);
                if ($qrVerificaVinculo->total == 0) {
                    return $this->returnError($rowProcV, 'O procedimento de indice [' . $chave . '] não possui vínculo com este(a) doutor(a)');
                }
            }
        }


        $verficaHorario = false;
        $erroHorafim = null;

        foreach ($qrHorarios[0]['horariosList'] as $rowHorario) {
            if (isset($dadosInput['tipoAtendimento']) and $dadosInput['tipoAtendimento'] == 'video') {


                if ($horario == $rowHorario['inicio'] and $rowHorario['disponivelVideo'] == true) {

                    $verficaHorario = true;
                    break;
                }
            } else {

                if ($horario == $rowHorario['inicio'] and $rowHorario['disponivel'] == true) {
                    $verficaHorario = true;
                    break;
                }
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



        $PacienteService = new PacienteService();

        if (!empty($pacienteId)) {
            $qrPaciente = $PacienteService->getById($idDominio, $pacienteId);

            if (!$qrPaciente['success']) {
                return $this->returnError('', 'Paciente não encontrado');
            }
            $rowPaciente = $qrPaciente['data'];
            if (!isset($dadosInput['paciente']['celular']) and!empty($dadosInput['paciente']['celular'])) {
                $PacienteService->atualizarPaciente($idDominio, $pacienteId, ['celular' => $dadosInput['paciente']['celular']]);
            }
        } else {
            $nomePac = explode(' ', $dadosPac['nome']);
            $dadosInsertPac['nome'] = $nomePac[0];
            unset($nomePac[0]);
            $dadosInsertPac['sobrenome'] = implode(' ', $nomePac);
            $dadosInsertPac['celular'] = (isset($dadosPac['celular']) and!empty($dadosPac['celular'])) ? $dadosPac['celular'] : null;
            $resultPaciente = $PacienteService->store($idDominio, $dadosInsertPac);
            if ($resultPaciente['success']) {
                $pacienteId = $resultPaciente['data']['id'];
            } else {
                return $resultPaciente;
            }

//            dd($pacienteId);
            $qrPaciente = $PacienteService->getById($idDominio, $pacienteId);
            $rowPaciente = $qrPaciente['data'];
        }


        $camposConsulta['identificador'] = $idDominio;
        $camposConsulta['data'] = Functions::dateDbToBr($data);
        $camposConsulta['data_agendamento'] = time();
        $camposConsulta['data_consulta'] = $data;
        $camposConsulta['hora_consulta'] = $horario;
        $camposConsulta['doutores_id'] = $doutorId;
        $camposConsulta['pacientes_id'] = $pacienteId;
        $camposConsulta['dados_consulta'] = $dados_consulta;

        if (!empty($rowConvenio)) {
            $camposConsulta['convenios_id'] = $rowConvenio[0]->convenios_id;
            $camposConsulta['convenio_numero_carteira'] = $rowConvenio[0]->numero_carteira;
            $camposConsulta['convenio_validade_carteira'] = $rowConvenio[0]->validade_carteira;
        }

        if (!empty($horarioFim)) {
            $camposConsulta['hora_consulta_fim'] = $horarioFim;
        }


        if (isset($dadosInput['encaixe']) and $dadosInput['encaixe'] == true) {
            $camposConsulta['encaixe'] = 1;
        }
//        $CamposInsert['administrador_id'] = $_SESSION['id_LOGADO'];
//        $CamposInsert['retorno'] = $retorno;
//        $CamposInsert['convenios_id'] = $rowConvenio->convenios_id;
//        $CamposInsert['convenio_numero_carteira'] = $rowConvenio->numero_carteira;
//        $CamposInsert['convenio_validade_carteira'] = $rowConvenio->validade_carteira;
//        $CamposInsert['tipo_desconto'] = $tipo_desconto;
//        $CamposInsert['acrescimo_tipo'] = $tipoAcrescimo;
//        $CamposInsert['confirmacao'] = $confirmacao;




        $idConsulta = $this->consultaRepository->insertConsulta($idDominio, $camposConsulta);

        $LogAtividadesService = new LogAtividadesService();
        $LogAtividadesService->store($idDominio, 2, "Agendou uma consulta do(a) paciente " . utf8_encode($rowPaciente['nome']) . " " . utf8_encode($rowPaciente['sobrenome']) . " com o(a) Dr(a).: " . utf8_encode($rowDoutor->nome), $idConsulta, 4);

        unset($camposConsulta);

//        dd($rowDoutor);
        if ($idConsulta) {

            if ($rowDominio->alteracao_docbizz == 1) {
                $NotificaoSistema = new NotificacoesSistemaService;
                $dadosInsertNot['identificador'] = $idDominio;
                $NotificaoSistema->store($idDominio, 1, $idConsulta, 1, $dadosInsertNot);
            }


            $linkPagSeguro = null;
            $linkVideo = null;
            $precoConsulta = null;
            $dadosProcedimentos = [];

            //Videoconsulta
            if (isset($dadosInput['tipoAtendimento']) and $dadosInput['tipoAtendimento'] == 'video') {
                $precoConsulta = str_replace(',', '.', str_replace('.', '', $rowDoutor->precoConsulta));

                $codVideo = md5($idConsulta);
                $dadosUpdate['codigo_id_videoconf'] = $codVideo;
                $dadosUpdate['videoconferencia'] = 1;
                $dadosUpdate['valor_consulta'] = $precoConsulta;
                $dadosUpdate['room'] = $codVideo;
                $dadosUpdate['room_pass'] = hash('sha256', md5($idConsulta));
                $this->consultaRepository->updateConsulta($idDominio, $idConsulta, $dadosUpdate);

                $linkVideo = $this->getLinkVideo($codVideo, $rowDominio->dominio);

                unset($dadosUpdate);

                ///Verifica proceidmento padrão para videoconsulta
                if ($rowDoutor->proc_doutor_id_video != null and $dadosInput['tipoAtendimento'] == 'video') {
                    $ProcedimentosRepository = new ProcedimentosRepository;
                    $rowProc = $ProcedimentosRepository->getAllProcedimentosVinculados($rowDoutor->identificador, null, null, $rowDoutor->proc_doutor_id_video);
                    $rowProc = $rowProc[0];
                    $precoConsulta = $rowProc->valor_proc;
                    $dadosProcedimentos[] = array(
                        'id' => $rowProc->procedimentos_id,
                        'convenioId' => $rowProc->proc_convenios_id,
                        'qnt' => 1,
                        'valor' => $rowProc->valor_proc,
                    );
                }
            } else {

                if (isset($dadosInput['procedimentos'])) {
                    $dadosProcedimentos = $dadosInput['procedimentos'];
                } else {
                    ///Verifica proceidmento padrão para consulta presencial
                    if ($rowDoutor->procPadraoIdProcedimento != null) {
                        $precoConsulta = $rowDoutor->procPadraoValor;
                        $dadosProcedimentos[] = array(
                            'id' => $rowDoutor->procPadraoIdProcedimento,
                            'convenioId' => $rowDoutor->procPadraoIdConvenio,
                            'qnt' => 1,
                            'valor' => $rowDoutor->procPadraoValor,
                        );
                    }
                }
            }

            if (count($dadosProcedimentos) > 0) {
                $resultProc = $ConsultaProcedimentoService->calculoProcedimentosConsultas($idDominio, $idConsulta, $doutorId, $dadosProcedimentos, $rowDominio, $rowDoutor);
                $precoConsulta = $resultProc['valorTotalProc'];
            }

            //Assaas
            if (isset($dadosInput['linkAsaas']) and $dadosInput['linkAsaas'] == true and $precoConsulta > 0
            ) {
                $descricaoAsaasCobranca = 'Agendamento com o dr(a) ' . $rowDoutor->nome . ' no dia ' . Functions::dateDbToBr($data) . ' as ' . $horario . 'h :';
                $CobrancaAsaasService = new CobrancaAsaasService;
                $CobrancaAsaasService->setAmbienteAsaas($rowDominio->ambiente_assas);
                $CobrancaAsaasService->setFormaPagamento($dadosInput['formaPagAsaas']);
                $CobrancaAsaasService->setIdCustomer($rowPaciente['idCustomerAsaas']);
                $CobrancaAsaasService->setPacienteCelular($rowPaciente['celular']);
                $CobrancaAsaasService->setPacienteCpf($dadosInput['paciente']['cpf']);
                $CobrancaAsaasService->setPacienteId($rowPaciente['id']);
                $CobrancaAsaasService->setPacienteEmail($dadosInput['paciente']['email']);
                $CobrancaAsaasService->setPacienteNome($rowPaciente['nome'] . ' ' . $rowPaciente['sobrenome']);
                $CobrancaAsaasService->setValor($precoConsulta);
                $CobrancaAsaasService->setDescricao($descricaoAsaasCobranca);
                $rowCons = $this->consultaRepository->getById($idDominio, $idConsulta);

                $cobrancaAsaas = $CobrancaAsaasService->createCobrancaConsulta($idDominio, $idConsulta, $rowConfigAsaas, $rowCons);

                if ($cobrancaAsaas['success']) {
                    $linkPagamento = $cobrancaAsaas['data']['linkPagamento'];
                } else {
                    return $cobrancaAsaas;
                }
            }


            if (isset($dadosInput['linkPagSeguro']) and $dadosInput['linkPagSeguro'] == true and $rowPAgSeguroCOnfig->habilitado == 1 AND $precoConsulta != null) {

                $CobrancaPagSeguroService = new CobrancaPagSeguroService();
                $dadosPagSeguro['ambiente'] = $rowDominio->pagseguro_ambiente;
                $dadosPagSeguro['descricao'] = 'Consulta';
                $dadosPagSeguro['valor'] = $precoConsulta;
                $dadosPagSeguro['idConsulta'] = $idConsulta;
                $dadosPagSeguro['email'] = $rowPaciente['email'];
                $linkPagamento = $CobrancaPagSeguroService->create($idDominio, $dadosPagSeguro);
                $linkPagamento = ($linkPagamento) ? $linkPagamento['link'] : null;
            }

            $StatusRefreshRepository = new StatusRefreshRepository;
            $StatusRefreshRepository->insertAgenda($idDominio, $doutorId);

            if (!empty($rowPaciente['email']) and $rowPaciente['enviaEmail'] == 1) {
                $EmailAgendamentoService = new EmailAgendamentoService($idDominio);
                $EmailAgendamentoService->setNomePaciente($rowPaciente['nome'] . ' ' . $rowPaciente['sobrenome']);
                $EmailAgendamentoService->setDoutorId($doutorId);
                $EmailAgendamentoService->setNomeDoutor($rowDoutor->nome);
                $EmailAgendamentoService->setLinkPagseguro($linkPagamento);
                $EmailAgendamentoService->setLinkVideo($linkVideo);
                $EmailAgendamentoService->setDataConsulta($data);
                $EmailAgendamentoService->setHoraConsulta($horario);
                $EmailAgendamentoService->setExibelinkConfirmar(true);
                $EmailAgendamentoService->setPrecoConsulta($precoConsulta);
                $EmailAgendamentoService->setEmailPaciente($rowPaciente['email']);
                $enviado = $EmailAgendamentoService->sendEmailAgendamento($idDominio, $idConsulta, 'confirmacaoConsulta');
            }


//            $ConsultaReservadaService->store($idDominio, [
//                'consultas_id' => '',
//                'identificador' => $idDominio,
//                'data_expiracao' => date('Y-m-d', strtotime(date('Y-m-d') . " +2 days")),
//            ]);


            $rowConsulta = $this->getById($idDominio, $idConsulta, ['showProcedimentos' => true]);

            return $this->returnSuccess($rowConsulta['data']);
        } else {
            return $this->returnError(NULL, 'Ocorreu o erro ao agendar a consulta');
        }
    }

    public function update($idDominio, $idConsulta, $dadosInput) {

        $rowConsulta = $this->consultaRepository->getById($idDominio, $idConsulta);

        if (!$rowConsulta) {
            return $this->returnError(NULL, 'Consulta não encontrada');
        }


        $pacienteId = $dadosInput['pacienteId'];
        $doutorId = $rowConsulta->doutores_id;
        $data = $dadosInput['data'];
        $horario = $dadosInput['horario'];
        $horarioFim = (isset($dadosInput['horarioFim']) and!empty($dadosInput['horarioFim'])) ? substr($dadosInput['horarioFim'], 0, 5) : null;
        $celularPaciente = (isset($dadosInput['celularPaciente']) and!empty($dadosInput['celularPaciente'])) ? $dadosInput['celularPaciente'] : null;
        $dados_consulta = (isset($dadosInput['observacoes']) and!empty($dadosInput['observacoes'])) ? Functions::accentsToUtf8Convert($dadosInput['observacoes']) : null;

        $DominioService = new DominioService;
        $rowDominio = $DominioService->getById($idDominio);
        $rowDominio = $rowDominio['data'];

        $ConsultaReservadaService = new ConsultaReservadaService;
        $HorariosService = new HorariosService;

        $DoutoresRepository = new DoutoresRepository();
        $qrDoutor = $DoutoresRepository->getById($idDominio, $doutorId);

        if (!$qrDoutor['success']) {
            return $this->returnError('', 'Doutor(a) não encontrado');
        }

        $rowDoutor = $qrDoutor;
        if (isset($dadosInput['tipoAtendimento']) and $dadosInput['tipoAtendimento'] == 'video' and!$rowDoutor->possui_videoconf) {
            return $this->returnError('', 'Este profissional não aceita consultas por vídeo');
        }

        $PacienteService = new PacienteService();
        $qrPaciente = $PacienteService->getById($idDominio, $pacienteId);

        if (!$qrPaciente['success']) {
            return $this->returnError('', 'Paciente não encontrado');
        }
//        dd($qrPaciente);
        $rowPaciente = $qrPaciente['data'];

        /////Verifica  horários
        if (!empty($dadosInput['horarioFim']) and ( strtotime($horarioFim) < strtotime($horario))) {
            return $this->returnError('', 'Horário de término deve ser maior que o de início');
        }

        $horaTermino = (!empty($horarioFim)) ? $horarioFim : $horario;
        $qrHorarios = $HorariosService->listHorarios($idDominio, $doutorId, $data, $horario, $data, $horaTermino, null, null, false, true, ['exibeConsultaTermino' => true]);

        if (!isset($qrHorarios[0]['horariosList'])) {
            return $this->returnError('', 'Horário indisponível');
        }



        $verficaHorario = false;
        $erroHorafim = null;
        foreach ($qrHorarios[0]['horariosList'] as $rowHorario) {


            if ($horario == $rowHorario['inicio']) {

                if ($rowHorario['disponivel'] == true) {
                    $verficaHorario = true;
                    break;
                } else if (isset($rowHorario['consultas'])) {

                    $idsConsultas = array_map(function ($item) {
                        return $item['id'];
                    }, $rowHorario['consultas']);

                    if (in_array($rowConsulta->id, $idsConsultas)) {
                        $verficaHorario = true;
                        break;
                    }
                }
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


//        dd($qrHorarios);
//        $camposConsulta['identificador'] = $idDominio;
        $camposConsulta['data'] = Functions::dateDbToBr($data);
        $camposConsulta['data_agendamento'] = time();
        $camposConsulta['data_consulta'] = $data;
        $camposConsulta['hora_consulta'] = $horario;
//        $camposConsulta['doutores_id'] = $doutorId;
        $camposConsulta['pacientes_id'] = $pacienteId;
        $camposConsulta['dados_consulta'] = $dados_consulta;

        if (!empty($horarioFim)) {
            $camposConsulta['hora_consulta_fim'] = $horarioFim;
        } else {
            $camposConsulta['hora_consulta_fim'] = null;
        }

        if (!empty($celularPaciente)) {
            $dadosPac['celular'] = $celularPaciente;
            $PacienteService->atualizarPaciente($idDominio, $pacienteId, $dadosPac);
        }


//        $CamposInsert['administrador_id'] = $_SESSION['id_LOGADO'];
//        $CamposInsert['retorno'] = $retorno;
//        $CamposInsert['convenios_id'] = $rowConvenio->convenios_id;
//        $CamposInsert['convenio_numero_carteira'] = $rowConvenio->numero_carteira;
//        $CamposInsert['convenio_validade_carteira'] = $rowConvenio->validade_carteira;
//        $CamposInsert['tipo_desconto'] = $tipo_desconto;
//        $CamposInsert['acrescimo_tipo'] = $tipoAcrescimo;
//        $CamposInsert['confirmacao'] = $confirmacao;




        $this->consultaRepository->updateConsulta($idDominio, $rowConsulta->id, $camposConsulta);
        $idConsulta = $rowConsulta->id;

        $LogAtividadesService = new LogAtividadesService();
        $LogAtividadesService->store($idDominio, 3, " Editou a consulta do dia " . Functions::dateDbToBr($data) . utf8_encode(" às ") . " $horario.
Paciente : " . utf8_encode($rowPaciente['nome']) . " " . utf8_encode($rowPaciente['sobrenome']) . " com o(a) doutor(a) " . utf8_encode($rowDoutor->nome), $idConsulta, 4);

        unset($camposConsulta);

//        dd($rowDoutor);
        if ($idConsulta) {

            $linkPagamento = null;
            $linkVideo = null;
            $precoConsulta = null;

            //Videoconsulta
            if (isset($dadosInput['tipoAtendimento']) and $dadosInput['tipoAtendimento'] == 'video') {
                $precoConsulta = $rowDoutor->precoConsulta;

                $codVideo = md5($idConsulta);
                $dadosUpdate['codigo_id_videoconf'] = $codVideo;
                $dadosUpdate['videoconferencia'] = 1;
                $dadosUpdate['valor_consulta'] = $precoConsulta;
                $dadosUpdate['room'] = $codVideo;
                $dadosUpdate['room_pass'] = hash('sha256', md5($idConsulta));
                $this->consultaRepository->updateConsulta($idDominio, $idConsulta, $dadosUpdate);

                $linkVideo = $this->getLinkVideo($codVideo, $rowDominio->dominio);

                unset($dadosUpdate);

                if (isset($dadosInput['linkPagSeguro']) and $dadosInput['linkPagSeguro'] == true) {
                    $CobrancaPagSeguroService = new CobrancaPagSeguroService();
                    $dadosPagSeguro['ambiente'] = $rowDominio->pagseguro_ambiente;
                    $dadosPagSeguro['descricao'] = 'Consulta';
                    $dadosPagSeguro['valor'] = $precoConsulta;
                    $dadosPagSeguro['idConsulta'] = $idConsulta;
                    $dadosPagSeguro['email'] = $rowPaciente['email'];
                    $linkPagSeguro = $CobrancaPagSeguroService->create($idDominio, $dadosPagSeguro);
                    $linkPagamento = ($linkPagSeguro) ? $linkPagSeguro['link'] : null;
                }
            } else {
//                $precoConsulta = '152';
            }





            $StatusRefreshRepository = new StatusRefreshRepository;
            $StatusRefreshRepository->insertAgenda($idDominio, $doutorId);

            if (!empty($rowPaciente->email) and $rowPaciente->envia_email == 1) {
                $EmailAgendamentoService = new EmailAgendamentoService($idDominio);
                $EmailAgendamentoService->setDoutorId($doutorId);
                $EmailAgendamentoService->setNomeDoutor($rowDoutor->nome);
                $EmailAgendamentoService->setLinkPagseguro($linkPagamento);
                $EmailAgendamentoService->setLinkVideo($linkVideo);
                $EmailAgendamentoService->setDataConsulta($data);
                $EmailAgendamentoService->setHoraConsulta($horario);
                $EmailAgendamentoService->setExibelinkConfirmar(true);
                $EmailAgendamentoService->setPrecoConsulta($precoConsulta);
                $EmailAgendamentoService->setEmailPaciente($rowPaciente->email);
                $enviado = $EmailAgendamentoService->sendEmailAgendamento($idDominio, $idConsulta, 'confirmacaoConsulta');
            }


//            $ConsultaReservadaService->store($idDominio, [
//                'consultas_id' => '',
//                'identificador' => $idDominio,
//                'data_expiracao' => date('Y-m-d', strtotime(date('Y-m-d') . " +2 days")),
//            ]);

            return $this->returnSuccess([
                        'consultaId' => $idConsulta
            ]);
        } else {
            return $this->returnError(NULL, 'Ocorreu o erro ao agendar a consulta');
        }
    }

    public function delete($idDominio, $idConsulta) {

        $rowConsulta = $this->consultaRepository->getById($idDominio, $idConsulta);
        if (!$rowConsulta) {
            return $this->returnError(NULL, 'Consulta não encontrada');
        }

        $PacienteService = new PacienteService();
        $qrPaciente = $PacienteService->getById($idDominio, $rowConsulta->pacientes_id);

        if (!$qrPaciente['success']) {
            return $this->returnError(NULL, 'Paciente não encontrado');
        }
        $rowPaciente = $qrPaciente['data'];

        $DoutoresService = new DoutoresService();
        $qrDoutor = $DoutoresService->getById($idDominio, $rowConsulta->doutores_id);

        if (!$qrDoutor['success']) {
            return $this->returnError('', 'Doutor(a) não encontrado');
        }
        $rowDoutor = $qrDoutor['data'];

        $rowDefinicoesConsultas = $this->definicaoMarcacaoConsultaRepository->getByDoutoresId($idDominio, $rowConsulta->doutores_id);
        $limiteConsultas = $rowDefinicoesConsultas->limite_consultas;
        $limite_encaixe_consulta = $rowDefinicoesConsultas->limite_encaixe_consulta;

        $HorariosService = new HorariosService;

        if (empty($rowConsulta->encaixe)) {

            $qrHorarios = $HorariosService->listHorarios($idDominio, $rowConsulta->doutores_id, $rowConsulta->data_consulta, $rowConsulta->hora_consulta, $rowConsulta->data_consulta, $rowConsulta->hora_consulta, null, null, false, true, ['exibeConsultaTermino' => true]);

            $ArrayConsultaAgendadas = [];
            if (isset($qrHorarios[0]['horariosList'])) {

                foreach ($qrHorarios[0]['horariosList'] as $rowHrList) {
                    if ($rowHrList['inicio'] == substr($rowConsulta->hora_consulta, 0, 5)) {
                        foreach ($rowHrList['consultas'] as $rowC) {

                            if ($rowC['id'] != $idConsulta and $rowC['encaixe']) {

                                $ArrayConsultaAgendadas[$rowC['id']] = $rowC['encaixe'];

                                $this->consultaRepository->updateConsulta($idDominio, $rowC['id'], ['encaixe' => null]);
                                break;
                            }
                        }
                    }
                }
            }
        }

//        dd($ArrayConsultaAgendadas);
//
//        dd($rowDoutor);

        if ($rowConsulta->statusConsulta != null) {

            $statusConsulta = explode('_', $rowConsulta->statusConsulta);

            switch ($statusConsulta[0]) {
                case 'estaSendoAtendido': return $this->returnError(NULL, 'Nâo foi possível excluir a consulta. O paciente está sendo atendido');
                    break;
                case 'jaFoiAtendido': return $this->returnError(NULL, 'Nâo foi possível excluir a consulta. O paciente já foi atendido');
                    break;
            }
        }

        $NotificaoSistema = new NotificacoesSistemaService;
        $resultNot = $NotificaoSistema->deleteByConsultaId($idDominio, $idConsulta);

        $LogAtividadesService = new LogAtividadesService();
        $LogAtividadesService->store($idDominio, 4, " Excluiu a consulta do dia " . Functions::dateDbToBr($rowConsulta->data_consulta) . utf8_encode(" às ") . " $rowConsulta->hora_consulta.
Paciente : " . utf8_encode($rowPaciente['nome']) . " " . utf8_encode($rowPaciente['sobrenome']) . " com o(a) doutor(a) " . utf8_encode($rowDoutor['nome']), $rowConsulta->id, 4);

        $qr = $this->consultaRepository->delete($idDominio, $idConsulta);
        $StatusRefreshRepository = new StatusRefreshRepository;
        $StatusRefreshRepository->insertAgenda($idDominio, $rowConsulta->doutores_id);
        return $this->returnSuccess(NULL, 'Excluido com sucesso');
    }

    public function confirmar($idDominio, $consultaId, $meioConfirmacao = null) {

        $rowConsultas = $this->consultaRepository->getById($idDominio, $consultaId);

        $statusConsulta = explode('_', $rowConsultas->statusConsulta);

        $arrayStatus = [''];
        if ($rowConsultas) {

            switch ($statusConsulta[0]) {
                case 'jaSeEncontra':
                    return $this->returnError(null, 'O paciente já se encontra na clínica');
                    break;
                case 'jaFoiAtendido':
                    return $this->returnError(null, 'O paciente já foi atendido');
                    break;
                case 'estaSendoAtendido':
                    return $this->returnError(null, 'O pacientesetá sendo atendido');
                    break;
                case 'desmarcado':
                    return $this->returnError(null, 'Esta consulta está desmarcada');
                    break;
                case 'faltou':
                    return $this->returnError(null, 'O paciente faltou');
                    break;
                default:
                    $dadosUpdate['confirmacao'] = (empty($meioConfirmacao)) ? 'sistema' : $meioConfirmacao;
                    $this->consultaRepository->updateConsulta($idDominio, $consultaId, $dadosUpdate);
                    return $this->returnSuccess(null, 'Consulta confirmada com sucesso');
                    break;
            }
        } else {
            return $this->returnError(null, 'Consultas não encontrada');
        }
    }

    public function desmarcar($idDominio, $consultaId, $desmarcadoPor = 1, $motivo = null) {

        $rowConsultas = $this->consultaRepository->getById($idDominio, $consultaId);
        $statusConsulta = explode('_', $rowConsultas->statusConsulta);

        if (!empty($rowConsultas->idRecebimento)) {
            return $this->returnError(null, 'Não foi possível desmacar. A consulta já foi paga.');
        }
        if ($rowConsultas) {

            switch ($statusConsulta[0]) {
                case 'jaFoiAtendido':
                    return $this->returnError(null, 'O paciente já foi atendido');
                    break;
                case 'estaSendoAtendido':
                    return $this->returnError(null, 'O paciente está sendo atendido');
                    break;
                case 'desmarcado':
                    return $this->returnError(null, 'Esta consulta está desmarcada');
                    break;
                case 'faltou':
                    return $this->returnError(null, 'O paciente faltou');
                    break;
                default:

                    $ConsultaStatusRepository = new ConsultaStatusRepository();
                    $dados['consulta_id'] = $consultaId;
                    $dados['identificador'] = $idDominio;
                    $dados['status'] = 'desmarcado';
                    $dados['hora'] = time();
                    $dados['razao_desmarcacao'] = $motivo;
                    $dados['desmarcado_por'] = (empty($desmarcadoPor)) ? 1 : '';
                    $ConsultaStatusRepository->alteraStatus($idDominio, $consultaId, $dados);

                    return $this->returnError(null, 'Consulta desmarcada com sucesso');
                    break;
            }
        } else {
            return $this->returnError(null, 'Consultas não encontrada');
        }
    }

    public function alterarStatus($idDominio, $consultaId, $status, $dadosInput = null) {


        $arrayStatusNome = array(
            'jaSeEncontra' => 'Paciente já se encontra',
            'estaSendoAtendido' => 'Paciente sendo atendido',
            'jaFoiAtendido' => 'Paciente já foi atendido',
        );
        $arrayStatusValid = ['confirmado', 'agendado', 'jaSeEncontra', 'estaSendoAtendido', 'jaFoiAtendido', 'desmarcado', 'faltou'];
        $rowConsultas = $this->consultaRepository->getById($idDominio, $consultaId);
        $statusConsulta = explode('_', $rowConsultas->statusConsulta);

        if (!in_array($status, $arrayStatusValid)) {
            return $this->returnError(null, 'Status inválido');
        }
        if (!$rowConsultas) {
            return $this->returnError(null, 'Consulta não encontrada.');
        }

        $StatusRefreshRepository = new StatusRefreshRepository;
        $AgendaFilaEsperaRepository = new AgendaFilaEsperaRepository;
        $DefinicaoMarcacaoGlobalRepository = new DefinicaoMarcacaoGlobalRepository;
        $rowDefGlobal = $DefinicaoMarcacaoGlobalRepository->getDadosDefinicao($idDominio);

        $Recebimentos = new RecebimentoRepository;
        $qrRecebimentoLancado = $Recebimentos->getAllEfetuados($idDominio, 'consulta', $consultaId);

        if (($status == 'jaSeEncontra'
                or $status == 'estaSendoAtendido'
                or $status == 'jaFoiAtendido'
                )
                and count($qrRecebimentoLancado) == 0 and $rowDefGlobal->somente_pago_fila == 1) {
            return $this->returnError('Para alterar o status do paciente para "' . $arrayStatusNome[$status] . '", deve ser feito pagamento do agendamento ');
        }

        if ($status == 'confirmado') {

            $this->consultaRepository->updateConsulta($idDominio, $consultaId, [
                'confirmacao' => (isset($dadosInput['origemConfirmacao']) and!empty($dadosInput['origemConfirmacao'])) ? $dadosInput['origemConfirmacao'] : 'sistema',
            ]);
            $StatusRefreshRepository->insertAgenda($idDominio, $rowConsultas->doutores_id);
            return $this->returnSuccess(null, 'Confirmado com sucesso');
        } else
        if ($status == 'agendado') {

            if ($statusConsulta[0] == 'jaFoiAtendido') {
                return $this->returnSuccess(null, 'Este agendamento já foi finalizado');
            }


            $ConsultaStatusRepository = new ConsultaStatusRepository;
            $ConsultaStatusRepository->limpaStatus($idDominio, $consultaId);

            $AgendaFilaEsperaRepository->excluirPorConsultaId($idDominio, $consultaId);
            $StatusRefreshRepository->insertAgenda($idDominio, $rowConsultas->doutores_id);

            $this->consultaRepository->updateConsulta($idDominio, $consultaId, ['liberado_fila_espera' => 0]);
            return $this->returnSuccess(null, 'Status alterado com sucesso!');
        } else {
            $agora = time();
            $dadosInsertStatus = [
                'identificador' => $idDominio,
                'consulta_id' => $consultaId,
                'status' => $status,
                'hora' => $agora,
                'administrador_id' => (isset(auth('clinicas')->user()->id)) ? auth('clinicas')->user()->id : null,
                'nome_administrador' => (isset(auth('clinicas')->user()->nome)) ? auth('clinicas')->user()->nome : null,
            ];
            switch ($status) {
                case 'jaSeEncontra':
                    $dataHoraConsulta = new DateTime($rowConsultas->data_consulta . ' ' . $rowConsultas->hora_consulta);
                    $dataHoraAtual = new DateTime();
                    $dif = $dataHoraAtual->diff($dataHoraConsulta);
                    $dadosInsertStatus['hora_atraso'] = ($dif->days * 24) + $dif->h . ':' . $dif->i . ':00';
                    break;

                case 'faltou':
                    $dadosInsertStatus['obs_falta'] = (isset($dadosInput['motivo']) and!empty($dadosInput['motivo'])) ? $dadosInput['motivo'] : '';

                    break;
                case 'desmarcado':
                    $dadosInsertStatus['desmarcado_por'] = (isset($dadosInput['desmarcadoPor']) and!empty($dadosInput['desmarcadoPor'])) ? $dadosInput['desmarcadoPor'] : 1;
                    $dadosInsertStatus['razao_desmarcacao'] = (isset($dadosInput['motivo']) and!empty($dadosInput['motivo'])) ? $dadosInput['motivo'] : '';

                    break;
            }


            if ((isset($statusConsulta[0]) and $statusConsulta[0] != $status) or empty($statusConsulta[0])) {

                $ConsultaStatusRepository = new ConsultaStatusRepository;
                $ConsultaStatusRepository->insereStatus($idDominio, $consultaId, $dadosInsertStatus);

                if ($status == 'desmarcado' or $status == 'faltou') {
                    $AgendaFilaEsperaRepository->excluirPorConsultaId($idDominio, $consultaId);
                    $this->consultaRepository->updateConsulta($idDominio, $consultaId, ['liberado_fila_espera' => 0]);
                }
                $StatusRefreshRepository->insertAgenda($idDominio, $rowConsultas->doutores_id);
                return $this->returnSuccess(null, 'Status alterado com sucesso!');
            } elseif ($statusConsulta[0] == $status) {

                if ($status == 'desmarcado' or $status == 'faltou') {
                    $ConsultaStatusRepository = new ConsultaStatusRepository;
                    $ConsultaStatusRepository->updateStatus($idDominio, $consultaId, $statusConsulta[2], $dadosInsertStatus);

                    $AgendaFilaEsperaRepository->excluirPorConsultaId($idDominio, $consultaId);
                    $this->consultaRepository->updateConsulta($idDominio, $consultaId, ['liberado_fila_espera' => 0]);

                    $StatusRefreshRepository->insertAgenda($idDominio, $rowConsultas->doutores_id);
                    return $this->returnSuccess(null, 'Status alterado com sucesso!');
                } else {
                    return $this->returnSuccess(null, 'O agendamento já está com este status');
                }
            }
        }
    }

}
