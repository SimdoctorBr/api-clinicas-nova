<?php

namespace App\Http\Controllers\ApiClinicas\Paciente;

use Illuminate\Http\Request;
use App\Http\Controllers\ApiClinicas\Controller as BaseController;
use App\Services\Clinicas\Paciente\PacienteFotosService;

class PacienteFotoController extends BaseController {

    private $pacienteFotoService;

    public function __construct(PacienteFotosService $pacFotoServ) {
        $this->pacienteFotoService = $pacFotoServ;
    }

    public function index(Request $request, $pacienteId) {

        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }
        $result = $this->pacienteFotoService->getAll($idDominio, $pacienteId, $request);
        return $this->returnResponse($result);
    }

    public function store(Request $request, $pacienteId) {

        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }
        $result = $this->pacienteFotoService->store($idDominio, $pacienteId, $request);

        return $this->returnResponse($result);
    }

    public function delete(Request $request, $pacienteId, $fotoId) {

        
   
        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }
        
        if (empty($fotoId)) {
            return $this->sendError('Informe o id da foto');
        }



        $result = $this->pacienteFotoService->delete($idDominio, $pacienteId, $fotoId);

        return $result;
    }

    public function update(Request $request, $pacienteId, $fotoId) {

        
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

        $result = $this->pacienteFotoService->update($idDominio, $pacienteId, $fotoId, $request->input('title'));

        return $result;
    }

}
