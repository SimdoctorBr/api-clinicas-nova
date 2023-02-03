<?php

namespace App\Http\Controllers\ApiClinicas;

use App\Http\Controllers\ApiClinicas\Controller;
use App\Services\Clinicas\AgendaService;
use Illuminate\Http\Request;
use App\Services\Clinicas\AtendimentoService;
use App\Services\Clinicas\CompromissoService;
use App\Services\Clinicas\HorariosService;

class CompromissoController extends Controller {

    private $agendaService;
    private $atendimentoService;
    private $compromissoService;
    private $horarioService;

    public function __construct(AgendaService $agServ, AtendimentoService $atendSErv, CompromissoService $consServ, HorariosService $horServ) {
        $this->agendaService = $agServ;
        $this->atendimentoService = $atendSErv;
        $this->compromissoService = $consServ;
        $this->horarioService = $horServ;
    }

    public function index(Request $request) {
        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }


        $validate = validator($request->query(), [
            'doutorId' => 'required|numeric',
            'data' => 'required|date_format:Y-m-d',
                ], [
            'doutorId.required' => 'Informe o id do(a) doutor(a)',
            'data.required' => 'Informe a data',
            'data.date_format' => 'Data inválida',
            'data.numeric' => 'O id deve ser numérico',
        ]);

        if ($validate->fails()) {
            return $this->sendErrorValidator($validate->errors()->all());
        } else {
            $dadosFiltro['data'] = $request->query('data');
            $dadosFiltro['dataFim'] = ($request->has('dataFim') and ! empty($request->query('dataFim'))) ? $request->query('dataFim') : null;


            $result = $this->compromissoService->getAll($idDominio, $request->query('doutorId'), $dadosFiltro);

            return $this->sendSuccess([
                        'success' => true,
                        'data' => $result
            ]);
        }
    }

    public function getById(Request $request, $compromissoId) {


        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }


        $result = $this->compromissoService->getById($idDominio, $compromissoId);
        return $result;
    }

    public function store(Request $request) {


        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

        $validator = validator($request->input(), [
            'compromisso' => 'required',
            'doutorId' => 'required|numeric',
            'data' => 'required|date_format:Y-m-d',
            'horario' => 'required|date_format:H:i',
            'horarioFim' => 'date_format:H:i',
                ], [
            'compromisso.required' => 'Informe o compromisso',
            'doutorId.required' => 'Informe o id do(a) doutor(a)',
            'data.required' => 'Informe a data do compromisso',
            'data.date_format' => 'Data inválida',
            'horario.required' => 'Informe o horário',
            'horario.date_format' => 'Horário inválida',
            'horarioFim.date_format' => 'Horário final inválido',
        ]);

        if ($validator->fails()) {
            return $this->sendErrorValidator($validator->errors()->all());
        } else {
            $result = $this->compromissoService->store($idDominio, $request->input());
            return $result;
        }
    }

    public function delete(Request $request, $compromissoId) {


        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }


        $result = $this->compromissoService->delete($idDominio, $compromissoId);
        return $result;
    }

    public function alterarStatus(Request $request, $compromissoId) {


        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }
        $validator = validator($request->input(), [
            'status' => 'required'
                ], [
            'status.required' => 'Informe o status do compromisso'
                ]
        );

        if ($validator->fails()) {
            return $this->sendErrorValidator($validator->errors()->all());
        } else {
            $result = $this->compromissoService->alterarStatus($idDominio, $compromissoId, $request->input('status'));
            return $result;
        }
    }

    public function update(Request $request, $idCompromisso) {


        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

        $validator = validator($request->input(), [
            'compromisso' => 'required',
//            'doutorId' => 'required|numeric',
            'data' => 'required|date_format:Y-m-d',
            'horario' => 'required|date_format:H:i',
            'horarioFim' => 'date_format:H:i',
                ], [
            'compromisso.required' => 'Informe o compromisso',
//            'doutorId.required' => 'Informe o id do(a) doutor(a)',
            'data.required' => 'Informe a data do compromisso',
            'data.date_format' => 'Data inválida',
            'horario.required' => 'Informe o horário',
            'horario.date_format' => 'Horário inválida',
            'horarioFim.date_format' => 'Horário final inválido',
        ]);


        if ($validator->fails()) {
            return $this->sendErrorValidator($validator->errors()->all());
        } else {
            $result = $this->compromissoService->store($idDominio, $request->input(), $idCompromisso);
            return $result;
        }
    }

}
