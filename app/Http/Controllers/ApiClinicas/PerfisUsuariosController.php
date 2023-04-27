<?php

namespace App\Http\Controllers\ApiClinicas;

use App\Http\Controllers\ApiClinicas\Controller;
use Illuminate\Http\Request;
use App\Services\Clinicas\PerfisUsuariosService;

class PerfisUsuariosController extends Controller {

    public function index(Request $request) {
        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }
        
        $PerfisUsuariosService = new PerfisUsuariosService;

        $result =  $PerfisUsuariosService->getAll($idDominio);
        return $result;
    }

}
