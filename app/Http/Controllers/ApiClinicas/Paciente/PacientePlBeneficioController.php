<?php

namespace App\Http\Controllers\ApiClinicas\Paciente;

use Illuminate\Http\Request;
use App\Http\Controllers\ApiClinicas\Controller as BaseController;
use Illuminate\Validation\Rules\Password;
use App\Services\Clinicas\Paciente\PlanoBeneficio\PacientePlanoBeneficioService;

class PacientePlBeneficioController extends BaseController {

    private $pacienteService;

    public function store(Request $request) {


        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }
    }

    public function index(Request $request, $pacienteId) {




        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

//        $idDominio = 3580;
        $PacientePlanoBeneficioService = new PacientePlanoBeneficioService;
        $result = $PacientePlanoBeneficioService->getAllPlanosBeneficios($idDominio, $pacienteId);
        return $result;
    }

    public function planoAtivo(Request $request, $pacienteId) {

        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

        $PacientePlanoBeneficioService = new PacientePlanoBeneficioService;
        $result = $PacientePlanoBeneficioService->getPlanoAtivo($idDominio, $pacienteId);
        return $result;
    }

    public function planoHistoricoPagamento(Request $request, $pacienteId, $plBeneficioContratadoId) {

        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

        $validate = validator($request->query(), [
            'dataVencimento' => 'nullable|date',
            'dataVencimentoFim' => 'nullable|date',
            'page' => 'nullable|numeric',
            'perPage' => 'nullable|numeric',
                ], [
            'dataVencimento.date' => 'Formato de data inválido',
            'dataVencimentoFim.date' => 'Formato de data inválido',
            'page' => "O campo 'page' deve ser numérico",
            'perPage' => "O campo 'perPage' deve ser numérico",
        ]);

        if ($validate->fails()) {
            return $this->sendErrorValidator($validate->errors()->all()[0]);
        }



        $PacientePlanoBeneficioService = new PacientePlanoBeneficioService;
        $result = $PacientePlanoBeneficioService->getPlBeneficioHistoricoPagamento($idDominio, $pacienteId, $plBeneficioContratadoId, $request->query());
        return $result;
    }

    public function delete(Request $request, $pacienteId, $plBeneficioContratadoId) {

        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

        $PacientePlanoBeneficioService = new PacientePlanoBeneficioService;
        $result = $PacientePlanoBeneficioService->cancelarPlano($idDominio, $pacienteId, $plBeneficioContratadoId);
        return $result;
    }

    public function contrataPlano(Request $request, $pacienteId) {

        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

        $validate = validator($request->input(), [
            'planoBeneficioId' => 'required|numeric',
            'nomeCompleto' => 'required|min:5',
            'email' => 'required|email',
            'cpf' => 'required|numeric',
            'celular' => 'required_if:formaPagamento,cartao_credito|numeric',
            'formaPagamento' => 'required',
                ], [
            'planoBeneficioId.required' => 'Informe o id do plano de benefício',
            'nomeCompleto.required' => 'Informe o nome completo do paciente',
            'email.required' => 'Informe o email do paciente',
            'email.required' => 'Informe o email do paciente',
            'celular.required_if' => 'Informe o número do celular',
            'formaPagamento.required' => 'Informe a forma de pagamento',
            'planoBeneficioId.numeric' => 'O Plano de benfício deve ser numérico',
            'cpf.numeric' => 'O CPF deve ser numérico',
        ]);

        $arrayFormaPag = ['cartao_credito'];

        if ($validate->fails()) {
            return $this->sendErrorValidator($validate->errors()->all()[0]);
        }

        if (!in_array($request->input('formaPagamento'), $arrayFormaPag)) {
            return $this->sendErrorValidator('Forma de pagamento inválida');
        }


        $PacientePlanoBeneficioService = new PacientePlanoBeneficioService;
        $result = $PacientePlanoBeneficioService->contrataPlano($idDominio, $pacienteId, $request->input());
        return $result;
    }

    public function alterarPlano(Request $request, $pacienteId) {

        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

        $validate = validator($request->input(), [
            'planoBeneficioId' => 'required|numeric',
            'diaAlteracao' => 'required|numeric',
//            'nomeCompleto' => 'required|min:5',
//            'email' => 'required|email',
//            'cpf' => 'required|numeric',
//            'celular' => 'required_if:formaPagamento,cartao_credito|numeric',
//            'formaPagamento' => 'required',
                ], [
            'planoBeneficioId.required' => 'Informe o id do plano de benefício',
            'diaAlteracao.required' => 'Informe o campo o dia da alteração',
            'diaAlteracao.numeric' => 'O campo o dia da alteração deve ser numérico',
//            'nomeCompleto.required' => 'Informe o nome completo do paciente',
//            'email.required' => 'Informe o email do paciente',
//            'email.required' => 'Informe o email do paciente',
//            'celular.required_if' => 'Informe o número do celular',
//            'formaPagamento.required' => 'Informe a forma de pagamento',
//            'planoBeneficioId.numeric' => 'O Plano de benfício deve ser numérico',
//            'cpf.numeric' => 'O CPF deve ser numérico',
        ]);

        $arrayFormaPag = ['cartao_credito'];
        $arrayDiaAlteracao = [1, 2];

        if ($validate->fails()) {
            return $this->sendErrorValidator($validate->errors()->all()[0]);
        }

        if (!in_array($request->input('diaAlteracao'), $arrayDiaAlteracao)) {
            return $this->sendErrorValidator("Valor inválido no campo 'diaAlteracao' ");
        }


        $PacientePlanoBeneficioService = new PacientePlanoBeneficioService;
        $result = $PacientePlanoBeneficioService->alterarPlano($idDominio, $pacienteId, $request->input());
        return $result;
    }

    public function cancelarAlteracaoPlano(Request $request, $pacienteId) {

        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

        $PacientePlanoBeneficioService = new PacientePlanoBeneficioService;
        $result = $PacientePlanoBeneficioService->cancelarAlteracaoPlano($idDominio, $pacienteId);
        return $result;
    }

}
