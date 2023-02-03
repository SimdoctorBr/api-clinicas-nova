<?php

namespace App\Http\Controllers\ApiClinicas\Financeiro;

use App\Http\Controllers\ApiClinicas\Controller;
use App\Services\Clinicas\AgendaService;
use Illuminate\Http\Request;
use App\Services\Clinicas\Financeiro\FormaPagamentoService;

class FormaPagamentoController extends Controller {

    public function __construct() {
        
    }

    public function getAll(Request $request) {

        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }


//        if ($validate->fails()) {
//            return response()->json([
//                        'success' => false,
//                        'data' => null,
//                        'message' => $validate->errors()->all()[0]
//                            ], 200);
//        } else {

            $FormaPagamentoService = new FormaPagamentoService;
            $result = $FormaPagamentoService->getAll($idDominio);

            return $result;
//        }
    }


}
