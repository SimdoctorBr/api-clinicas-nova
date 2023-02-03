<?php

namespace App\Http\Controllers\ApiClinicas;

use App\Http\Controllers\ApiClinicas\Controller;
use App\Services\Clinicas\AgendaService;
use Illuminate\Http\Request;
use App\Services\Clinicas\AtendimentoService;
use App\Services\Clinicas\HorariosService;
use App\Services\Clinicas\BloqueioAgendaService;
use App\Repositories\Clinicas\StatusRefreshRepository;

class AgendaController extends Controller {

    private $agendaService;
    private $atendimentoService;
    private $horarioService;

    public function __construct(AgendaService $agServ, AtendimentoService $atendSErv, HorariosService $horServ) {
        $this->agendaService = $agServ;
        $this->atendimentoService = $atendSErv;
        $this->horarioService = $horServ;
    }

    public function index() {
        
    }

    public function resumoDiario(Request $request, $doutorId) {

        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }


        $result = $this->agendaService->getResumoDiario($idDominio, $doutorId, $request);
        return $this->returnResponse($result);
    }

    public function filaEspera(Request $request, $doutorId) {

        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

        $result = $this->agendaService->getFilaEspera($idDominio, $doutorId, $request);
        return $this->returnResponse($result);
    }

    public function iniciarAtendimento(Request $request, $consultaId) {

        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }


        $result = $this->atendimentoService->iniciarAtendimento($idDominio, $consultaId);

        if ($result['success']) {
            return $this->sendSuccess($result);
        } else {
            return $this->returnResponse($result);
        }
    }

    public function salvarAtendimento(Request $request, $consultaId) {

        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

        $validate = validator($request->input(), [
//            'textoProntuarioSimples' => 'required',
            'procedimentos' => 'required|array',
            'tipoDesconto' => 'required_with:valorTipoDesconto',
            'valorTipoDesconto' => 'required_with:tipoDesconto',
//            'desconto' => 'numeric',
            'tipoAcrescimo' => 'required_with:valorTipoAcrescimo',
            'valorTipoAcrescimo' => 'required_with:tipoAcrescimo',
//            'acrescimo' => 'numeric',
            'valorTotal' => 'required|numeric',
                ], [
//            'textoProntuarioSimples.required' => 'O prontuário não foi preenchido',
            'procedimentos.required' => 'Nenhum procedimento adicionado',
            'procedimentos.array' => 'Nenhum procedimento adicionado',
            'tipoDesconto.required_with' => 'Informe o tipo de desconto',
            'valorTipoDesconto.required_with' => 'Informe o valor do tipo de desconto',
            'desconto.numeric' => 'O desconto dever ser numérico',
            'tipoAcrescimo.required_with' => 'Informe o tipo de acréscimo',
            'valorTipoAcrescimo.required_with' => 'Informe o valor do tipo de acréscimo',
            'acrescimo.numeric' => 'O acréscimo dever ser numérico',
            'valorTotal.required' => 'Informe o valor total',
            'valorTotal.numeric' => 'O valor total dever ser numérico',
        ]);

        if ($validate->fails()) {
            return $this->sendErrorValidator($validate->errors()->all()[0]);
        } else {



            $dadosInput = $request->input();

            $dadosInput['finalizar'] = false;
            if ($request->has('finalizar') and!empty($request->input('finalizar'))) {
                $dadosInput['finalizar'] = ($request->input('finalizar') == "true") ? true : false;
            }


            $result = $this->atendimentoService->salvarAtendimento($idDominio, $consultaId, $dadosInput);

            return $result;

//            return $this->returnResponse($result);
        }
    }

    public function listaHorarios(Request $request, $doutorId) {



        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }



        $result = $this->agendaService->getHorariosByDoutor($idDominio, $doutorId, $request);
        return $this->returnResponse($result);
    }

    public function percentualCalPreenchido(Request $request, $doutorId) {

        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }


        $result = $this->agendaService->getPercentualCalPreenchido($idDominio, $doutorId, $request);
        return $this->returnResponse($result);
    }

    public function bloqueioRapidoAgenda(Request $request, $doutorId) {
        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }


        $dadosInput = [];
        $validate = validator($request->input(), [
            'data' => 'required|date',
            'dataFim' => 'date',
            'diaInteiro' => 'numeric',
            'hora' => 'date_format:H:i|required_if:diaInteiro,|required_if:diaInteiro,0|required_if:diaInteiro,',
            'horaFim' => 'date_format:H:i'
                ], [
            'data.required' => 'Data não informada',
            'data.date' => 'Data inválida',
            'dataFim.date' => 'Data inválida',
            'diaInteiro.numeric' => 'O campo dia Inteiro deve ser numérico',
            'hora.hour' => 'Hora inválida',
            'hora.required_if' => 'A hora deve ser informada',
            'hora.date_format' => 'Hora inválida',
            'horaFim.date_format' => 'Hora inválida',
        ]);

        if ($validate->fails()) {
            return $this->sendError($validate->errors()->all(), $validate->errors());
        } else {
            $BloqueioAgendaService = new BloqueioAgendaService;
            $result = $BloqueioAgendaService->bloqueioRapidoAgenda($idDominio, $doutorId, $request->input());
            return $result;
        }
    }

    public function desbloqueioRapidoAgenda(Request $request, $doutorId) {

        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }


        $dadosInput = [];
        $validate = validator($request->input(), [
            'data' => 'required|date',
            'hora' => 'required|date_format:H:i',
            'areaBloqueio' => 'required',
                ], [
            'data.required' => 'Data não informada',
            'data.date' => 'Data inválida',
            'hora.hour' => 'Hora inválida',
            'hora.date_format' => 'Hora inválida',
        ]);

        if ($validate->fails()) {
            return $this->sendError($validate->errors()->all(), $validate->errors());
        } else {
            $BloqueioAgendaService = new BloqueioAgendaService;
            $result = $BloqueioAgendaService->desbloqueiaHorario($idDominio, $doutorId, $request->input());
            return $result;
        }
    }

    public function verificaMudancaAgenda(Request $request, $doutorId) {
        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }
        $StatusRefreshRepository = new StatusRefreshRepository();

        $verifica = $StatusRefreshRepository->verificaStatus($idDominio, $doutorId);
        $verificaTodos = $StatusRefreshRepository->verificaStatusTodos($idDominio);

        $retorno = [
            'agendaDoutor' => $verifica,
            'todos' => $verificaTodos,
        ];

        return response()->json($retorno);
    }

    public function alertas(Request $request) {


        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

        $result = $this->agendaService->getConsultasAlertas($idDominio);
        return $result;
    }

}
