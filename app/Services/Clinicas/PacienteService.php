<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\Clinicas;

use App\Services\BaseService;
use DateTime;
use App\Services\Clinicas\CalculosService;
use App\Repositories\Clinicas\Paciente\PacienteRepository;
use App\Repositories\Clinicas\EmpresaRepository;
use App\Repositories\Clinicas\LogomarcaRepository;
use Illuminate\Support\Facades\Mail;
use App\Services\Gerenciamento\DominioService;
use App\Helpers\Functions;
use App\Repositories\Clinicas\ConsultaRepository;
use App\Repositories\Clinicas\UfRepository;
use App\Repositories\Gerenciamento\DominioRepository;
use App\Services\Clinicas\Utils\UploadService;
use App\Services\Clinicas\ConsultaService;

/**
 * Description of Activities
 *
 * @author ander
 */
class PacienteService extends BaseService {

    private $pacienteRepository;

    public function __construct() {
        $this->pacienteRepository = new PacienteRepository;
        $this->consultaRepository = new ConsultaRepository;
    }

    private function generateHashAuthBiometria($idDominio, $pacienteId) {
        $authTokenBio = hash('sha256', time() . $pacienteId);
        $this->pacienteRepository->update($idDominio, $pacienteId, ['auth_token_biometria' => $authTokenBio]);
        return $authTokenBio;
    }

    private function fieldsResponse($row, $nomeDominio) {

//        $sexoArray = array('Masculino' => 'M', 'Feminino' => 'F');

        $data['id'] = $row->id;
        $data['matricula'] = $row->matricula;
        $data['nome'] = $row->nome;
        $data['sobrenome'] = $row->sobrenome;
        $data['email'] = $row->email;
        $data['telefone'] = Functions::limpaTelefone($row->telefone);
        $data['celular'] = Functions::limpaTelefone($row->celular);
        $data['logradouro'] = Functions::correcaoUTF8Decode($row->logradouro);
        $data['estado'] = Functions::correcaoUTF8Decode($row->estado);
        $data['ufSigla'] = (!empty($row->ufSigla)) ? $row->ufSigla : null;
        $data['cidade'] = Functions::correcaoUTF8Decode($row->cidade);
        $data['bairro'] = Functions::correcaoUTF8Decode($row->bairro);
        $data['complemento'] = $row->complemento;
        $data['sexo'] = $row->sexo;
        $data['dataNascimento'] = Functions::dateBrToDB($row->data_nascimento);
        $data['comentarios'] = $row->comentarios;
//        $data['indicado_por'] = $row->indicado_por;
        $data['rg'] = Functions::cpfToNumber($row->rg);
        $data['cpf'] = Functions::cpfToNumber($row->cpf);
//        $data['indicacao_id'] = $row->indicacao_id;
//        $data['profissao'] = utf8_decode($row->profissao);
//        $data['estadoCivil'] = $row->estado_civil;
//        $data['nomeConjuge'] = $row->nome_conjuge;
        $data['cep'] = $row->cep;
        $data['perfilId'] = $row->identificador;
        $data['dataCadastro'] = $row->data_cad_pac;
        $data['dataUltimaAlteracao'] = $row->data_alter_pac;

        $data['enviaSms'] = $row->envia_sms;
        $data['enviaEmail'] = $row->envia_email;
//        $data['imagem_perfil'] = $row->imagem_perfil;
//        $data['telefone2'] = $row->telefone2;
        $data['alerta'] = $row->alerta;
        $data['pacienteFalecido'] = $row->paciente_falecido;
        $data['dataObito'] = $row->data_obito;
//        $data['statusPaciente'] = $row->status_paciente;

        $data['urlFoto'] = null;
        if (isset($row->imagem_perfil) and $row->imagem_perfil == 1
                and isset($row->extensao_imagem) and!empty($row->extensao_imagem)) {

            $data['urlFoto'] = env('APP_URL_CLINICAS') . $nomeDominio . '/fotos_perfil_pacientes/' . $row->id . '.' . $row->extensao_imagem;
        }


        $data['idade'] = (!empty($row->data_nascimento) and Functions::validateDate(Functions::dateBrToDB($row->data_nascimento))) ? (int) Functions::calculaIdade(Functions::dateBrToDB($row->data_nascimento)) : null;

        $data['ultimaConsulta'] = null;
        $rowUltimaConsulta = $this->consultaRepository->getUltimaConsultaPaciente($row->identificador, null, $row->id);
        if ($rowUltimaConsulta) {
            $dadosUltConsulta['data'] = $rowUltimaConsulta->data_consulta;
            $dadosUltConsulta['hora'] = $rowUltimaConsulta->hora_consulta;
            $data['ultimaConsulta'] = $dadosUltConsulta;
        }

        $data['idCustomerAsaas'] = null;
        if (isset($row->idCustomerAsaas)) {
            $data['idCustomerAsaas'] = $row->idCustomerAsaas;
        }
        return $data;
    }

