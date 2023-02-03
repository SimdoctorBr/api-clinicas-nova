<?php

namespace App\Http\Controllers\ApiClinicas;

use App\Http\Controllers\ApiClinicas\Controller;
use Illuminate\Http\Request;
use App\Services\Clinicas\ProcedimentoService;

class ProcedimentoController extends Controller {

    private $procedimentoService;

    public function __construct(ProcedimentoService $agServ) {
        $this->procedimentoService = $agServ;
    }

    public function getByConsulta(Request $request, $consultaId) {

        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

        $result = $this->procedimentoService->getByConsulta($idDominio, $consultaId, $request);
        return $this->returnResponse($result);
    }

    public function getByDoutor(Request $request, $doutorId) {

        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

        $result = $this->procedimentoService->getByDoutor($idDominio, $doutorId, $request);
     
        return $this->returnResponse($result);
    }

}
