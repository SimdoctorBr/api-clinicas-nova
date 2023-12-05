<?php

namespace App\Http\Controllers\ApiClinicas\Configuracoes;

use App\Http\Controllers\ApiClinicas\Controller;
use Illuminate\Http\Request;
use App\Services\Clinicas\Configuracoes\DocumentosExigidosService;

class DocumentosExigidosController extends Controller {

    private $documentosExigidosService;

    public function __construct(DocumentosExigidosService $agSer) {
        $this->documentosExigidosService = $agSer;
    }

    public function index(Request $request) {
        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }


        $validate = validator($request->query(),
                [
                    'tipoDoc' => 'numeric'
                ], [
            'tipoDoc.numeric' => 'O tipo de documento deve ser numérico'
        ]);
        $tipoDoc = ($request->has('tipoDoc')) ? $request->query('tipoDoc') : null;

        if ($validate->fails()) {
            return $this->sendErrorValidator($validate->errors()->all());
        } else {

            $result = $this->documentosExigidosService->getAllExibeCadastro($idDominio, $tipoDoc);

            return $result;
        }
    }

}
