<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use App\Models\Gerenciamento\UsersInternoApiSimdoctor;
use App\Http\Controllers\Controller as BaseController;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Repositories\Clinicas\AdministradorRepository;
use App\Services\Clinicas\AdministradorService;
use App\Repositories\Gerenciamento\DominioRepository;
use App\Repositories\Clinicas\PacienteRepository;

class AuthControllerInterno extends BaseController {

    private $administradorService;
    private $pacienteService;

    public function __construct() {
        $this->middleware('auth:interno_api', ['except' => ['loginInterno', 'register', 'loginPorPerfil', 'esqueciSenha', 'registerPacienteLogin']]);
        $this->administradorService = new AdministradorService();
    }

    public function getAuthPassword() {
        return $this->senha;
    }

    /**
     * Store a new user.
     *
     * @param  Request  $request
     * @return Response
     */
    public function register(Request $request) {
//validate incoming request 
//           var_dump($request);
//        $validate = Validator::make($request->all(), [
//                    'login' => 'required|string|unique:users',
//                    'senha' => 'required|confirmed',
//                        ], [
//                    'login.required' => 'Informe o  e-mail',
//                    'login.unique' => 'Este e-mail já existe',
//        ]);
//
//
//        try {
//            $user = new User;
//            $user->login = $request->input('login');
//            $user->senha = md5($request->input('senha'));
//            $user->password = app('hash')->make($request->input('password'));
//            $user->save();
//
//            return response()->json([
//                        'entity' => 'users',
//                        'action' => 'create',
//                        'result' => 'success'
//                            ], 201);
//        } catch (\Exception $e) {
//
//
//            return response()->json([
//                        'message' => '',
//                        'error' => $validate->errors()
//                            ], 409);
//        }
    }

    /**
     * Get a JWT via given credentials.
     *
     * @param  Request  $request
     * @return Response
     */
    public function loginInterno(Request $request) {


        //validate incoming request 
        $validate = validator($request->input(), [
            'login' => 'required',
            'password' => 'required',
                ], [
            'login.required' => "Informe o login",
            'password.required' => "Informe a senha",
        ]);

        if ($validate->fails()) {

            return response()->json([
                        'success' => false,
                        'data' => $validate->errors()->all(),
                        'message' => $validate->errors()->all()[0]
            ]);
        }

        $credentials = $request->only(['login', 'password']);

        $UserObj = new UsersInternoApiSimdoctor;


        $user = $UserObj::where('login', $request->input('login'))
                ->where('password', hash('sha256', $request->input('password')))
                ->first();



        if ($user != null) {

            if (!$token = Auth::guard('interno_api')->setTTL(7200)->login($user)) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }
        } else {
            return response()->json(['message' => 'Unauthorized'], 401);
        }


        return $this->respondWithToken($token);
    }

    /**
     * Get user details.
     *
     * @param  Request  $request
     * @return Response
     */
    public function me() {

        $AdministradorRepository = new AdministradorRepository;

        $retorno = null;

        $user = auth('interno_api')->user();
//        dd($user);
//     
        $dados = [
            'id' => $user->id,
            'nome' => $user->name,
//            'dout_ver_outros' =>$user->dout_ver_outros,
        ];

        return response()->json($dados);
    }

