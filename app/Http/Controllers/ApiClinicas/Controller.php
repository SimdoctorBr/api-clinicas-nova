<?php

namespace App\Http\Controllers\ApiClinicas;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Services\Gerenciamento\DominioService;
use Illuminate\Http\Request;

class Controller extends BaseController {

    public function respondWithToken($token) {
        return response()->json([
                    'token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => null
                        ], 200);
    }

    protected function sendSuccess($data, $msg = null, $codeStatus = 200) {

        return response()->json($data, $codeStatus);
    }

    protected function sendError($msg = null, $data = null, $codeStatus = 400) {

        return response()->json([
                    'error' => $msg
                        ], $codeStatus);
    }

    protected function sendErrorValidator($msg = null, $data = null, $codeStatus = 200) {

        return response()->json([
                    'success' => false,
                    'data' => $data,
                    'message' => $msg
                        ], $codeStatus);
    }

    protected function returnResponse($result) {
        if ($result['success']) {
            return $this->sendSuccess($result['data']);
        } else {
            return $this->sendError($result['message'], $result['data']);
        }
    }

    /**
     * 
     * @param \App\Http\Controllers\ApiClinicas\Request $request
     * @param type $tipoRequest  - 'query' ou 'input'
     * @return type
     */
    public function getIdDominio(Request $request = null, $tipoRequest = 'query', $perfilIdInternoObrigatorio = false) {


            
   
        if (auth('clinicas')->check()) {
            return [
                'success' => true,
                'perfisId' => auth('clinicas')->user()->identificador,
                
            ];
        } elseif (auth('clinicas_pacientes')->check()) {
            return [
                'success' => true,
                'perfisId' => auth('clinicas_pacientes')->user()->identificador
            ];
        } elseif (auth('interno_api')->check()) {

            
            $DominioService = new DominioService;
            $idsDominio = $DominioService->getDominiosByUserApiInterno(auth('interno_api')->user()->id);
            
        
            
            if ($perfilIdInternoObrigatorio && empty($request->$tipoRequest('perfilId'))) {
                return [
                    'success' => false,
                    'msg' => 'Informe o id do perfil'
                ];
            }
            if ($request != null && $request->$tipoRequest('perfilId') !== null) {

                if (!empty($request->$tipoRequest('perfilId')) && in_array($request->$tipoRequest('perfilId'), $idsDominio)) {
                  
                    return [
                        'success' => true,
                        'perfisId' => request('perfilId')
                    ];
                } else {
                    return [
                        'success' => false,
                        'msg' => 'Perfil não encontrado'
                    ];
                }
            }

            return [
                'success' => true,
                'perfisId' => $idsDominio
            ];
        }
    }

}
