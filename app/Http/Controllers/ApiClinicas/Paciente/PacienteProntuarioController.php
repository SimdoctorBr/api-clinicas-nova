<?php

namespace App\Http\Controllers\ApiClinicas\Paciente;

use Illuminate\Http\Request;
use App\Http\Controllers\ApiClinicas\Controller as BaseController;
use App\Services\Clinicas\Paciente\ProntuarioService;
use App\Services\Clinicas\ConsultaService;

class PacienteProntuarioController extends BaseController {

    private $prontuarioService;
    private $consultaService;

    public function __construct(ProntuarioService $pacFotoServ, ConsultaService $consultServ) {
        $this->prontuarioService = $pacFotoServ;
        $this->consultaService = $consultServ;
    }

    public function getHistoricoUnificado(Request $request, $pacienteId) {

        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

        $result = $this->prontuarioService->getHistoricoUnificado($idDominio, $pacienteId, $request);

        if (!$result['success']) {
            return $result;
        } else {
            return $this->returnResponse($result);
        }
    }

    public function storeProntuarioSimplesAvulso(Request $request, $pacienteId) {

        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

        $validate = validator($request->input(), [
            'textoProntuario' => 'required',
            'consultaId' => 'numeric'
                ], [
            'textoProntuario.required' => 'O texto do prontuário deve ser preenchido',
            'textoProntuario.numeric' => 'O id da consulta dever ser numérico'
        ]);

        if ($validate->fails()) {
            return $this->sendError($validate->errors()->all(), $validate->errors());
        } else {

            $dadosInput['textoProntuario'] = $request->input('textoProntuario');
            if ($request->has('consultaId') and ! empty($request->input('consultaId'))) {

                $rowConsulta = $this->consultaService->getById($idDominio, $request->input('consultaId'));
                if (!$rowConsulta['success']) {
                    return $this->sendError($rowConsulta['message']);
                }
                $dadosInput['consultaId'] = $request->input('consultaId');
            }

            if ($request->has('atualizar') and $request->query('atualizar') == 'true') {
                $dadosInput['atualizaProntuario'] = 1;
            }

            $result = $this->prontuarioService->storeProntuarioSimplesAvulso($idDominio, $pacienteId, $dadosInput);
        }

//        dd($result);
        if (!$result['success']) {
            return $result;
        } else {
            return $this->returnResponse($result);
        }
    }

    public function storeObservacao(Request $request, $pacienteId, $prontuarioId) {

        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

        $validate = validator($request->input(), ['observacao' => 'required'], ['observacao.required' => 'O campo de observação deve ser preenchido'
        ]);

        $rowProntuario = $this->prontuarioService->getPontuarioById($idDominio, $prontuarioId);

        if (!$rowProntuario['success']) {
            return $this->sendError($rowProntuario['message']);
        }


        if ($validate->fails()) {
            return $this->sendError($validate->errors()->all(), $validate->errors());
        } else {
            return $this->prontuarioService->storeObservacoes($idDominio, $prontuarioId, $request->input());
        }
    }

    public function getObservacoes(Request $request, $pacienteId, $prontuarioId) {

        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

//        $validate = validator($request->input(),[],
//                []);
//
//
//        if ($validate->fails()) {
//            return $this->sendError($validate->errors()->all(), $validate->errors());
//        } else {
        return $this->prontuarioService->getObservacoes($idDominio, $prontuarioId);
//        }
    }

}
