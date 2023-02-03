<?php

namespace App\Http\Controllers\ApiClinicas\Paciente;

use Illuminate\Http\Request;
use App\Http\Controllers\ApiClinicas\Controller as BaseController;
use App\Services\Clinicas\Paciente\PacienteArquivoService;

class PacienteArquivoController extends BaseController {

    private $pacienteArquivoService;

    public function __construct(PacienteArquivoService $pacArquivoServ) {
        $this->pacienteArquivoService = $pacArquivoServ;
    }

    private function verificaDoiminio() {
        
    }

    public function index(Request $request, $pacienteId) {

        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }
        $result = $this->pacienteArquivoService->getAll($idDominio, $pacienteId, $request);
        return $this->returnResponse($result);
    }

    public function store(Request $request, $pacienteId) {

        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }


        $result = $this->pacienteArquivoService->store($idDominio, $pacienteId, $request);
        return $this->returnResponse($result);
    }

    public function delete(Request $request, $pacienteId, $arquivoId) {

        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }
        
        
        if (empty($arquivoId)) {
            return $this->sendError('Informe o id da arquivo');
        }
        $result = $this->pacienteArquivoService->delete($idDominio, $pacienteId, $arquivoId);

        return $result;
    }

    public function update(Request $request, $pacienteId, $arquivoId) {

        
         $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }
        
        
        
        
        if (empty($arquivoId)) {
            return $this->sendError('Informe o id da arquivo');
        }


        if (!$request->has('title') or empty($request->input('title'))) {
            return $this->sendError('Informe o nome da arquivo');
        }

        $result = $this->pacienteArquivoService->update($idDominio, $pacienteId, $arquivoId, $request->input('title'));

        return $result;
    }

}
