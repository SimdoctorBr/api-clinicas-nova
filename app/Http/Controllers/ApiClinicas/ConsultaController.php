<?php

namespace App\Http\Controllers\ApiClinicas;

use App\Http\Controllers\ApiClinicas\Controller;
use App\Services\Clinicas\AgendaService;
use Illuminate\Http\Request;
use App\Services\Clinicas\AtendimentoService;
use App\Services\Clinicas\ConsultaService;
use App\Services\Clinicas\HorariosService;

class ConsultaController extends Controller {

    private $agendaService;
    private $atendimentoService;
    private $consultaService;
    private $horarioService;

    public function __construct(AgendaService $agServ, AtendimentoService $atendSErv, ConsultaService $consServ, HorariosService $horServ) {
        $this->agendaService = $agServ;
        $this->atendimentoService = $atendSErv;
        $this->consultaService = $consServ;
        $this->horarioService = $horServ;
    }

    public function index(Request $request) {
        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }



        $result = $this->consultaService->getAll($idDominio, $request, $request->query());
        return $this->returnResponse($result);
    }

    public function getById(Request $request, $consultaId) {


        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

        $dadosFiltro['showProcedimentos'] = false;
        $dadosFiltro['showProntuarios'] = false;
        $showProntuarios = false;
        if ($request->has('showProcedimentos') and $request->query('showProcedimentos') == 'true') {
            $dadosFiltro['showProcedimentos'] = true;
        }
        if ($request->has('showProntuarios') and $request->query('showProntuarios') == 'true') {
            $dadosFiltro['showProntuarios'] = true;
        }

        $result = $this->consultaService->getById($idDominio, $consultaId, $dadosFiltro);
        return $this->returnResponse($result);
    }

    public function store(Request $request) {


        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

        $validator = validator($request->input(), [
            'doutorId' => 'required|numeric',
            'pacienteId' => 'required_without:paciente|numeric',
            'data' => 'required|date_format:Y-m-d',
            'horario' => 'required|date_format:H:i',
            'horarioFim' => 'date_format:H:i',
                ], [
            'doutorId.required' => 'Informe do id do(a) doutor(a)',
            'doutorId.numeric' => 'O id do(a) doutor(a) deve ser numérico',
            'pacienteId.required_without' => 'Infome o id do paciente',
            'pacienteId.numeric' => 'O id do paciente deve ser numérico',
            'data.required' => 'Informe a data do agendamento',
            'data.date_format' => 'Data inválida',
            'horario.required' => 'Informe o horário',
            'horario.date_format' => 'Horário invalido',
            'horarioFim.date_format' => 'Horário de término invalido',
        ]);

        $dadosPac = null;
        if ($request->has('paciente')) {

            if (!is_array($request->input('paciente'))) {
                return $this->sendErrorValidator("O campo 'paciente' deve ser um array");
            }

            if (isset($request->paciente['nome'])) {
                $dadosPac = $request->paciente;
            } else {
                $dadosPac = $request->paciente[0];
            }

            $validatePaciente = validator($dadosPac, [
                'nome' => 'required|min:3',
                'celular' => 'numeric|digits:11'
                    ], [
                'nome.required' => 'Informe o nome do paciente',
                'celular.numeric' => 'O celular  deve ser numérico',
                'celular.digits' => 'O celular  deve ser 11 digitos',
            ]);

            if ($validatePaciente->fails()) {
                return $this->sendErrorValidator($validatePaciente->errors()->all());
            }
        }
//        

        if ($validator->fails()) {
            return $this->sendErrorValidator($validator->errors()->all());
        } else {
            $result = $this->consultaService->store($idDominio, $request->input(), $dadosPac);
            return $result;
        }
    }

    public function update(Request $request, $consultaId) {


        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

        $validator = validator($request->input(), [
//            'doutorId' => 'required|numeric',
            'pacienteId' => 'required|numeric',
            'data' => 'required|date_format:Y-m-d',
            'horario' => 'required|date_format:H:i',
            'horarioFim' => 'date_format:H:i',
            'celularPaciente' => 'numeric|digits:11 ',
                ], [
            'pacienteId.required' => 'Informe o id do paciente',
            'pacienteId.numeric' => 'O id do paciente deve ser numérico',
            'data.required' => 'Informe a data da consulta',
            'horario.required' => 'Informe a hora da consulta',
            'horario.date_format' => 'Formato inválido',
            'horarioFim.date_format' => 'Formato inválido',
            'celularPaciente.numeric' => 'O celular deve ter somente números',
            'celularPaciente.digits' => 'O celular deve ter 11 números',
        ]);
        if ($validator->fails()) {
            return $this->sendErrorValidator($validator->errors()->all());
        } else {
            $result = $this->consultaService->update($idDominio, $consultaId, $request->input());
            return $result;
        }
    }

    public function delete(Request $request, $consultaId) {


        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }


        $result = $this->consultaService->delete($idDominio, $consultaId);
        return $result;
    }

    public function confirmar(Request $request, $consultaId) {

        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

        $meioConsulta = ($request->has('origemConfirmacao')) ? $request->input('origemConfirmacao') : null;

        $result = $this->consultaService->confirmar($idDominio, $consultaId, $meioConsulta);
        return $result;
    }

    public function desmarcar(Request $request, $consultaId) {

        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

        $desmarcadorPor = ($request->has('desmarcadorPor')) ? $request->input('desmarcadorPor') : 1;
        $motivo = ($request->has('motivo')) ? $request->input('motivo') : null;

        $result = $this->consultaService->desmarcar($idDominio, $consultaId, $desmarcadorPor, $motivo);
        return $result;
    }

    public function alterarStatus(Request $request, $consultaId) {

        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

        $validate = validator($request->input(), [
            'status' => 'required'
        ]);

        if ($validate->fails()) {
            return $this->sendErrorValidator($validate->errors()->all());
        } else {
            
         
            
            

            $dadosInput['motivo'] = ($request->has('motivo')) ? $request->input('motivo') : null;
            $dadosInput['desmarcadoPor'] = ($request->has('desmarcadoPor')) ? $request->input('desmarcadoPor') : null;
            $dadosInput['origemConfirmacao'] = ($request->has('origemConfirmacao')) ? $request->input('origemConfirmacao') : null;

            $result = $this->consultaService->alterarStatus($idDominio, $consultaId, $request->input('status'), $dadosInput);
            return $result;
        }
    }

}
