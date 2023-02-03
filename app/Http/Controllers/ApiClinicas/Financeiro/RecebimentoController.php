<?php

namespace App\Http\Controllers\ApiClinicas\Financeiro;

use App\Http\Controllers\ApiClinicas\Controller;
use App\Services\Clinicas\AgendaService;
use Illuminate\Http\Request;
use App\Services\Clinicas\Financeiro\RecebimentoService;

class RecebimentoController extends Controller {

    
    public function store(Request $request) {
        
        
          $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }
        
        $RecebimentoService = new RecebimentoService;

        $validate = validator($request->input(),
                [
                    'consultasId' => 'numeric',
                    'pacientesId' => 'numeric',
//                    'orcamentosId' => 'numeric',
                ], [
            'consultasId.numeric' => 'O campo \'consultasId\' deve ser numérico',
            'consultasId.numeric' => 'O campo \'consultasId\' deve ser numérico',
        ]);

      
        if ($validate->fails()) {
            return response()->json([
                'success' =>false,
                'error' =>$validate->errors()->all()[0]
            ]);
        } else {


            $result = $RecebimentoService->store($idDominio, $request->input());

            if (!$result['success']) {
                return $result;
            } else {
                return $this->returnResponse($result);
            }
        }
        
        
        
    }

}
