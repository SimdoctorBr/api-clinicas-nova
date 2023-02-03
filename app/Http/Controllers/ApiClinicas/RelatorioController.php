<?php

namespace App\Http\Controllers\ApiClinicas;

use App\Http\Controllers\ApiClinicas\Controller;
use App\Services\Clinicas\Relatorios\RelatorioAgendamentoService;
use Illuminate\Http\Request;

class RelatorioController extends Controller {

    private $relAgendaService;

    public function __construct(RelatorioAgendamentoService $agServ) {
        $this->relAgendaService = $agServ;
    }

    public function getRelAgendamento(Request $request) {

        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

        $validate = validator($request->query(), [
            'data' => 'required|date',
            'dataFim' => 'date',
            'doutorId' => 'numeric'
                ], [
            'data.required' => 'Data não informada',
            'data.date' => 'Data inválida',
            'dataFim.date' => 'Data inválida',
            'doutorId.numeric' => 'Id do doutor inválido',
        ]);

        $dadosFiltro['data'] = $request->query('data');
        $dadosFiltro['dataFim'] = $request->query('dataFim');
        if ($request->has('doutorId') and!empty($request->query('doutorId'))) {
            $dadosFiltro['doutorId'] = $request->query('doutorId');
        }


        $result = $this->relAgendaService->getRelatorioAgendamento($this->getIdDominio(), $dadosFiltro);
        return $this->returnResponse($result);
    }

    public function getRelAgendamento2(Request $request) {

       
        $getDominio = $this->getIdDominio($request, 'input', true);

        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

      
        $validate = validator($request->query(), [
            'data' => 'required|date',
            'dataFim' => 'date',
            'doutorId' => 'numeric'
                ], [
            'data.required' => 'Data não informada',
            'data.date' => 'Data inválida',
            'dataFim.date' => 'Data inválida',
            'doutorId.numeric' => 'Id do doutor inválido',
        ]);

        $dadosFiltro['data'] = $request->query('data');
        $dadosFiltro['dataFim'] = $request->query('dataFim');
        if ($request->has('doutorId') and!empty($request->query('doutorId'))) {
            $dadosFiltro['doutorId'] = $request->query('doutorId');
        }


        $result = $this->relAgendaService->getRelatorioAgendamento2($idDominio, $dadosFiltro);
        return $this->returnResponse($result);
    }

}
