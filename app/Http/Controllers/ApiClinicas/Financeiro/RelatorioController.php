<?php

namespace App\Http\Controllers\ApiClinicas\Financeiro;

use App\Http\Controllers\ApiClinicas\Controller;
use App\Services\Clinicas\AgendaService;
use Illuminate\Http\Request;
use App\Services\Clinicas\Financeiro\RelatorioService;

class RelatorioController extends Controller {

    public function __construct() {
        
    }

    public function relMensalPdf(Request $request) {

        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }


        $validate = validator($request->query(), [
            'doutorId' => 'numeric',
            'mes' => 'numeric',
            'ano' => 'numeric',
                ], [
            '*.numeric' => ':attribute deve ser numérico'
        ]);

        if ($validate->fails()) {
            return response()->json([
                        'success' => false,
                        'data' => null,
                        'message' => $validate->errors()->all()[0]
                            ], 200);
        } else {

            $RelatorioService = new RelatorioService;
            $result = $RelatorioService->getRelatorioMensalPdf($idDominio, $request->query());

            return $result;
        }
    }

    public function relDiarioDoutor(Request $request) {

        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }


        $validate = validator($request->query(), [
            'doutorId' => 'required|numeric',
            'data' => 'date|required'
                ], [
            'doutorId.required' => 'Informe o Id do(a) doutor(a)',
            'data.required' => 'Informe a data',
            '*.numeric' => ':attribute deve ser numérico',
            '*.date' => 'Data inválida: :attribute',
        ]);

        if ($validate->fails()) {
            return response()->json([
                        'success' => false,
                        'data' => null,
                        'message' => $validate->errors()->all()[0]
                            ], 200);
        } else {

            $RelatorioService = new RelatorioService;
            $result = $RelatorioService->getRelatorioDiarioDoutor($idDominio, $request->query('doutorId'), $request->query());
            return $result;
        }
    }

    public function relDiarioDoutorCalendario(Request $request) {

        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

        $validate = validator($request->query(), [
            'doutorId' => 'required|numeric',
            'mes' => 'numeric|required',
            'ano' => 'numeric|required',
                ], [
            'doutorId.required' => 'Informe o id do(a) doutor(a)',
            'mes.required' => 'Informe o mês',
            'ano.required' => 'Informe o ano',
        ]);

        if ($validate->fails()) {
            return response()->json([
                        'success' => false,
                        'data' => null,
                        'message' => $validate->errors()->all()[0]
                            ], 200);
        } else {

            $RelatorioService = new RelatorioService;

            $dadosFiltro['mes'] = trim($request->query('mes'));
            $dadosFiltro['ano'] = trim($request->query('ano'));
            $dadosFiltro['calendario'] = true;

            $result = $RelatorioService->getRelatorioDiarioDoutor($idDominio, $request->query('doutorId'), $dadosFiltro);
            return $result;
        }
    }

}