    public function getAll($idDominio, $dadosFiltro = null, $page = 1, $perPage = 100) {

        $DominioService = new DominioService;
        $ConsultaRepository = new ConsultaRepository;
        $PacienteRepository = new PacienteRepository;
        $qr = $PacienteRepository->getAll($idDominio, $dadosFiltro, $page, $perPage);
        $nomesDominio = null;

        if (count($qr['results']) > 0) {
            $retorno = null;

            foreach ($qr['results'] as $row) {

                if (!isset($nomesDominio[$row->identificador])) {
                    $rowDominio = $DominioService->getById($row->identificador);
                    $nomesDominio[$row->identificador] = $rowDominio['data']->dominio;
                }

                $retorno[] = $this->fieldsResponse($row, $nomesDominio[$row->identificador]);
            }

            $qr['results'] = $retorno;

            return $this->returnSuccess($qr);
        } else {
            return $this->returnError(null, 'Sem pacientes Registrados');
        }
    }

    public function getById($idDominio, $pacienteId) {



        $PacienteRepository = new PacienteRepository;
        $qr = $PacienteRepository->getAll($idDominio, ['id' => $pacienteId]);

        if (count($qr) > 0) {
            $DominioService = new DominioService;
            $rowDominio = $DominioService->getById($qr[0]->identificador);
            $nomesDominio = $rowDominio['data']->dominio;

            $qr = $this->fieldsResponse($qr[0], $nomesDominio);
            return $this->returnSuccess($qr);
        } else {

            return $this->returnError(null, 'Paciente não encontrado');
        }
    }

    public function alterarSenha($idDominio, $idPaciente, $novaSenha, $alterSenha = false, $senhaAtual = null) {
        if ($alterSenha) {
            if (empty($senhaAtual)) {
                return $this->returnError(null, "Informe a senha atual");
            }
            $qrVerificaSenha = $this->pacienteRepository->verificaSenha($idDominio, $idPaciente, ($senhaAtual));
            if (!$qrVerificaSenha) {
                return $this->returnError(null, "Senha atual inválida!");
            }
        }



        $dadosInsert['senha'] = ($novaSenha);
        $qr = $this->pacienteRepository->update($idDominio, $idPaciente, $dadosInsert);

        return $this->returnSuccess(null, "Senha alterada com sucesso");
    }

    public function esqueciSenha($idDominio = null, $email) {

        $EmpresaRepository = new EmpresaRepository;
        $LogomarcaRepository = new LogomarcaRepository;

        $rowPaciente = $this->pacienteRepository->buscaPorEmail($idDominio, $email);

        $Links = [];

        if ($rowPaciente) {
            $rowEmpresa = $EmpresaRepository->getById($rowPaciente->identificador);
            $rowEmpresa = $rowEmpresa[0];
            $nome = $rowPaciente->nome;

            $dados['cod_troca_senha'] = substr(mt_rand(), 0, 6);
            $dados['cod_senha_validade'] = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . " +10 minutes"));
            $this->pacienteRepository->update($rowPaciente->identificador, $rowPaciente->id, $dados);

            $Links[] = array(
                'clinica' => $rowEmpresa->nome,
                'codigo' => $dados['cod_troca_senha'],
            );

            $teste = Mail::send('emails.esqueciSenhaPacienteApiMail', ['name' => $nome, 'links' => $Links], function ($message) use ($email, $nome, $rowEmpresa) {
                        $message->to($email, $nome)->subject('Alteração de senha');
                        $message->from('naoresponda@simdoctor.com.br', $rowEmpresa->nome);
                    });

