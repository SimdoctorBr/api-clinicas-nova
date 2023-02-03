<?php

namespace App\Http\Controllers\ApiClinicas;

use App\Http\Controllers\ApiClinicas\Controller;
use Illuminate\Http\Request;
use App\Services\Clinicas\ConvenioService;

class ConvenioController extends Controller {

    public function __construct() {
        
    }

    public function index(Request $request) {
        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

        $ConvenioService = new ConvenioService;

        $result = $ConvenioService->getAll($idDominio);
        return $this->returnResponse($result);
    }

    public function getById(Request $request, $consultaId) {
        
    }

}
