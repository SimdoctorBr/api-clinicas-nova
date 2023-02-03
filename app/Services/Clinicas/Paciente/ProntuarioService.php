<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\Clinicas\Paciente;

use App\Services\BaseService;
use DateTime;
use App\Helpers\Functions;
use App\Repositories\Gerenciamento\DominioRepository;
use App\Repositories\Clinicas\ConsultaRepository;
use App\Repositories\Clinicas\Consulta\ConsultaProntuarioRepository;
use App\Services\Clinicas\Consulta\ConsultaProcedimentoService;
use App\Repositories\Clinicas\Consulta\ConsultaProcedimentoRepository;
use App\Repositories\Clinicas\Paciente\PacienteFotosRepository;
use App\Repositories\Clinicas\Paciente\PacienteArquivoRepository;
use App\Repositories\Clinicas\Paciente\PacienteAtestadoRepository;
use App\Repositories\Clinicas\Paciente\PacientePrescricaoRepository;
use App\Repositories\Clinicas\Consulta\ConsultaPedidoExameRepository;
use App\Services\Clinicas\PacienteService;

//use App\Repositories\Clinicas\Paciente\PacienteExameRepository;
//use App\Repositories\Clinicas\Paciente\PacienteLaudoRepository;
//use App\Repositories\Clinicas\Paciente\PacienteResultadoExameRepository;

/**
 * Description of Activities
 *
 * @author ander
 */
class ProntuarioService extends BaseService {