            return $this->returnSuccess(null, 'E-mail enviado com sucesso.');
        } else {
            return $this->returnError(null, 'E-mail não encontrado.');
        }
    }

    public function esqueciSenhaVerificaCodigo($idDominio = null, $email, $codigo, $senha) {

        $qr = $this->pacienteRepository->buscaPorEmail($idDominio, $email, $codigo);

        if ($qr) {
            $dadosUpdate['senha'] = ($senha);
//            $dadosUpdate['cod_troca_senha'] = null;
//            $dadosUpdate['cod_senha_validade'] = null;
            $this->pacienteRepository->update($qr->identificador, $qr->id, $dadosUpdate);

            return $this->returnSuccess([
                        'id' => $qr->id,
                        'nome' => $qr->nome,
                        'email' => $qr->email,
                            ], 'Senha alterada com successo');
        } else {
            return $this->returnError(null, 'Código inválido.');
        }
    }

    public function login($idDominio = null, $email, $senha, $tokenBio = null) {



        if (!empty($tokenBio)) {
            $qrPaciente = $this->pacienteRepository->login($idDominio, $email, $senha, $tokenBio);
        } else {
            $qrPaciente = $this->pacienteRepository->login($idDominio, $email, $senha);
        }






        if ($qrPaciente) {
            $retorno = null;

            foreach ($qrPaciente as $row) {

                $Dominio = new DominioService;
                $rowDominio = $Dominio->getById($row->identificador);
                $rowDominio = $rowDominio['data'];

                $urlFotoPerfil = null;
                if ($row->imagem_perfil == 1) {
                    $urlFotoPerfil = env('APP_URL_CLINICAS') . $rowDominio->dominio . '/fotos_perfil_pacientes/' . $row->id . '.' . $row->extensao_imagem;
                }

                if (!empty($tokenBio)) {
                    $authTokenBio = $this->generateHashAuthBiometria($row->identificador, $row->id);
                } else {
                    $authTokenBio = (empty($row->auth_token_biometria)) ? $this->generateHashAuthBiometria($row->identificador, $row->id) : $row->auth_token_biometria;
                }


                $retorno[] = [
                    'id' => $row->id,
                    'nome' => $row->nome,
                    'sobrenome' => $row->sobrenome,
                    'email' => $row->email,
                    'urlFoto' => $urlFotoPerfil,
                    'perfilId' => $row->identificador,
                    'authTokenBio' => $authTokenBio,
                ];
            }
            return $this->returnSuccess($retorno, '');
        } else {

            if (!empty($tokenBio)) {
                return $this->returnError(null, 'Token de biometria inválido inválidos');
            } else {
                return $this->returnError(null, 'E-mail e/ou senha inválidos');
            }
        }

//        dd($rowPaciente);
    }

    public function googleRegistro($idDominio, $dados) {

        $dadosInsert['nome_cript'] = $dados['nome'];
        $dadosInsert['sobrenome_cript'] = $dados['sobrenome'];
        $dadosInsert['email_cript'] = $dados['email'];
        $dadosInsert['id_google'] = $dados['codigoGoogle'];
        $dadosInsert['foto_google'] = $dados['urlFotoGoogle'];
        $dadosInsert['link_google'] = (isset($dados['link_google']) and!empty($dados['link_google'])) ? $dados['link_google'] : '';

        $rowVerifica = $this->pacienteRepository->loginGoogle($idDominio, $dados['codigoGoogle']);
        if (!$rowVerifica) {
            return $this->returnError('', 'Paciente já registrado');
        } else {
            $id = $this->pacienteRepository->insert($idDominio, $dadosInsert);

            $row = $this->pacienteRepository->getById($idDominio, $id);

            return $this->returnSuccess([
                        'id' => $row[0]->id,
                        'nome' => $row[0]->nome,
                        'sobrenome' => $row[0]->sobrenome,
                        'email' => $row[0]->email,
                        'perfilId' => $row[0]->identificador,
                        'urlFotoGoogle' => $row[0]->foto_google,
            ]);
        }
    }

    public function googleLogin($idDominio, $codigo) {


        $row = $this->pacienteRepository->loginGoogle($idDominio, $codigo);

        if ($row) {
            return $this->returnSuccess([
                        'id' => $row->id,
                        'nome' => $row->nome,
                        'sobrenome' => $row->sobrenome,
                        'email' => $row->email,
                        'perfilId' => $row->identificador,
                        'urlFotoGoogle' => $row->foto_google,
            ]);
        } else {
            return $this->returnError('', 'Paciente não registrado');
        }
    }

    public function facebookRegistro($idDominio, $dados) {

        $dadosInsert['nome_cript'] = $dados['nome'];
        $dadosInsert['sobrenome_cript'] = $dados['sobrenome'];
        $dadosInsert['email_cript'] = $dados['email'];
        $dadosInsert['id_facebook'] = $dados['codigoFacebook'];
        $dadosInsert['foto_facebook'] = $dados['urlFotoFacebook'];
        $dadosInsert['link_facebook'] = (isset($dados['link_facebook']) and!empty($dados['link_facebook'])) ? $dados['link_facebook'] : '';

        $rowVerifica = $this->pacienteRepository->loginFacebook($idDominio, $dados['codigoFacebook']);

        if ($rowVerifica) {
            return $this->returnError('', 'Paciente já registrado');
        } else {
            $id = $this->pacienteRepository->insert($idDominio, $dadosInsert);

            $row = $this->pacienteRepository->getById($idDominio, $id);

            return $this->returnSuccess([
                        'id' => $row[0]->id,
                        'nome' => $row[0]->nome,
                        'sobrenome' => $row[0]->sobrenome,
                        'email' => $row[0]->email,
                        'perfilId' => $row[0]->identificador,
                        'urlFotoFacebook' => $row[0]->foto_facebook,
            ]);
        }
    }

    public function facebookLogin($idDominio, $codigo) {


        $row = $this->pacienteRepository->loginFacebook($idDominio, $codigo);

        if ($row) {
            return $this->returnSuccess([
                        'id' => $row->id,
                        'nome' => $row->nome,
                        'sobrenome' => $row->sobrenome,
                        'email' => $row->email,
                        'perfilId' => $row->identificador,
                        'urlFotoFacebook' => $row->foto_facebook,
            ]);
        } else {
            return $this->returnError('', 'Paciente não registrado');
        }
    }

    public function atualizarPaciente($idDominio, $pacienteId, $dadosInput) {

        $DominioService = new DominioService;
        $rowDominio = $DominioService->getById($idDominio);
        $nomesDominio = $rowDominio['data']->dominio;

        $rowPaciente = $this->pacienteRepository->getById($idDominio, $pacienteId);
        if (!$rowPaciente) {
            return $this->returnError('Paciente não encontrado');
        }
        $arrayMapTabelaPacientes = [
            'nome' => 'nome_cript',
            'sobrenome' => 'sobrenome_cript',
            'email' => 'email_cript',
            'nome_social' => 'nomeSocial',
            'telefone' => 'telefone_cript',
            'telefone2' => 'telefone2_cript',
            'celular' => 'celular_cript',
            'uf' => 'pac_uf_id',
            'cep' => 'cep',
            'cidade' => 'cidade',
            'estado' => 'estado',
            'bairro' => 'bairro',
            'logradouro' => 'logradouro',
            'complemento' => 'complemento',
            'sexo' => 'sexo',
            'dataNascimento' => 'data_nascimento',
            'cpf' => 'cpf_cript',
            'rg' => 'rg_cript',
            'envia_sms' => 'envia_sms',
            'envia_email' => 'envia_email',
            'alerta' => 'alerta',
            'observacoes' => 'comentarios',
            'filiacaoPai' => 'filiacao_pai',
            'filiacaoMae' => 'filiacao_mae',
        ];

        $dadosInsert = null;
        foreach ($dadosInput as $nomeCampo => $valor) {

            if (isset($arrayMapTabelaPacientes[$nomeCampo])) {

                if ($nomeCampo == 'sexo' and!empty($valor) and $valor != 'Masculino' and $valor != 'Feminino') {
                    return $this->returnError('', 'O campo sexo deve ser Masculino ou Feminino');
                }
                if ($nomeCampo == 'envia_sms' and!empty($valor) and $valor != 1 and $valor != 0) {
                    return $this->returnError('', 'O campo envia_sms deve ser 1 ou 0');
                }
                if ($nomeCampo == 'envia_email' and!empty($valor) and $valor != 1 and $valor != 0) {
                    return $this->returnError('', 'O campo envia_email deve ser 1 ou 0');
                }

                if ($nomeCampo == 'dataNascimento') {
                    $valor = Functions::dateDbToBr($valor);
                }
                if ($nomeCampo == 'uf' and!empty($valor)) {
                    $UfRepository = new UfRepository;
                    $dadosUf = $UfRepository->getBySigla($valor);
                    $valor = $dadosUf->cd_uf;
                    $dadosInsert['estado'] = $dadosUf->ds_uf_nome;
                }

                $dadosInsert[$arrayMapTabelaPacientes[$nomeCampo]] = $valor;
            }
        }

        if ($dadosInsert != null) {
            $this->pacienteRepository->update($idDominio, $pacienteId, $dadosInsert);

            $rowPac = $this->pacienteRepository->getById($idDominio, $pacienteId);
            $rowPac = $this->fieldsResponse($rowPac[0], $nomesDominio);
            return $this->returnSuccess($rowPac, 'Atualizado com sucesso');
        }
    }

    public function alterarFotoPerfil($idDominio, $pacienteId, $dadosFile) {




        $DominioRepository = new DominioRepository();
        $rowDominio = $DominioRepository->getById($idDominio);
        $url = env('APP_URL_CLINICAS') . $rowDominio->dominio . '/fotos_perfil_pacientes/';
        $urlThumb = env('APP_URL_CLINICAS') . $rowDominio->dominio . '/fotos_perfil_pacientes/';
        $baseDir = '../../app/perfis/' . $rowDominio->dominio . '/fotos_perfil_pacientes';

        $file = $dadosFile;
        $extensao = $file->getClientOriginalExtension();
        $originalName = $file->getClientOriginalName();

        $nameFile = $pacienteId . "." . $extensao;

//        dd($baseDir.'/'.$nameFile);
//        $imageThumb = "/fotos_perfil_pacientes/" . $nameFile;

        $moveFile = $file->move($baseDir, $nameFile);
        if ($moveFile) {
            $UploadService = new UploadService;
            $UploadService->resizeImage($moveFile->getRealPath(), $baseDir . '/' . $nameFile, 320, 240);

            $DadosFotos['imagem_perfil'] = 1;
            $DadosFotos['extensao_imagem'] = $extensao;

            $idInsert = $this->pacienteRepository->update($idDominio, $pacienteId, $DadosFotos);

            return $this->returnSuccess([
                        'urlFoto' => $url . $nameFile
                            ], ['Alterado com sucesso']);
        } else {
            return $this->returnError(null, ['Ocorreu um erro ao adicionar a foto, por favor tente mais tarde']);
        }
    }

    public function store($idDominio, $dadosInput) {

        $DominioService = new DominioService;
        $rowDominio = $DominioService->getById($idDominio);
        $nomesDominio = $rowDominio['data']->dominio;

        $arrayMapTabelaPacientes = [
            'nome' => 'nome_cript',
            'sobrenome' => 'sobrenome_cript',
            'email' => 'email_cript',
            'nome_social' => 'nomeSocial',
            'telefone' => 'telefone_cript',
            'telefone2' => 'telefone2_cript',
            'celular' => 'celular_cript',
            'uf' => 'pac_uf_id',
            'cidade' => 'cidade',
            'estado' => 'estado',
            'bairro' => 'bairro',
            'logradouro' => 'logradouro',
            'complemento' => 'complemento',
            'sexo' => 'sexo',
            'dataNascimento' => 'data_nascimento',
            'cpf' => 'cpf_cript',
            'rg' => 'rg_cript',
            'envia_sms' => 'envia_sms',
            'envia_email' => 'envia_email',
            'alerta' => 'alerta',
            'senha' => 'senha',
            'observacoes' => 'comentarios',
            'filiacaoPai' => 'filiacao_pai',
            'filiacaoMae' => 'filiacao_mae',
        ];

        $dadosInsert = null;
        foreach ($dadosInput as $nomeCampo => $valor) {

            if (isset($arrayMapTabelaPacientes[$nomeCampo])) {

                if ($nomeCampo == 'sexo' and!empty($valor) and $valor != 'Masculino' and $valor != 'Feminino') {
                    return $this->returnError('', 'O campo sexo deve ser Masculino ou Feminino');
                }
                if ($nomeCampo == 'envia_sms' and!empty($valor) and $valor != 1 and $valor != 0) {
                    return $this->returnError('', 'O campo envia_sms deve ser 1 ou 0');
                }
                if ($nomeCampo == 'envia_email' and!empty($valor) and $valor != 1 and $valor != 0) {
                    return $this->returnError('', 'O campo envia_email deve ser 1 ou 0');
                }

                if ($nomeCampo == 'dataNascimento') {
                    $valor = Functions::dateDbToBr($valor);
                }
                if ($nomeCampo == 'uf') {
                    $UfRepository = new UfRepository;
                    $dadosUf = $UfRepository->getBySigla($valor);
                    $valor = $dadosUf->cd_uf;
                    $dadosInsert['estado'] = $dadosUf->ds_uf_nome;
                }

                $dadosInsert[$arrayMapTabelaPacientes[$nomeCampo]] = $valor;
            }
        }

        $dadosInsert['matricula'] = $this->pacienteRepository->getUltimaMatricula($idDominio);
        if ($dadosInsert != null) {
            $pacienteId = $this->pacienteRepository->insert($idDominio, $dadosInsert);

            $rowPac = $this->pacienteRepository->getById($idDominio, $pacienteId);
            $rowPac = $this->fieldsResponse($rowPac[0], $nomesDominio);
            return $this->returnSuccess($rowPac, 'Cadastrado com sucesso');
        }
    }

    public function proximasConsultas($idDominio, $pacienteId, $request) {

        $ConsultaService = new ConsultaService();

        $dadosQuery['pacienteId'] = $pacienteId;
        $dadosQuery['dataHoraApartirDe'] = date('Y-m-d H:i');
        $dadosQuery['page'] = ($request->has('page') and!empty($request->has('page'))) ? $request->query('page') : 1;
        $dadosQuery['perPage'] = ($request->has('perPage') and!empty($request->has('perPage'))) ? $request->query('perPage') : 50;
        $dadosQuery['orderBy'] = 'dataConsulta.asc,horaCOnsultas.asc';
//        $dadosQuery['data'] = $pacienteId;
        $qrConsultas = $ConsultaService->getAll($idDominio, null, $dadosQuery);

        return $qrConsultas;
    }

    public function getHistoricoConsulta($idDominio, $pacienteId, $request) {

        $ConsultaService = new ConsultaService();

        $dadosQuery['pacienteId'] = $pacienteId;
        $dadosQuery['showProcedimentos'] = true;
        $dadosQuery['dataHoraLimite'] = ($request->has('dataHoraLimite') and!empty($request->has('dataHoraLimite'))) ? $request->query('dataHoraLimite') : date('Y-m-d H:i');
        ;
        $dadosQuery['page'] = ($request->has('page') and!empty($request->has('page'))) ? $request->query('page') : 1;
        $dadosQuery['perPage'] = ($request->has('perPage') and!empty($request->has('perPage'))) ? $request->query('perPage') : 50;
        $dadosQuery['orderBy'] = ($request->has('orderBy') and!empty($request->has('orderBy'))) ? $request->query('orderBy') : 'dataConsulta.desc,horaCOnsultas.desc';
//        $dadosQuery['data'] = $pacienteId;
        $qrConsultas = $ConsultaService->getAll($idDominio, null, $dadosQuery);

        $RecebimentoService = new RecebimentoService;

        foreach ($qrConsultas['data']['results'] as $chave => $rowConsulta) {

            $qrConsultas['data']['results'][$chave]['dadosPagamento'] = null;
            if ($rowConsulta['pago']) {
                $rowRecebimentoPag = $RecebimentoService->getDadosPagamentosEfetuados($idDominio, 'consulta', $rowConsulta['id'], false);

                $qrConsultas['data']['results'][$chave]['dadosPagamento'] = [
                    'valorBruto' => $rowRecebimentoPag[0]['valor_bruto'],
                    'valorDesconto' => $rowRecebimentoPag[0]['valor_desconto'],
                    'valorAcrescimo' => $rowRecebimentoPag[0]['valor_acrescimo'],
                    'valorLiquido' => $rowRecebimentoPag[0]['valor_liquido'],
                ];
            }
        }



        return $qrConsultas;
    }

}
