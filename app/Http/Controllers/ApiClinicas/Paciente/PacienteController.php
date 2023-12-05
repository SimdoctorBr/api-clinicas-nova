<?php

namespace App\Http\Controllers\ApiClinicas\Paciente;

use Illuminate\Http\Request;
use App\Http\Controllers\ApiClinicas\Controller as BaseController;
use App\Services\Clinicas\PacienteService;
use Illuminate\Validation\Rules\Password;
use App\Helpers\Functions;

class PacienteController extends BaseController {

    private $pacienteService;

    public function __construct(PacienteService $pacFotoServ) {
        $this->pacienteService = $pacFotoServ;
    }

//    public function index(Request $request, $pacienteId) {
//
//
//        $result = $this->pacienteService->getAll($this->getIdDominio(), $pacienteId, $request);
//        return $this->returnResponse($result);
//    }

    public function store(Request $request) {


        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

        $validate = validator($request->input(), [
            'nome' => 'sometimes|required|min:3',
            'sobrenome' => 'sometimes|min:3',
            'nomeSocial' => 'min:3',
            'email' => 'sometimes|email',
            'telefone' => 'nullable|numeric',
            'telefone2' => 'nullable|numeric',
            'celular' => 'nullable|numeric',
            'uf' => 'max:2',
            'cpf' => 'nullable|numeric|digits:11',
            'rg' => 'nullable|numeric',
            'envia_sms' => 'nullable|numeric',
            'envia_email' => 'nullable|numeric',
            'cep' => 'nullable|numeric|digits:8',
            'dataNascimento' => 'date',
            'pacIdResponsavel' => 'required_if:isDependente,true',
            'telefoneEmergencia' => 'nullable|numeric'
                ], [
            'nome.required' => 'O nome do paciente não pode ser vazio',
            'nome.min' => 'O nome do paciente deve ter no mínimo 3 letras',
            'nome.alpha' => 'O nome do paciente deve ter somente letras',
            'sobrenome.required' => 'O nome do paciente não pode ser vazio',
            'sobrenome.min' => 'O sobrenome do paciente deve ter no mínimo 3 letras',
            'sobrenome.alpha' => 'O sobrenome do paciente deve ter somente letras',
            'email.email' => 'Email inválido',
            'telefone.numeric' => 'O telefone dever ser numérico',
            'telefone.digits' => 'O telefone deve conter 10 dígitos',
            'telefone2.numeric' => 'O telefone2 dever ser numérico',
            'celular.digits' => 'O celular deve conter 11 dígitos',
            'cpf.digits' => 'O cpf deve conter 11 dígitos',
            'dataNascimento.date' => 'Data de nascimento inválida',
            'senha' => 'min:8|max:16',
            'cep.numeric' => 'O cep deve ser numérico',
            'cep.digits' => 'O cep deve ter 8 dígitos',
            'uf.max' => 'A UF deve ser a sigla do estado',
            'pacIdResponsavel.required_if' => 'Id do paciente responsável não informado no campo \'pacIdResponsavel\'',
            'pacIdResponsavel.numeric' => 'Id do paciente responsável deve ser numérico',
            'telefoneEmergencia.numeric' => 'O telefoneEmergencia dever ser numérico',
        ]);

        if ($validate->fails()) {
            return $this->sendError(
                            ['success' => false,
                                'data' => null,
                                'message' => $validate->errors()->all()[0]
            ]);
        } else {

            $result = $this->pacienteService->store($idDominio, $request->input(), $request->file());
            return $result;
        }
    }

    public function index(Request $request) {


        $getDominio = $this->getIdDominio($request, 'input', false);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }
        $dadosFiltro = null;
        $page = 1;
        $perPage = 100;
        if ($request->has('page') and !empty($request->query("page"))) {
            $page = $request->query("page");
        }
        if ($request->has('perPage') and !empty($request->query("perPage"))) {
            $perPage = $request->query("perPage");
        }
        if ($request->has('search') and !empty($request->query("search"))) {
            $dadosFiltro['search'] = $request->query("search");
        }


        $result = $this->pacienteService->getAll($idDominio, $dadosFiltro, $page, $perPage);