    public function getHistoricoUnificado($idDominio, $pacienteId, $request) {

        $PacienteFotosRepository = new PacienteFotosRepository;
        $PacienteArquivoRepository = new PacienteArquivoRepository;
        $PacientePrescricaoRepository = new PacientePrescricaoRepository;
        $ConsultaProcedimentoService = new ConsultaProcedimentoService;
        $PacienteAtestadoRepository = new PacienteAtestadoRepository;
        $ConsultaProcedimentoRepository = new ConsultaProcedimentoRepository;
        $ConsultaPedidoExameRepository = new ConsultaPedidoExameRepository;

        $DominioRepository = new DominioRepository;
        $rowDominio = $DominioRepository->getById($idDominio);

        $PacienteService = new PacienteService;
        $qrPaciente = $PacienteService->getById($idDominio, $pacienteId);

        if (!$qrPaciente['success']) {
            return $this->returnError('', 'Paciente não encontrado');
        }

        $dadosFiltro = null;
        $dadosFiltroFotos = null;
        if ($request->has('doutorId') and!empty($request->query('doutorId'))) {
            $dadosFiltro['doutorId'] = $request->query('doutorId');
            $dadosFiltroFotos['doutorId'] = $request->query('doutorId');
        }
        if ($request->has('data') and!empty($request->query('data'))) {
            $dadosFiltro['data'] = $request->query('data');
            $dadosFiltroFotos['dataInicio'] = $request->query('data');
        }
        if ($request->has('dataFim') and!empty($request->query('dataFim'))) {
            $dadosFiltro['dataFim'] = $request->query('dataFim');
            $dadosFiltroFotos['dataFim'] = $request->query('dataFim');
        }

        $filtroRetorno = null;
        if ($request->has('filtro') and!empty(trim($request->query('filtro')))) {
            $filtroRetorno = explode(',', $request->query('filtro'));
        }

        $dadosFiltro['pacienteId'] = $pacienteId;
        $DADOS_RETORNO = null;

        $dadosPaginacao = $this->getPaginate($request);
        $ConsultaProntuarioRepository = new ConsultaProntuarioRepository;

//        
        ///PRONTUARIOS
        if ($filtroRetorno == null or ( count($filtroRetorno) > 0 and in_array('prontuarios', $filtroRetorno))) {







            $ConsultaRepository = new ConsultaRepository();
            $dadosFiltroCons = $dadosFiltro;
            $dadosFiltroCons['orderBy'] = 'data_consulta DESC';
            $dadosFiltroCons['doutoresId'] = ($request->has('doutorId') and!empty($request->query('doutorId'))) ? $request->query('doutorId') : null;
            $qrConsultas = $ConsultaRepository->getAll($idDominio, $dadosFiltroCons);
//        dd($dadosFiltroCons);
            foreach ($qrConsultas as $rowConsulta) {
                $qrProntuario = $ConsultaProntuarioRepository->getByConsultaId($idDominio, $rowConsulta->id);

                $dadosPront = null;
                if (count($qrProntuario) > 0) {
                    $rowPront = $qrProntuario[0];
                    $dadosPront = $rowPront;
                } else {
                    $statusConsulta = explode('_', $rowConsulta->statusConsulta);
                    if ((isset($statusConsulta[0]) and $statusConsulta[0] != 'jaFoiAtendido') or empty($rowConsulta->statusConsulta)) {
                        continue;
                    }
                }
//                dd($rowPront);

                if (isset($dadosPront->id)) {
                    $observacoes = $this->getObservacoes($idDominio, $dadosPront->id);
                }
                $DADOS_RETORNO[$rowConsulta->data_consulta]['prontuarios'][] = [
                    'id' => (isset($dadosPront->id)) ? $dadosPront->id : '',
                    'consultaId' => $rowConsulta->id,
                    'dataConsulta' => $rowConsulta->data_consulta,
                    'horaConsulta' => $rowConsulta->hora_consulta,
                    'doutorId' => $rowConsulta->doutores_id,
                    'nomeDoutor' => $rowConsulta->nomeDoutor,
//                'nomePaciente' => $rowPront->nome,
//                'sobrenomePaciente' => $rowPront->sobrenome,
                    'pacienteId' => $rowConsulta->pacientes_id,
                    'secaoId' => (isset($dadosPront->tipo_anotacao_id)) ? $dadosPront->tipo_anotacao_id : '',
                    'nomeSecao' => (isset($dadosPront->nomeSecao)) ? $dadosPront->nomeSecao : '',
//                'modeloProntuarioId' => $rowPront->modelo_prontuario_id,
                    'jsonProntuario' => (isset($dadosPront->json_prontuario_cript)) ? (html_entity_decode(strip_tags((($dadosPront->json_prontuario_cript))))) : '',
                    'dataCadPront' => (isset($dadosPront->data_cad)) ? $dadosPront->data_cad : '',
                    'userCad' => (isset($dadosPront->user_cad)) ? $dadosPront->user_cad : '',
                    'nomeUserCad' => (isset($dadosPront->nomeUserCad)) ? Functions::utf8Fix((($dadosPront->nomeUserCad))) : '',
                    'prontuarioAdicional' => $rowConsulta->prontuario_adicional,
                    'observacoes' => (isset($observacoes['data']) and count($observacoes['data']) > 0) ? $observacoes['data'] : null
                ];
            }
            
            
               if ($rowDominio->alteracao_docbizz == 1) {
                foreach ($DADOS_RETORNO as $dataCons => $rowC) {
                    if (count($rowC['prontuarios']) > 1) {
                        $pronts = array_values(array_filter(array_map(function ($item) {
                                            if (!empty($item['jsonProntuario'])) {
                                                return $item;
                                            }
                                        }, $rowC['prontuarios'])));

                        if (count($pronts) == 0) {
                            $DADOS_RETORNO[$dataCons]['prontuarios'] = array($rowC['prontuarios'][0]);
                        } else {
                            $DADOS_RETORNO[$dataCons]['prontuarios'] = $pronts;
                        }
                    }
                }
            }

//
//            
//              $ConsultaRepository = new ConsultaRepository();
//            $dadosFiltroCons = $dadosFiltro;
//            $dadosFiltroCons['orderBy'] = 'data_consulta DESC';
//            $dadosFiltroCons['doutoresId'] = ($request->has('doutorId') and!empty($request->query('doutorId')))?$request->query('doutorId'):null;
//            $qrConsultas = $ConsultaRepository->getAll($idDominio, $dadosFiltroCons);
////        dd($dadosFiltroCons);
//            foreach ($qrConsultas as $rowConsulta) {
//                $qrProntuario = $ConsultaProntuarioRepository->getByConsultaId($idDominio, $rowConsulta->id);
//
//                $dadosPront = null;
//                if (count($qrProntuario) > 0) {
//                    $rowPront = $qrProntuario[0];
//                    $dadosPront = $rowPront;
//                }
////                dd($rowPront);
//
//                $observacoes = $this->getObservacoes($idDominio, $rowPront->id);
//                $DADOS_RETORNO[$rowConsulta->data_consulta]['prontuarios'][] = [
//                    'id' => (isset($dadosPront->id)) ? $dadosPront->id : '',
//                    'consultaId' => $rowPront->consulta_id,
//                    'dataConsulta' => $rowConsulta->data_consulta,
//                    'horaConsulta' => $rowConsulta->hora_consulta,
//                    'doutorId' => $rowConsulta->doutores_id,
//                    'nomeDoutor' => $rowConsulta->nomeDoutor,
////                'nomePaciente' => $rowPront->nome,
////                'sobrenomePaciente' => $rowPront->sobrenome,
//                    'pacienteId' => $rowConsulta->pacientes_id,
//                    'secaoId' => (isset($dadosPront->tipo_anotacao_id)) ? $dadosPront->tipo_anotacao_id : '',
//                    'nomeSecao' => (isset($dadosPront->nomeSecao)) ? $dadosPront->nomeSecao : '',
////                'modeloProntuarioId' => $rowPront->modelo_prontuario_id,
//                    'jsonProntuario' => (isset($dadosPront->json_prontuario_cript)) ? (html_entity_decode(strip_tags((($rowPront->json_prontuario_cript))))) : '',
//                    'dataCadPront' => (isset($dadosPront->data_cad)) ? $dadosPront->data_cad : '',
//                    'userCad' => (isset($dadosPront->user_cad)) ? $dadosPront->user_cad : '',
//                    'nomeUserCad' => (isset($dadosPront->nomeUserCad)) ? Functions::utf8Fix((($rowPront->nomeUserCad))) : '',
//                    'prontuarioAdicional' => $rowConsulta->prontuario_adicional,
//                    'observacoes' => (isset($observacoes['data']) and count($observacoes['data']) > 0) ? $observacoes['data'] : null
//                ];
//            }
//            
        }

        //FOTOS
        if ($filtroRetorno == null or ( count($filtroRetorno) > 0 and in_array('fotos', $filtroRetorno))) {
            $qrFotos = $PacienteFotosRepository->getAll($idDominio, $pacienteId, $dadosFiltroFotos);

            foreach ($qrFotos as $rowFoto) {

                $url = env('APP_URL_CLINICAS') . $rowDominio->dominio . '/fotos/' . $rowFoto->foto;
                $urlThumb = env('APP_URL_CLINICAS') . $rowDominio->dominio . '/fotos/fotos_paciente_thumbs/' . $rowFoto->foto;

                if (!isset($DADOS_RETORNO[substr($rowFoto->data_cad, 0, 10)])) {
                    $DADOS_RETORNO[substr($rowFoto->data_cad, 0, 10)]['fotos'] = null;
                }


                $DADOS_RETORNO[substr($rowFoto->data_cad, 0, 10)]['fotos'][] = [
                    'id' => $rowFoto->id,
                    'title' => $rowFoto->title,
                    'pacienteId' => $rowFoto->pacientes_id,
                    'consultaId' => $rowFoto->consultas_id,
                    'dataCad' => $rowFoto->data_cad,
                    'url' => $url,
                    'urlThumb' => $urlThumb,
                ];
            }
        }

        //ARQUIVOS
        if ($filtroRetorno == null or ( count($filtroRetorno) > 0 and in_array('arquivos', $filtroRetorno))) {
            $qrArquivos = $PacienteArquivoRepository->getAll($idDominio, $pacienteId, $dadosFiltroFotos);

            foreach ($qrArquivos as $rowArquivo) {

                $url = env('APP_URL_CLINICAS') . $rowDominio->dominio . '/arquivos/' . $rowArquivo->arquivo;

                if (!isset($DADOS_RETORNO[substr($rowArquivo->data_cad, 0, 10)])) {
                    $DADOS_RETORNO[substr($rowArquivo->data_cad, 0, 10)]['arquivos'] = null;
                }
                $DADOS_RETORNO[substr($rowArquivo->data_cad, 0, 10)]['arquivos'][] = [
                    'id' => $rowArquivo->id,
                    'title' => $rowArquivo->title,
                    'pacienteId' => $rowArquivo->pacientes_id,
                    'consultaId' => $rowArquivo->consultas_id,
                    'dataCad' => $rowArquivo->data_cad,
                    'url' => $url,
                ];
            }
        }


//PRESCRICOES
        if ($filtroRetorno == null or ( count($filtroRetorno) > 0 and in_array('prescricoes', $filtroRetorno))) {
            $qrPrescricoes = $PacientePrescricaoRepository->getAll($idDominio, $dadosFiltro);
            $dadosPrescricao = null;
//dd($qrPrescricoes);
            foreach ($qrPrescricoes as $rowPrescricao) {

                $dadosPrescricao = [
                    'id' => $rowPrescricao->id,
                    'pacienteId' => $rowPrescricao->pacientes_id,
                    'consultaId' => $rowPrescricao->consulta_id,
                    'dataCad' => $rowPrescricao->data_consulta,
                    'nomeDoutor' => $rowPrescricao->nome,
                        //    'prescricao_especial' => $rowPrescricao->prescricao_especial,
                ];

                $itensPrescricao = null;
                $qrItensPrescricao = $PacientePrescricaoRepository->getItensByIdPrescricao($idDominio, $rowPrescricao->id);
                $i = 0;
                if (count($qrItensPrescricao) > 0) {
                    foreach ($qrItensPrescricao as $rowItem) {
                        $posologia = '';
                        if ($rowItem->med_vezes_ao_dia != 0) {
                            $posologia = $rowItem->med_tomar;
                            $posologia .= ' ' . $rowItem->med_quantidade_medida;
                            $posologia .= ' ' . $rowItem->med_medida_nome;
                            $posologia .= ' ' . ('vezes ao dia ' . $rowItem->med_vezes_ao_dia);
                            $posologia .= ' ' . ($rowItem->med_duracao);
                            $posologia .= ' ' . ($rowItem->med_quantidade_duracao);
                            $posologia .= ' ' . ($rowItem->med_tipo_duracao);
                        }
                        $tipo = 'normal';
                        if ($rowItem->especial == 1) {
                            $tipo = 'especial';
                        }
                        $itensPrescricao[$i][$tipo][] = [
                            'id' => $rowItem->idConsultaPrescItem,
                            'medNome' => $rowItem->med_nome,
                            'posologia' => $posologia,
                            'observacao' => $rowItem->observacao,
                        ];
                    }
                }
                $dadosPrescricao['itens'] = $itensPrescricao;

                if (!isset($DADOS_RETORNO[substr($rowPrescricao->data_consulta, 0, 10)])) {
                    $DADOS_RETORNO[substr($rowPrescricao->data_consulta, 0, 10)]['prescricoes'] = null;
                }

                $DADOS_RETORNO[substr($rowPrescricao->data_consulta, 0, 10)]['prescricoes'][] = $dadosPrescricao;
                unset($dadosPrescricao);
            }
        }



//ATESTADOS
        if ($filtroRetorno == null or ( count($filtroRetorno) > 0 and in_array('atestados', $filtroRetorno))) {

            $qrAtestados = $PacienteAtestadoRepository->getAll($idDominio, $dadosFiltro);

//        dd($qrAtestados);
            foreach ($qrAtestados as $rowAtestado) {

                if (!isset($DADOS_RETORNO[substr($rowAtestado->data_consulta, 0, 10)])) {
                    $DADOS_RETORNO[substr($rowAtestado->data_consulta, 0, 10)]['atestados'] = null;
                }
                $DADOS_RETORNO[substr($rowAtestado->data_consulta, 0, 10)]['atestados'][] = [
                    'id' => $rowAtestado->id,
                    'pacienteId' => $rowAtestado->pacientes_id,
                    'consultaId' => $rowAtestado->consulta_id,
                    'dataCad' => $rowAtestado->data_consulta,
                    'nomeDoutor' => $rowAtestado->nome,
                    'atestado' => $rowAtestado->conteudo,
                ];
            }
        }

///PROCEDIMENTOS
        if ($filtroRetorno == null or ( count($filtroRetorno) > 0 and in_array('procedimentos', $filtroRetorno))) {
            $qrConsultaProcedimentos = $ConsultaProcedimentoRepository->getHistoricoProcedimentosPagos($idDominio, $dadosFiltro);

            foreach ($qrConsultaProcedimentos as $rowProc) {
//                dd($rowProc);
                if (!isset($DADOS_RETORNO[substr($rowProc->data_consulta, 0, 10)])) {
                    $DADOS_RETORNO[substr($rowProc->data_consulta, 0, 10)]['procedimentos'] = null;
                }
                $DADOS_RETORNO[substr($rowProc->data_consulta, 0, 10)]['procedimentos'][] = [
//                    'id' => $rowProc->id,
                    'consultaId' => $rowProc->consulta_id,
                    'nomeProcedimento' => $rowProc->nome_procedimento,
                    'procedimentoId' => $rowProc->idProcedimento,
                ];
            }
        }

///Pedidos de exame
        if ($filtroRetorno == null or ( count($filtroRetorno) > 0 and in_array('pedidoExame', $filtroRetorno))) {
            $qrConsultaPedidoExame = $ConsultaPedidoExameRepository->getHistoricoProcedimentosPagos($idDominio, $dadosFiltro);

//            dd($qrConsultaPedidoExame);
            $dadosPedidoEx = null;
            foreach ($qrConsultaPedidoExame as $rowPedido) {

                $dadosPedidoEx = [
                    'id' => $rowPedido->id,
                    'consultaId' => $rowPedido->consultas_id,
                    'data' => $rowPedido->data_cad,
                ];

                $itensPedidos = null;
                $i = 0;
                $qrItensPedidos = $ConsultaPedidoExameRepository->getItensByPedidoId($idDominio, $rowPedido->id);
//                dd($qrItensPedidos);
                if (count($qrItensPedidos) > 0) {
                    foreach ($qrItensPedidos as $rowItem) {

                        $itensPedidos[$i] = [
                            'id' => $rowItem->id,
                            'procedimento' => [
                                'id' => $rowItem->procedimentos_id,
                                'nome' => $rowItem->nome_proc,
                            ],
                            'categoria' => [
                                'id' => $rowItem->procedimentos_cat_id,
                                'nome' => $rowItem->procedimentos_cat_nome
                            ],
                            'convenio' => [
                                'id' => $rowItem->convenios_id,
                                'nome' => $rowItem->convenios_nome,
                            ],
                            'possuiParceiro' => $rowItem->possui_parceiro,
                            'parceiro' => ($rowItem->possui_parceiro == 1) ? [
                        'id' => $rowItem->doutor_parceiro_id,
                        'nome' => $rowItem->nomeParceiro,
                            ] : null,
                            'qnt' => $rowItem->qnt,
                            'valor' => $rowItem->valorProc
                        ];
                        $i++;
                    }
                }
                $dadosPedidoEx['itens'] = $itensPedidos;

                if (!isset($DADOS_RETORNO[substr($rowPedido->data_cad, 0, 10)])) {
                    $DADOS_RETORNO[substr($rowPedido->data_cad, 0, 10)]['pedidoExame'] = null;
                }
                $DADOS_RETORNO[substr($rowPedido->data_cad, 0, 10)]['pedidoExame'][] = $dadosPedidoEx;
            }
        }


//  dd($qrConsultaProcedimentos);

        $RETORNO = null;
        $i = 0;

//         dd($DADOS_RETORNO);
        if ($DADOS_RETORNO != null) {
            krsort($DADOS_RETORNO);
            foreach ($DADOS_RETORNO as $dataDados => $row) {
                $RETORNO[$i]['data'] = $dataDados;
                $RETORNO[$i]['result'] = $DADOS_RETORNO[$dataDados];
                $i++;
            }

            return $this->returnSuccess($RETORNO);
        } else {
            return $this->returnError(null, "Sem registros nesse período");
        }
    }

