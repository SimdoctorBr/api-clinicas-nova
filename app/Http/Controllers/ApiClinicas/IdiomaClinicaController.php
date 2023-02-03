<?php

namespace App\Http\Controllers\ApiClinicas;

use Illuminate\Http\Request;
use App\Http\Controllers\ApiClinicas\Controller;
use App\Services\Clinicas\IdiomaClinicaService;

class IdiomaClinicaController extends Controller {

    private $idiomaClinicaService;

    public function __construct(IdiomaClinicaService $espServ) {
        $this->idiomaClinicaService = $espServ;
    }

    public function index(Request $request) {

        $getDominio = $this->getIdDominio($request, 'input', false);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

        $dadosFiltro = null;
        if ($request->has('withDoctors') and $request->query('withDoctors') == 'true') {
            $dadosFiltro['withDoctors'] = true;
        }

        $result = $this->idiomaClinicaService->getAll($idDominio, $dadosFiltro);

        return $result;
    }

}
