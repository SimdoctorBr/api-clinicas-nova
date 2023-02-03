<?php

namespace App\Http\Controllers\ApiClinicas;

use App\Http\Controllers\ApiClinicas\Controller;
use Illuminate\Http\Request;
use App\Services\Clinicas\EmpresaService;

class EmpresaController extends Controller {

    private $empresaService;

    public function __construct(EmpresaService $agServ) {
        $this->empresaService = $agServ;
    }

    public function getAll(Request $request) {


        $getDominio = $this->getIdDominio($request, 'input', false);
      
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }      

        $result = $this->empresaService->getAll($idDominio);
        return $this->returnResponse($result);
    }

}