    public function storeProntuarioSimplesAvulso($idDominio, $pacienteId, $dados) {

        $ConsultaProntuarioRepository = new ConsultaProntuarioRepository;

        $ArrayModeloProntuarioSimples = array(array(
                'tipo_campo' => 'area_texto',
                'label' => '',
                'type' => 'textarea',
                'class' => 'form-control input-sm',
                'rows' => 3,
                'value' => $dados['textoProntuario']
            ),
//            array(
//                'tipo_campo' => 'assinatura',
//                'label' => '',
//                'type' => 'assinatura',
//                'class' => 'form-control input-sm',
//                'rows' => 3,
//                'value' => 'Médico(a)'
//            ),
//            array(
//                'tipo_campo' => 'assinatura',
//                'label' => '',
//                'type' => 'assinatura',
//                'class' => 'form-control input-sm',
//                'rows' => 3,
//                'value' => 'CRM'
//            ),
        );

        $PacienteService = new PacienteService;
        $rowPaciente = $PacienteService->getById($idDominio, $pacienteId);

        if (!$rowPaciente['success']) {
            return $this->returnError(null, $rowPaciente['message']);
        }
        $rowPaciente = $rowPaciente['data'];

        $dataConsultaPront = date('Y-m-d');
        $horaConsultaPront = date('H:i');
        if (!isset($dados['consultaId'])) {


            $dadosConsultaInsert['pacientes_id'] = $pacienteId;
            $dadosConsultaInsert['data'] = date('d/m/Y');
            $dadosConsultaInsert['data_consulta'] = $dataConsultaPront;
            $dadosConsultaInsert['hora_anotacao'] = $horaConsultaPront;
            $dadosConsultaInsert['hora_consulta'] = $horaConsultaPront;
            $dadosConsultaInsert['identificador'] = $idDominio;
            $dadosConsultaInsert['prontuario_adicional'] = 1;
            $dadosConsultaInsert['administrador_id'] = (auth('clinicas')->check()) ? auth('clinicas')->user()->id : null;
            $ConsultaRepository = new ConsultaRepository;
            $consultaId = $ConsultaRepository->insertConsulta($idDominio, $dadosConsultaInsert);
        } else {
            $consultaId = $dados['consultaId'];

            $verificaProntConsulta = $ConsultaProntuarioRepository->getByConsultaId($idDominio, $dados['consultaId'], true);

            if (count($verificaProntConsulta) > 0 and (!isset($dados['atualizaProntuario']) or ( isset($dados['atualizaProntuario']) and $dados['atualizaProntuario'] != 1))) {
                return $this->returnError(null, "Já existe um prontuário para esta consulta");
            }
        }


        if (isset($dados['atualizaProntuario']) and $dados['atualizaProntuario'] == 1 and count($verificaProntConsulta) > 0) {
            $id = $verificaProntConsulta[0]->id;
            $dadosInsert['json_prontuario_cript'] = json_encode($ArrayModeloProntuarioSimples);
            $dadosInsert ['data_cad'] = date('Y-m-d H:i:s');

            $ConsultaProntuarioRepository->update($idDominio, $id, $dadosInsert);

//            $LogAtividadesService = new LogAtividadesService();
//            $LogAtividadesService->store($idDominio, 3, "Editou um prontuário do(a) paciente " . utf8_encode($rowPaciente['nome']) . " " . utf8_encode($rowPaciente['sobrenome']) . ' para o dia ' . Functions::dateDbToBr($dataConsultaPront) . utf8_encode(" às ") . $horaConsultaPront, $id, 25);
        } else {

            $dadosInsert ['identificador'] = $idDominio;
            $dadosInsert ['consulta_id'] = $consultaId;
            $dadosInsert ['tipo_anotacao_id'] = 1;
            $dadosInsert['json_prontuario_cript'] = json_encode($ArrayModeloProntuarioSimples);
            $dadosInsert ['data_cad'] = date('Y-m-d H:i:s');
            $dadosInsert ['prontuario_simples'] = 1;
            $dadosInsert ['user_cad'] = (auth('clinicas')->check()) ? auth('clinicas')->user()->id : null;

            $id = $ConsultaProntuarioRepository->store($idDominio, $dadosInsert);

            $LogAtividadesService = new LogAtividadesService();
            $LogAtividadesService->store($idDominio, 2, utf8_encode("Cadastrou um prontuário do(a) paciente ") . utf8_encode($rowPaciente['nome']) . " " . utf8_encode($rowPaciente['sobrenome']) . ' para o dia ' . Functions::dateDbToBr($dataConsultaPront) . utf8_encode(" às ") . $horaConsultaPront, $id, 25);
        }








        if ($id) {
            return $this->returnSuccess([
                        'id' => $id,
                        'textoProntuario' => $dadosInsert['json_prontuario_cript'],
            ]);
        } else {
            return $this->returnError(null, ["Ocorreu um erro ao cadastrar o prontuario"]);
        }
    }