        if ($result['success']) {
            return $this->returnResponse($result);
        } else {
            return $result;
        }
    }

    public function login(Request $request) {


        $getDominio = $this->getIdDominio($request, 'input', false);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

        $validate = validator($request->input(), [
            'email' => 'required_without:authTokenBio|email',
            'senha' => 'required_without:authTokenBio|min:8|max:16',
                ]
        );

        $tokenBio = null;
        if ($request->has('authTokenBio') and !empty($request->input('authTokenBio'))) {
            $tokenBio = $request->input('authTokenBio');
        } else {
            
        }

        if ($validate->fails()) {
            return $this->sendErrorValidator($validate->errors()->all());
        } else {
            $result = $this->pacienteService->login($idDominio, $request->input('email'), $request->input('senha'), $tokenBio);
            return $this->returnResponse($result);
        }
    }

    public function esqueciSenha(Request $request) {

        $getDominio = $this->getIdDominio($request, 'input', false);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

        app('translator')->setLocale('pt-br');

        $validate = validator($request->input(), ['email' => 'required|email',
            'codigo' => 'numeric|digits:6',
            'password' => ['required_with:codigo', 'min:8', 'max:14', Password::min(8)->mixedCase()->numbers()->symbols(),],
            'confirmPassword' => 'required_with:password|same:password',
                ], [
            'email.required' => 'Informe o e-mail',
            'email.email' => 'E-mail inválido',
            'codigo.numeric' => 'O código deve ser numérico',
            'codigo.digits' => 'O código deve ter 6 digitos',
            'password.required' => "Informe a nova senha",
            'password.min' => "A nova senha dever ter no mínimo 8 caracteres",
            'confirmPassword.required_with' => "Confirme a nova senha",
            'confirmPassword.same' => "As novas senhas são diferentes",
                ]
        );

        if ($validate->fails()) {
            return $this->sendErrorValidator($validate->errors()->all());
        } else {

            if ($request->has('codigo') and !empty($request->input('codigo'))) {
                return $result = $this->pacienteService->esqueciSenhaVerificaCodigo($idDominio, $request->input('email'), $request->input('codigo'), $request->input('password'));
            } else {

                return $result = $this->pacienteService->esqueciSenha($idDominio, $request->input('email'));
            }
        }
    }

    public function getById(Request $request, $pacienteId) {


        $getDominio = $this->getIdDominio($request, 'input', false);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

//        $validate = validator($request->query(),
//                ['showDependentes' => 'boolean'],
//                ['showDependentes.boolean' => 'O campo \'showDependentes\' deve ser \'true\' ou \'false\'']);
        if ($request->has('showDependentes') and
                $request->query('showDependentes') != true and
                $request->query('showDependentes') != false
        ) {
            return $this->sendErrorValidator('O campo \'showDependentes\' deve ser \'true\' ou \'false\'');
        }


        return $this->pacienteService->getById($idDominio, $pacienteId, $request->query());
    }

    public function alterarSenha(Request $request, $pacienteId) {


        $getDominio = $this->getIdDominio($request, 'input', false);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }


        app('translator')->setLocale('pt-br');

        $validate = validator($request->input(), [
            'oldPassword' => ['required'],
            'newPassword' => ['required', 'min:8', 'max:14', Password::min(8)->mixedCase()->numbers()->symbols(),],
            'confirmNewPassword' => 'required|same:newPassword',
                ], [
            'oldPassword.required' => "Informe a senha atual",
            'newPassword.required' => "Informe a nova senha",
            'newPassword.min' => "A nova senha dever ter no mínimo 8 caracteres",
            'newPassword.mixedCase' => "A nova senha dever ter no mínimo 8 caracteres",
            'confirmNewPassword.required' => "Confirme a nova senha",
            'confirmNewPassword.same' => "As novas senhas são diferentes",
                ], ['validation.min.string']);

        if ($validate->fails()) {
            return $this->sendError(
                            ['success' => false,
                                'data' => null,
                                'message' => $validate->errors()->all()[0]
            ]);
        } else {

            $result = $this->pacienteService->alterarSenha($idDominio, $pacienteId, $request->input('newPassword'), true, $request->input('oldPassword'));

            return $result;
        }
    }

    public function googleRegistrar(Request $request) {


        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

        $validate = validator($request->input(), [
            'nome' => 'required',
            'sobrenome' => 'required',
            'email' => 'required',
            'codigoGoogle' => 'required',
            'urlFotoGoogle' => 'required',
            'perfilId' => 'required',
                ], [
            'nome.required' => 'Informe o nome do paciente',
            'sobrenome.required' => 'Informe o sobrenome do paciente',
            'email.required' => 'Informe o email do paciente',
            'codigoGoogle.required' => 'Informe o código do Google',
            'urlFotoGoogle.required' => 'Informe a url da foto no Google',
            'perfilId.required' => 'Informe o id  do perfil',
        ]);

        if ($validate->fails()) {
            return $this->sendError(
                            ['success' => false,
                                'data' => null,
                                'message' => $validate->errors()->all()[0]
            ]);
        } else {

            $result = $this->pacienteService->googleRegistro($idDominio, $request->input());
            return $result;
        }
    }

    public function googleLogin(Request $request) {

        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

        $validate = validator($request->input(), [
            'codigoGoogle' => 'required',
            'perfilId' => 'required',
                ], [
            'codigoGoogle.required' => 'Informe o código do Google',
            'perfilId.required' => 'Informe o id  do perfil',
        ]);

        if ($validate->fails()) {
            return $this->sendError(
                            ['success' => false,
                                'data' => null,
                                'message' => $validate->errors()->all()[0]
            ]);
        } else {

            $result = $this->pacienteService->googleLogin($idDominio, $request->input('codigoGoogle'));

            return $result;
        }
    }

    public function facebookRegistrar(Request $request) {


        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

        $validate = validator($request->input(), [
            'nome' => 'required',
            'sobrenome' => 'required',
            'email' => 'required',
            'codigoFacebook' => 'required',
            'urlFotoFacebook' => 'required',
            'perfilId' => 'required',
                ], [
            'nome.required' => 'Informe o nome do paciente',
            'sobrenome.required' => 'Informe o sobrenome do paciente',
            'email.required' => 'Informe o email do paciente',
            'codigoFacebook.required' => 'Informe o código do Facebook',
            'urlFotoFacebook.required' => 'Informe a url da foto no Facebook',
            'perfilId.required' => 'Informe o id  do perfil',
        ]);

        if ($validate->fails()) {
            return $this->sendError(
                            ['success' => false,
                                'data' => null,
                                'message' => $validate->errors()->all()[0]
            ]);
        } else {

            $result = $this->pacienteService->facebookRegistro($idDominio, $request->input());

            return $result;
        }
    }

    public function facebookLogin(Request $request) {


        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

        $validate = validator($request->input(), [
            'codigoFacebook' => 'required',
            'perfilId' => 'required',
                ], [
            'codigoFacebook.required' => 'Informe o código do Facebook',
            'perfilId.required' => 'Informe o id  do perfil',
        ]);

        if ($validate->fails()) {
            return $this->sendError(
                            ['success' => false,
                                'data' => null,
                                'message' => $validate->errors()->all()[0]
            ]);
        } else {

            $result = $this->pacienteService->facebookLogin($idDominio, $request->input('codigoFacebook'));

            return $result;
        }
    }

