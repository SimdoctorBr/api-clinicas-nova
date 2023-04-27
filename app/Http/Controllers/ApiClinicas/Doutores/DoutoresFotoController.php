<?php

namespace App\Http\Controllers\ApiClinicas\Doutores;

use Illuminate\Http\Request;
use App\Http\Controllers\ApiClinicas\Controller as BaseController;
use App\Services\Clinicas\Doutores\DoutoresFotosService;

class DoutoresFotoController extends BaseController {

    private $doutoresFotoService;

    public function __construct(DoutoresFotosService $pacFotoServ) {
        $this->doutoresFotoService = $pacFotoServ;
    }

    public function index(Request $request, $doutorId) {

        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }
        $result = $this->doutoresFotoService->getAll($idDominio, $doutorId, $request);

        if (!$result['success']) {
            return response()->json($result);
        } else {
            return $this->returnResponse($result);
        }
    }

    public function store(Request $request, $doutorId) {

        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }
        $result = $this->doutoresFotoService->store($idDominio, $doutorId, $request);
        
        
        return $result;
    }

    public function delete(Request $request, $doutorId, $fotoId) {



        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

        if (empty($fotoId)) {
            return $this->sendError('Informe o id da foto');
        }



        $result = $this->doutoresFotoService->delete($idDominio, $doutorId, $fotoId);

        return $result;
    }

    public function update(Request $request, $doutorId, $fotoId) {


        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

        if (empty($fotoId)) {
            return $this->sendError('Informe o id da foto');
        }


        if (!$request->has('title') or empty($request->input('title'))) {
            return $this->sendError('Informe o nome da foto');
        }

        $result = $this->doutoresFotoService->update($idDominio, $doutorId, $fotoId, $request->input('title'));

        return $result;
    }

}