    public function getPontuarioById($idDominio, $idConsultaPront, $dadosInput = null) {

        $ConsultaProntuarioRepository = new ConsultaProntuarioRepository;
        $rowProntuario = $ConsultaProntuarioRepository->getById($idDominio, $idConsultaPront);
        if ($rowProntuario) {
            $retorno = [
                'id' => $rowProntuario->id,
                'consultaId' => $rowProntuario->consulta_id,
                'dataCad' => $rowProntuario->data_cad,
                'prontuarioSimples' => $rowProntuario->prontuario_simples,
                'conteudo' => $rowProntuario->json_prontuario_cript,
            ];
            return $this->returnSuccess($retorno);
        } else {
            return $this->returnError(null, 'Prontuário não encontrado');
        }

        return $retorno;
    }

    public function getByConsultaId($idDominio, $idConsulta, $dadosInput = null) {


        $somenteProntSimples = false;
        if (isset($dadosInput['somenteProntuarioSimples']) and $dadosInput['somenteProntuarioSimples'] == 1) {
            $somenteProntSimples = true;
        }



        $ConsultaProntuarioRepository = new ConsultaProntuarioRepository;
        $qrProntuario = $ConsultaProntuarioRepository->getByConsultaId($idDominio, $idConsulta, $somenteProntSimples);

        $retorno = null;
        foreach ($qrProntuario as $row) {
            $retorno = [
                'id' => $row->id,
                'consultaId' => $row->consulta_id,
                'dataCad' => $row->data_cad,
                'prontuarioSimples' => $row->prontuario_simples,
                'conteudo' => $row->json_prontuario_cript,
            ];
            $qrObservacoes = $ConsultaProntuarioRepository->getObservacoes($idDominio, $row->id);
            if (count($qrObservacoes) > 0) {
                foreach ($qrObservacoes as $rowObs) {

                    $retorno['observacoes'][] = [
                        'id' => $rowObs->id,
                        'observacao' => $rowObs->observacao,
                        'dataCad' => $rowObs->data_cad,
                        'userIdCad' => $rowObs->administrador_id_cad,
                        'userNome' => Functions::utf8Fix($rowObs->nomeUserCad, true),
                    ];
                }
            }
//            dd($qrObservacoes);
        }
//        var_dump($rowObs)
        return $retorno;
    }

