<?php

namespace App\Http\Controllers\ApiClinicas\Paciente;

use Illuminate\Http\Request;
use App\Http\Controllers\ApiClinicas\Controller as BaseController;
use App\Services\Clinicas\Paciente\PacienteConvenioService;

class PacienteConvenioController extends BaseController {

    private $pacienteConvenioService;

    public function __construct(PacienteConvenioService $pacFotoServ) {
        $this->pacienteConvenioService = $pacFotoServ;
    }

    public function index(Request $request, $pacienteId) {

        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }


        $result = $this->pacienteConvenioService->getAll($idDominio, $pacienteId);

        if (!$result['success']) {
            return $result;
        } else {
            return $this->returnResponse($result);
        }
    }

    public function store(Request $request, $pacienteId) {

        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

        $validate = validator($request->input(),
                [
                    'conveniosId' => 'required|numeric',
                    'numeroCarteira' => 'required|numeric',
                    'validadeCarteira' => 'required|date',
                ], [
            'conveniosId.required' => 'Informe o id do convênio',
            'conveniosId.numeric' => 'O id do convênio deve ser numérico',
            'numeroCarteira.required' => 'O campo  \'numeroCarteira\' deve ser informado',
            'numeroCarteira.numeric' => 'O campo  \'numeroCarteira\' deve ser numérico',
            'validadeCarteira.required' => 'O campo  \'validadeCarteira\' deve ser informado',
            'validadeCarteira.date' => 'O campo  \'validadeCarteira\' deve ser uma data',
        ]);

        if ($validate->fails()) {
            return response()->json([
                        'success' => false,
                        'error' => $validate->errors()->all()[0]
            ]);
        } else {


            $result = $this->pacienteConvenioService->store($idDominio, $pacienteId, $request->input());

            if (!$result['success']) {
                return $result;
            } else {
                return $this->returnResponse($result);
            }
        }
    }

    public function update(Request $request, $pacienteId, $convenioId) {

        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

        $validate = validator($request->input(),
                [
                    'numeroCarteira' => 'required|numeric',
                    'validadeCarteira' => 'required|date',
                ], [
            'conveniosId.required' => 'Informe o id do convênio',
            'conveniosId.numeric' => 'O id do convênio deve ser numérico',
            'numeroCarteira.required' => 'O campo  \'numeroCarteira\' deve ser informado',
            'numeroCarteira.numeric' => 'O campo  \'numeroCarteira\' deve ser numérico',
            'validadeCarteira.required' => 'O campo  \'validadeCarteira\' deve ser informado',
            'validadeCarteira.date' => 'O campo  \'validadeCarteira\' deve ser uma data',
        ]);

        if ($validate->fails()) {
            return response()->json([
                        'success' => false,
                        'error' => $validate->errors()->all()[0]
            ]);
        } else {


            $result = $this->pacienteConvenioService->update($idDominio, $pacienteId, $convenioId, $request->input());

            if (!$result['success']) {
                return $result;
            } else {
                return $this->returnResponse($result);
            }
        }
    }

    public function delete(Request $request, $pacienteId, $convenioId) {

        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }


        $result = $this->pacienteConvenioService->delete($idDominio, $pacienteId, $convenioId);

        if (!$result['success']) {
            return $result;
        } else {
            return $this->returnResponse($result);
        }
    }

}