//
//    public function logout() {
//
//        auth('clinicas')->logout(true);
//
//        return $this->sendSuccess([
//                    'success' => true,
//                    'message' => "Logout realizado com sucesso"
//                        ], "Logout realizado com sucesso");
//    }
//
//    public function alterarSenha(Request $request) {
//
//        app('translator')->setLocale('pt-br');
//
//        $validate = validator($request->input(), [
//            'oldPassword' => ['required'],
//            'newPassword' => ['required', 'min:8', 'max:14', Password::min(8)->mixedCase()->numbers()->symbols(),],
//            'confirmNewPassword' => 'required|same:newPassword',
//                ], [
//            'oldPassword.required' => "Informe a senha atual",
//            'newPassword.required' => "Informe a nova senha",
//            'newPassword.min' => "A nova senha dever ter no mínimo 8 caracteres",
//            'newPassword.mixedCase' => "A nova senha dever ter no mínimo 8 caracteres",
//            'confirmNewPassword.required' => "Confirme a nova senha",
//            'confirmNewPassword.same' => "As novas senhas são diferentes",
//                ], ['validation.min.string']);
//
//
//        if ($validate->fails()) {
//            return $this->sendError(
//                            ['success' => false,
//                                'data' => null,
//                                'message' => $validate->errors()->all()[0]
//            ]);
//        } else {
//
//            $result = $this->administradorService->alterarSenha(auth('clinicas')->user()->identificador, auth('clinicas')->user()->id, $request->input('newPassword'), true, $request->input('oldPassword'));
//
//            return $result;
//        }
//    }
//
//    public function esqueciSenha(Request $request) {
//        app('translator')->setLocale('pt-br');
//
//
//
//        $validate = validator($request->input(), ['email' => 'required|email',
//            'codigo' => 'numeric|digits:6',
//            'password' => ['required_with:codigo', 'min:8', 'max:14', Password::min(8)->mixedCase()->numbers()->symbols(),],
//            'confirmPassword' => 'required_with:password|same:password',
//                ], [
//            'email.required' => 'Informe o e-mail',
//            'email.email' => 'E-mail inválido',
//            'codigo.numeric' => 'O código deve ser numérico',
//            'codigo.digits' => 'O código deve ter 6 digitos',
//            'password.required' => "Informe a nova senha",
//            'password.min' => "A nova senha dever ter no mínimo 8 caracteres",
//            'confirmPassword.required_with' => "Confirme a nova senha",
//            'confirmPassword.same' => "As novas senhas são diferentes",
//                ]
//        );
//
//        if ($validate->fails()) {
//            return $this->sendError(
//                            ['success' => false,
//                                'data' => null,
//                                'message' => $validate->errors()->all()[0]
//            ]);
//        } else {
//
//            $idDominio = ($request->has('perfilId')) ? $request->input('perfilId') : null;
//
//            $DominioRepository = new DominioRepository;
//            $rowDominio = $DominioRepository->getById($idDominio);
//
//            $dominiosDocBiz = [];
//
//            if ($rowDominio->alteracao_docbizz == 1) {
//                $qrDominioDocBiz = $DominioRepository->getDominiosDocBiz();
//                foreach ($qrDominioDocBiz as $chave => $rowDominioDoc) {
//                    $dominiosDocBiz[] = $rowDominioDoc->dominio_id;
//                }
//                $idDominio = $dominiosDocBiz;
//            }
//
//            if ($request->has('codigo') and ! empty($request->input('codigo'))) {
//
//                return $result = $this->administradorService->esqueciSenhaVerificaCodigo($idDominio, $request->input('email'), $request->input('codigo'), $request->input('password'));
//            } else {
//                return $result = $this->administradorService->esqueciSenha($idDominio, $request->input('email'));
//            }
//        }
//    }
//
//    //pacientes
//    public function registerPacienteLogin(Request $request) {
////validate incoming request 
////           var_dump($request);
//
//        $validate = Validator::make($request->all(), [
//                    'nome' => 'required|string|min:3|max:255',
//                    'sobrenome' => 'required|string|min:3|max:255',
//                    'email' => 'required|email',
//                    'senha' => 'required|min:8|max:16',
//                    'perfil_id' => 'required|numeric',
//                        ], [
//        ]);
//
//        $user = new Paciente;
//
//        $verificaExiste = $user->isExistsLogin($request->input('perfil_id'), trim($request->input('email')));
//        if ($verificaExiste) {
//            return response()->json([
//                        'success' => false,
//                        'data' => '',
//                        'message' => 'Este e-mail já está cadastrado',
//            ]);
//        }
//
//
//        try {
//
//
//            $dadosPaciente['nome'] = trim($request->input('nome'));
//            $dadosPaciente['sobrenome'] = trim($request->input('sobrenome'));
//            $dadosPaciente['email'] = trim($request->input('email'));
//            $dadosPaciente['senha'] = trim($request->input('senha'));
//            $dadosPaciente['identificador'] = trim($request->input('perfil_id'));
//            $dadosPaciente['envia_email'] = true;
//
//            $idPaciente = $user->storeLogin($dadosPaciente);
//
//            return response()->json([
//                        'success' => true,
//                        'data' => ['id' => $idPaciente],
//                        'message' => 'Paciente cadastrado com sucesso@',
//                            ], 200);
//        } catch (\Exception $e) {
//
//
//            return response()->json([
//                        'success' => false,
//                        'message' => '',
//                        'data' => $validate->errors()
//                            ], 409);
//        }
//    }
//
//    public function esqueciSenhaPaciente(Request $request) {
//        app('translator')->setLocale('pt-br');
//
//
//        $validate = validator($request->input(), ['email' => 'required|email',
//            'codigo' => 'numeric|digits:6',
//            'password' => ['required_with:codigo', 'min:8', 'max:14', Password::min(8)->mixedCase()->numbers()->symbols(),],
//            'confirmPassword' => 'required_with:password|same:password',
//                ], [
//            'email.required' => 'Informe o e-mail',
//            'email.email' => 'E-mail inválido',
//            'codigo.numeric' => 'O código deve ser numérico',
//            'codigo.digits' => 'O código deve ter 6 digitos',
//            'password.required' => "Informe a nova senha",
//            'password.min' => "A nova senha dever ter no mínimo 8 caracteres",
//            'confirmPassword.required_with' => "Confirme a nova senha",
//            'confirmPassword.same' => "As novas senhas são diferentes",
//                ]
//        );
//
//        if ($validate->fails()) {
//            return $this->sendError(
//                            ['success' => false,
//                                'data' => null,
//                                'message' => $validate->errors()->all()[0]
//            ]);
//        } else {
//
//            $idDominio = ($request->has('perfilId')) ? $request->input('perfilId') : null;
//
//
//
//            if ($request->has('codigo') and ! empty($request->input('codigo'))) {
//                return $result = $this->pacienteService->esqueciSenhaVerificaCodigo($idDominio, $request->input('email'), $request->input('codigo'), $request->input('password'));
//            } else {
//
//                return $result = $this->pacienteService->esqueciSenha($idDominio, $request->input('email'));
//            }
//        }
//    }
//
//    public function alterarSenhaPaciente(Request $request) {
//
//        app('translator')->setLocale('pt-br');
//
//        $validate = validator($request->input(), [
//            'oldPassword' => ['required'],
//            'newPassword' => ['required', 'min:8', 'max:14', Password::min(8)->mixedCase()->numbers()->symbols(),],
//            'confirmNewPassword' => 'required|same:newPassword',
//                ], [
//            'oldPassword.required' => "Informe a senha atual",
//            'newPassword.required' => "Informe a nova senha",
//            'newPassword.min' => "A nova senha dever ter no mínimo 8 caracteres",
//            'newPassword.mixedCase' => "A nova senha dever ter no mínimo 8 caracteres",
//            'confirmNewPassword.required' => "Confirme a nova senha",
//            'confirmNewPassword.same' => "As novas senhas são diferentes",
//                ], ['validation.min.string']);
//
//
//        if ($validate->fails()) {
//            return $this->sendError(
//                            ['success' => false,
//                                'data' => null,
//                                'message' => $validate->errors()->all()[0]
//            ]);
//        } else {
//
//            $result = $this->pacienteService->alterarSenha(auth('clinicas_pacientes')->user()->identificador, auth('clinicas_pacientes')->user()->id, $request->input('newPassword'), true, $request->input('oldPassword'));
//
//            return $result;
//        }
//    }
}