    public function storeObservacoes($idDominio, $idConsultaPront, $dadosInput) {

        $ConsultaProntuarioRepository = new ConsultaProntuarioRepository;
        $dadosInsert['consultas_prontuarios_id'] = $idConsultaPront;
        $dadosInsert['observacao'] = $dadosInput['observacao'];
        $qr = $ConsultaProntuarioRepository->storeObservacoes($idDominio, $idConsultaPront, $dadosInsert);

        if ($qr) {

            $row = $ConsultaProntuarioRepository->getObservacoes($idDominio, $idConsultaPront, ['id' => $qr]);

//            dd($row);
            $dadosObs['prontuarioId'] = $row->consultas_prontuarios_id;
            $dadosObs['observacao'] = $row->observacao;
            $dadosObs['userCad'] = [
                'id' => $row->administrador_id_cad,
                'nome' => ($row->nomeUserCad)
            ];
            return $this->returnSuccess($dadosObs, 'Cadastrado com successo');
        } else {
            return $this->returnError(null, ['Ocorreu um erro ao cadastrar a observação']);
        }
    }

    public function getObservacoes($idDominio, $idConsultaPront, $dadosFiltro = null) {

        $ConsultaProntuarioRepository = new ConsultaProntuarioRepository;

        $qr = $ConsultaProntuarioRepository->getObservacoes($idDominio, $idConsultaPront, $dadosFiltro);

        if ($qr) {
            $dados = [];
            foreach ($qr as $row) {
                $dados[] = [
                    'id' => $row->id,
                    'prontuarioId' => $row->consultas_prontuarios_id,
                    'observacao' => $row->observacao,
                    'dataCad' => $row->data_cad,
                    'userCad' => [
                        'id' => $row->administrador_id_cad,
                        'nome' => ($row->nomeUserCad)
                    ]
                ];
            }

            return $this->returnSuccess($dados, '');
        } else {
            return $this->returnError(null, ['Sem observações para este prontuário']);
        }
    }

}
