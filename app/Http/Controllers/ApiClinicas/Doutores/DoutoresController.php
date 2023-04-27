<?php

namespace App\Http\Controllers\ApiClinicas\Doutores;

use Illuminate\Http\Request;
use App\Http\Controllers\ApiClinicas\Controller as BaseController;
use App\Services\Clinicas\Doutores\DoutoresService;

class DoutoresController extends BaseController {

    private $doutoresService;

    public function __construct(DoutoresService $pacArquivoServ) {
        $this->doutoresService = $pacArquivoServ;
    }

    public function index(Request $request) {

        $getDominio = $this->getIdDominio($request, 'input', false);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }



        $dadosFiltro = null;

//        $validate = $this->validate($request->query(),
//                [
//                    'nome'=>'alpha',
//                    'valorConsulta'=>'numeric',
//                    'valorConsultaMax'=>'numeric',
//                ],[
//                   'nome.alpha'=> 'O campo nome deve conter somente letras',
//                   'nome.valorConsulta'=> 'O valorConsulta dever numérico',
//                   'nome.valorConsultaMax'=> 'O valorConsultaMax dever numérico',
//                    
//                ]);
//        
        if ($request->has('sexo')) {
            if (is_array($request->query('sexo'))) {

                foreach ($request->query('sexo') as $chave => $sexoR) {


                    if (!empty($sexoR) and ( $sexoR != 'M' and $sexoR != 'F' and $sexoR != 'O')) {

                        return $this->sendErrorValidator('Tipo de sexo inválido. Valor: ' . $sexoR);
                    }
                }
            } elseif ($request->has('sexo') and!empty($request->query('sexo')) and ( $request->query('sexo') != 'M' and $request->query('sexo') != 'F' and $request->query('sexo') != 'O')) {
                return $this->sendErrorValidator('Tipo de sexo inválido');
            }
        }


        if ($request->has('tipoAtendimento') and!empty($request->query('tipoAtendimento')) and (
                $request->query('tipoAtendimento') != 'presencial' and $request->query('tipoAtendimento') != 'video' and $request->query('tipoAtendimento') != 'presencial,video')) {
            return $this->sendErrorValidator('Tipo de atendimento inválido');
        }

        if ($request->has('grupoAtendimentoId')) {
            foreach ($request->query('grupoAtendimentoId') as $chave => $idGrupAtend) {
                if (!empty($idGrupAtend) and!is_numeric($idGrupAtend)) {
                    return $this->sendErrorValidator('Grupo de atendimento [' . $chave . '] inválido');
                }
            }
        }

        if ($request->has('idiomaId')) {
            foreach ($request->query('idiomaId') as $chave => $idGrupAtend) {
                if (!empty($idGrupAtend) and!is_numeric($idGrupAtend)) {
                    return $this->sendErrorValidator('Id do idioma [' . $chave . '] inválido');
                }
            }
        }

        $page = 1;
        $perPage = 100;
        if ($request->has('page') and!empty($request->query("page"))) {
            $page = $request->query("page");
        }
        if ($request->has('perPage') and!empty($request->query("perPage"))) {
            $perPage = $request->query("perPage");
        }

        $result = $this->doutoresService->getAll($idDominio, $request->query(), $page, $perPage);
        return $result;
    }

    public function store(Request $request, $pacienteId) {

        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }


        $result = $this->doutoresService->store($idDominio, $pacienteId, $request);
        return $this->returnResponse($result);
    }

    public function delete(Request $request, $pacienteId) {

        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }
        $result = $this->doutoresService->delete($idDominio, $pacienteId, $arquivoId);

        return $result;
    }

    public function update(Request $request, $pacienteId, $arquivoId) {

        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }
        $result = $this->doutoresService->update($idDominio, $pacienteId, $arquivoId, $request->input('title'));
        return $result;
    }

    public function storeAvaliacoes(Request $request, $doutorId) {

        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }


        $validation = validator($request->input(), ['pacienteId' => 'required|numeric',
            'pontuacao' => 'required|numeric|min:0|max:5',
                ], [
            'pacienteId.required' => 'Infome o ID do paciente',
            'pacienteId.numeric' => 'O id do paciente deve ser numérico',
            'pontuacao.required' => 'Infome a pontuação',
            'pontuacao.numeric' => 'A pontuação deve ser numérico',
            'pontuacao.min' => 'A pontuação deve ser no mínimo 0 e no máximo 5',
            'pontuacao.max' => 'A pontuação deve ser no mínimo 0 e no máximo 5',
                ]
        );

        if ($validation->fails()) {
            return $this->sendErrorValidator($validation->errors()->all());
        } else {

            $result = $this->doutoresService->storeAvaliacoes($idDominio, $doutorId, $request->input('pacienteId'), $request->input('pontuacao'));
        }



        return $result;
    }

    public function filtros(Request $request) {

        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

        $dadosFiltro = null;
        if ($request->has('withDoctors') and $request->query('withDoctors') == 'true') {
            $dadosFiltro['withDoctors'] = true;
        }
        if ($request->has('tipoAtendimento') and!empty($request->query('tipoAtendimento'))) {
            $dadosFiltro['tipoAtendimento'] = $request->query('tipoAtendimento');
        }
        if ($request->has('sexo') and!empty($request->query('sexo'))) {
            $dadosFiltro['sexo'] = $request->query('sexo');
        }
        if ($request->has('nomeFormacao') and!empty($request->query('nomeFormacao'))) {
            $dadosFiltro['nomeFormacao'] = $request->query('nomeFormacao');
        }
        if ($request->has('especialidade') and!empty($request->query('especialidade'))) {
            $dadosFiltro['especialidade'] = $request->query('especialidade');
        }

        if ($request->has('grupoAtendimentoId') and!empty($request->query('grupoAtendimentoId'))) {
            $dadosFiltro['grupoAtendimentoId'] = $request->query('grupoAtendimentoId');
        }
        if ($request->has('valorConsulta') and!empty($request->query('valorConsulta'))) {
            $dadosFiltro['valorConsulta'] = $request->query('valorConsulta');
        }
        if ($request->has('valorConsultaMax') and!empty($request->query('valorConsultaMax'))) {
            $dadosFiltro['valorConsultaMax'] = $request->query('valorConsultaMax');
        }


        $result = $this->doutoresService->getfiltros($idDominio, $dadosFiltro);
        return $result;
    }

    public function getConveniosDoutores(Request $request, $doutorId) {

        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

        $dadosFiltro = null;
        if ($request->has('somente_com_procedimento') and $request->query('somente_com_procedimento') == true) {
            $dadosFiltro['somente_com_procedimento'] = 1;
        }


//        if ($validation->fails()) {
//            return $this->sendErrorValidator($validation->errors()->all());
//        } else {

        $result = $this->doutoresService->getConveniosDoutores($idDominio, $doutorId, $dadosFiltro);
//        }



        return $result;
    }

    public function getById(Request $request, $doutorId) {

        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }


//        if ($validation->fails()) {
//            return $this->sendErrorValidator($validation->errors()->all());
//        } else {

        $result = $this->doutoresService->getById($idDominio, $doutorId);
//        }



        return $result;
    }

}
