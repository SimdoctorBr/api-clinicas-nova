<?php

namespace App\Http\Controllers\ApiClinicas;

use App\Http\Controllers\ApiClinicas\Controller;
use Illuminate\Http\Request;
use App\Services\Clinicas\PlanoBeneficioService;
use App\Services\Clinicas\Paciente\PlanoBeneficio\PacientePlanoBeneficioService;

class PlanoBeneficioController extends Controller {

    public function __construct() {
        
    }

    public function index(Request $request) {
        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

        $PlanoBeneficioService = new PlanoBeneficioService;

        $result = $PlanoBeneficioService->getAll($idDominio);
        return $result;
    }

    public function store(Request $request) {
        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

        $validate = validator($request->input(), [
            'nome' => 'required',
            'valor' => 'required|numeric',
            'descontoTipo' => 'required|numeric',
            'descontoValor' => 'required|numeric',
            'periodo' => 'required|numeric'
                ], [
            'nome.required' => 'Informe o nome do plano',
            'valor.required' => 'Informe o valor do plano',
            'descontoTipo.required' => 'Informe o tipo de desconto do plano',
            'descontoValor.required' => 'Informe o valor de desconto do plano',
            'periodo.required' => 'Informe o período do plano',
            'valor.numeric' => 'O valor do plano deve ser númerico',
            'descontoTipo.numeric' => 'O tipo de desconto do plano deve ser númerico',
            'descontovalor.numeric' => 'O valor do desconto do plano deve ser númerico',
            'periodo.numeric' => 'O período do plano deve ser númerico',
        ]);

        if ($validate->fails()) {
            return $this->sendErrorValidator($validate->errors()->all()[0]);
        } else {
            $arrayDescontoTipo = [1, 2];
            $arrayPeriodo = [2, 3, 4, 5];

            if (!in_array($request->input('descontoTipo'), $arrayDescontoTipo)) {
                return $this->sendErrorValidator('Tipo de desconto inválido.');
            }
            if (!in_array($request->input('periodo'), $arrayPeriodo)) {
                return $this->sendErrorValidator('Período inválido.');
            }

            $PlanoBeneficioService = new PlanoBeneficioService;

            $result = $PlanoBeneficioService->store($idDominio, $request->input());
            return $result;
        }
    }

    public function update(Request $request, $idPlano) {
        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

        $validate = validator($request->input(), [
            'nome' => 'required',
            'valor' => 'required|numeric',
            'descontoTipo' => 'required|numeric',
            'descontoValor' => 'required|numeric',
            'periodo' => 'required|numeric'
                ], [
            'nome.required' => 'Informe o nome do plano',
            'valor.required' => 'Informe o valor do plano',
            'descontoTipo.required' => 'Informe o tipo de desconto do plano',
            'descontoValor.required' => 'Informe o valor de desconto do plano',
            'periodo.required' => 'Informe o período do plano',
            'valor.numeric' => 'O valor do plano deve ser númerico',
            'descontoTipo.numeric' => 'O tipo de desconto do plano deve ser númerico',
            'descontovalor.numeric' => 'O valor do desconto do plano deve ser númerico',
            'periodo.numeric' => 'O período do plano deve ser númerico',
        ]);

        if ($validate->fails()) {
            return $this->sendErrorValidator($validate->errors()->all()[0]);
        } else {
            $arrayDescontoTipo = [1, 2];
            $arrayPeriodo = [2, 3, 4, 5];

            if (!in_array($request->input('descontoTipo'), $arrayDescontoTipo)) {
                return $this->sendErrorValidator('Tipo de desconto inválido.');
            }
            if (!in_array($request->input('periodo'), $arrayPeriodo)) {
                return $this->sendErrorValidator('Período inválido.');
            }

            $PlanoBeneficioService = new PlanoBeneficioService;
            $result = $PlanoBeneficioService->update($idDominio, $idPlano, $request->input());
            return $result;
        }
    }

    public function delete(Request $request, $idPlano) {
        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

        $PlanoBeneficioService = new PlanoBeneficioService;

        $result = $PlanoBeneficioService->delete($idDominio, $idPlano);
        return $result;
    }

    public function getById(Request $request, $idPlano) {
        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

        $PlanoBeneficioService = new PlanoBeneficioService;
        $result = $PlanoBeneficioService->getById($idDominio, $idPlano);
        return $result;
    }

    public function getPlBeneficioPacienteAtivo(Request $request) {
        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

        $validate = validator($request->input(),
                [
                    'cpf' => 'required|numeric',
                ], [
            'cpf.required' => 'Informe o cpf',
            'cpf.numeric' => 'o CPF deve ser numérico',
                ]
        );

        if ($validate->fails()) {
            return $this->sendErrorValidator($validate->errors()->all()[0]);
        }

        $PacientePlanoBeneficioService = new PacientePlanoBeneficioService;

        $result = $PacientePlanoBeneficioService->buscaPlanoAtivoPacienteByCpf($idDominio, $request->input('cpf'));
        return $result;
    }

}
