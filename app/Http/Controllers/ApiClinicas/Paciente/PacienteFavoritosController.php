<?php

namespace App\Http\Controllers\ApiClinicas\Paciente;

use Illuminate\Http\Request;
use App\Http\Controllers\ApiClinicas\Controller as BaseController;
use App\Services\Clinicas\Paciente\PacienteFavoritosService;

class PacienteFavoritosController extends BaseController {

    private $pacienteFavoritosService;

    public function __construct(PacienteFavoritosService $pacFotoServ) {
        $this->pacienteFavoritosService = $pacFotoServ;
    }

    public function index(Request $request, $pacienteId) {

        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

        $result = $this->pacienteFavoritosService->getAll($idDominio, $pacienteId);
        return $result;
    }

    public function store(Request $request, $pacienteId) {

        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }


        $validate = validator($request->input(), ['idDoutor' => 'required|numeric'], [
            'idDoutor.required' => 'Informe o id do doutor',
            'idDoutor.numeric' => 'O id deve ser numérico',
                ]
        );

        if ($validate->fails()) {
            return $this->sendErrorValidator($validate->errors()->all());
        } else {
            $result = $this->pacienteFavoritosService->store($idDominio, $pacienteId, $request->input());
            return $result;
        }
    }

    public function delete(Request $request, $pacienteId, $idFavorito) {


        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

        if (empty($idFavorito)) {
            return $this->sendError('Informe o id do(a) doutor(a) favorito(a)');
        }
        $result = $this->pacienteFavoritosService->delete($idDominio, $pacienteId, $idFavorito);

        return $result;
    }

}
