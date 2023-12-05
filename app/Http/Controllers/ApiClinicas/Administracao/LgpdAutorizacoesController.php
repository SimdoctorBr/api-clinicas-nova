<?php

namespace App\Http\Controllers\ApiClinicas\Administracao;

use App\Http\Controllers\ApiClinicas\Controller;
use Illuminate\Http\Request;
use App\Services\Clinicas\Administracao\ModeloAutorizacoesService;
use App\Services\Clinicas\EmpresaService;

class LgpdAutorizacoesController extends Controller {

    public function index(Request $request) {

        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

        $ModeloAutorizacoesService = new ModeloAutorizacoesService;

        $result = $ModeloAutorizacoesService->getAll($idDominio);
        return $this->returnResponse($result);
    }

    public function getTermosCondicoes(Request $request) {

        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

        $EmpresaService = new EmpresaService;
        $result = $EmpresaService->getTermosCondicoes($idDominio);
      
        return $result;
    }
}
