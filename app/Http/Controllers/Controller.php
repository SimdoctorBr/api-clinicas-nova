<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController {

    public function respondWithToken($token, $authTokenDocbiz = null) {


        $retorno = [
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => null
        ];
        
        if(!empty($authTokenDocbiz)){
            $retorno['authTokenBio'] = $authTokenDocbiz;
        }


        return response()->json($retorno, 200);
    }

    protected function sendSuccess($data, $msg = null, $codeStatus = 200) {

        return response()->json($data, $codeStatus);
    }

    protected function sendError($msg = null, $data = null, $codeStatus = 400) {

        return response()->json([
                    'error' => $msg
                        ], $codeStatus);
    }

}