//    public function delete(Request $request, $pacienteId, $fotoId) {
//
//        if (empty($fotoId)) {
//            return $this->sendError('Informe o id da foto');
//        }
//
//
//
//        $result = $this->pacienteService->delete($this->getIdDominio(), $pacienteId, $fotoId);
//
//        return $result;
//    }
//
//    public function update(Request $request, $pacienteId, $fotoId) {
//
//        if (empty($fotoId)) {
//            return $this->sendError('Informe o id da foto');
//        }
//
//
//        if (!$request->has('title') or empty($request->input('title'))) {
//            return $this->sendError('Informe o nome da foto');
//        }
//
//        $result = $this->pacienteService->update($this->getIdDominio(), $pacienteId, $fotoId, $request->input('title'));
//
//        return $result;
//    }




    public function update(Request $request, $pacienteId) {

        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

        $inputValidate = Functions::trimInputArray($request->input());
        $validate = validator($inputValidate, [
            'nome' => 'sometimes|required|min:3',
            'sobrenome' => 'sometimes|min:3',
            'nomeSocial' => 'min:3',
            'email' => 'sometimes|email',
            'telefone' => 'nullable|numeric',
            'telefone2' => 'nullable|numeric',
            'celular' => 'nullable|numeric',
            'uf' => 'max:2',
            'cpf' => 'nullable|numeric|digits:11',
//            'rg' => 'nullable|numeric',
            'envia_sms' => 'nullable|numeric',
            'envia_email' => 'nullable|numeric',
            'cep' => 'nullable|numeric|digits:8',
            'dataNascimento' => 'date',
            'telefoneEmergencia' => 'nullable|numeric'
                ], [
            'nome.required' => 'O nome do paciente não pode ser vazio',
            'nome.min' => 'O nome do paciente deve ter no mínimo 3 letras',
            'nome.alpha' => 'O nome do paciente deve ter somente letras',
            'sobrenome.required' => 'O nome do paciente não pode ser vazio',
            'sobrenome.min' => 'O sobrenome do paciente deve ter no mínimo 3 letras',
            'sobrenome.alpha' => 'O sobrenome do paciente deve ter somente letras',
            'email.email' => 'Email inválido',
            'telefone.numeric' => 'O telefone dever ser numérico',
            'telefone.digits' => 'O telefone deve conter 10 dígitos',
            'telefone2.numeric' => 'O telefone2 dever ser numérico',
            'celular.digits' => 'O celular deve conter 10 dígitos',
            'cpf.digits' => 'O cpf deve conter 11 dígitos',
            'cep.digits' => 'O cpf deve conter 8 dígitos',
            'dataNascimento.date' => 'Data de nascimento inválida',
            'uf.max' => 'A UF deve ser a sigla do estado',
            'telefoneEmergencia.numeric' => 'O telefoneEmergencia dever ser numérico',
        ]);

        if ($validate->fails()) {
            return $this->sendError(
                            ['success' => false,
                                'data' => null,
                                'message' => $validate->errors()->all()[0]
            ]);
        } else {



            $result = $this->pacienteService->atualizarPaciente($idDominio, $pacienteId, $inputValidate, $request->file());
            return $result;
        }
    }

    public function alteraFotoPaciente(Request $request, $pacienteId) {

        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

        $validateFile = validator($request->file(), [
            'foto' => 'required|file|image',
                ], [
            'foto.required' => 'Foto não enviada',
            'foto.image' => 'Formatos suportados: jpg,gif,bmp,jpeg,png',
            'foto.size' => 'O tamanho máximo é de 24MB',
        ]);

        if ($validateFile->fails()) {
            return $this->sendError(['success' => false,
                        'data' => null,
                        'message' => $validateFile->errors()->all()[0]
            ]);
        } else if ($request->file('foto')->getSize() > 24000000) {
            $this->sendError(
                    ['success' => false,
                        'data' => null,
                        'message' => 'O tamanho máximo é de 24MB'
            ]);
        } else {

            $result = $this->pacienteService->alterarFotoPerfil($idDominio, $pacienteId, $request->file('foto'));
            return $result;
        }
    }

    public function proximasConsultas(Request $request, $pacienteId) {

        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

        $validate = validator($request->query(), [
            'page' => 'numeric',
            'perPage' => 'numeric',
                ], [
            'page.numeric' => 'O campo \'page\' deve ser numérico',
            'perPage.numeric' => 'O campo \'page\' deve ser numérico'
        ]);

        if ($validate->fails()) {
            return response()->json(
                            ['success' => false,
                                'data' => null,
                                'message' => $validate->errors()->all()[0]
            ]);
        } else {
            $result = $this->pacienteService->proximasConsultas($idDominio, $pacienteId, $request);
            return $result;
        }
    }

    public function getHistoricoConsulta(Request $request, $pacienteId) {

        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

        $validate = validator($request->query(), [
            'dataHoraLimite' => 'date_format:Y-m-d H:i:s',
            'page' => 'numeric',
            'perPage' => 'numeric',
                ], [
            'dataHoraLimite.date_format' => 'A data deve estar no formato Y-m-d H:i:s',
            'page.numeric' => 'O campo \'page\' deve ser numérico',
            'perPage.numeric' => 'O campo \'page\' deve ser numérico'
        ]);

        if ($validate->fails()) {
            return response()->json(
                            ['success' => false,
                                'data' => null,
                                'message' => $validate->errors()->all()[0]
            ]);
        } else {
            $result = $this->pacienteService->getHistoricoConsulta($idDominio, $pacienteId, $request);
            return $result;
        }
    }

    public function getDependentes(Request $request, $pacienteId) {

        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

        $result = $this->pacienteService->getDependentes($idDominio, $pacienteId, $request->query());
        return $result;
    }

    public function enviarCodigo(Request $request, $pacienteId) {

        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }

        $result = $this->pacienteService->enviarCodigo($idDominio, $pacienteId, $request->query());
        return $result;
    }

    public function verificarCodigo(Request $request, $pacienteId) {

        $getDominio = $this->getIdDominio($request, 'input', true);
        if ($getDominio['success']) {
            $idDominio = $getDominio['perfisId'];
        } else {
            return response()->json($getDominio);
        }
        $validate = validator($request->input(), [
            'codigoConfirmacao' => 'required|numeric',
            'tipo' => 'required',
                ],
                [
                    'tipo.required' => 'Informe o tipo',
                    'codigoConfirmacao.required' => 'Informe o código',
                    'codigoConfirmacao.numeric' => 'O código deve ser numérico',
        ]);

        if ($validate->fails()) {
            return response()->json(
                            ['success' => false,
                                'data' => null,
                                'message' => $validate->errors()->all()[0]
            ]);
        } else {
            $result = $this->pacienteService->verificarCodigo($idDominio, $pacienteId, $request->input());
            return $result;
        }
    }
}
