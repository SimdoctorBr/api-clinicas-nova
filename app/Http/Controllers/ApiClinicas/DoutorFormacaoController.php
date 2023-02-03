<?php

namespace App\Http\Controllers\ApiClinicas;

use Illuminate\Http\Request;
use App\Http\Controllers\ApiClinicas\Controller;
use App\Services\Clinicas\DoutorFormacaoService;

class DoutorFormacaoController extends Controller {

    private $doutorFormacaoService;

    public function __construct(DoutorFormacaoService $espServ) {
        $this->doutorFormacaoService = $espServ;
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


        $result = $this->doutorFormacaoService->getAll($idDominio, $dadosFiltro);

        return $result;
    }

}
