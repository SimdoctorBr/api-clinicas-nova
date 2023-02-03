<?php

namespace App\Http\Controllers\ApiClinicas;

use Illuminate\Http\Request;
use App\Http\Controllers\ApiClinicas\Controller;
use App\Services\Clinicas\GrupoAtendimentoService;

class GrupoAtendimentoController extends Controller {

    private $grupoAtendimentoService;

    public function __construct(GrupoAtendimentoService $espServ) {
        $this->grupoAtendimentoService = $espServ;
    }

    public function index(Request $request) {
        $dadosFiltro = null;
        if ($request->has('withDoctors') and $request->query('withDoctors') == 'true') {
            $dadosFiltro['withDoctors'] = true;
        }

        $getDominio = $this->getIdDominio($request, 'input', false);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }


        $result = $this->grupoAtendimentoService->getAll($idDominio, $dadosFiltro);
        return $this->returnResponse($result);
    }

}
