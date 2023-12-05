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
use App\Repositories\Clinicas\Paciente\PacienteDependentesRepository;
use App\Repositories\Clinicas\DefinicaoMarcacaoGlobalRepository;
use App\Repositories\Clinicas\PlanoAprovacoesRepository;
use App\Services\Clinicas\Paciente\PacienteDependenteService;
use App\Repositories\Clinicas\Configuracoes\DocumentosExigidosRepository;
use App\Services\Clinicas\PlanoAprovacoesService;
use App\Services\Clinicas\CodigoConfirmacaoService;
use App\Services\Clinicas\Administracao\LgpdAutorizacoesService;
use App\Repositories\Clinicas\Administracao\LgpdAutorizacoesRepository;
use App\Repositories\Clinicas\Paciente\PacienteQrCodeRepository;

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

    private function getNomeStatusDocVerificacao($idDocVerificacao) {
        switch ($idDocVerificacao) {
            case 0: return 'Não verificado';
                break;
            case 1: return 'Em análise';
                break;
            case 2: return 'Verificado';
                break;
            case 3: return 'Reprovado';
                break;
        }
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
        $data['nomeEmergencia'] = $row->emergencia_nome;
        $data['telefoneEmergencia'] = $row->emergencia_telefone;
//        $data['statusPaciente'] = $row->status_paciente;

        $data['urlFoto'] = null;
        if (isset($row->imagem_perfil) and $row->imagem_perfil == 1
                and isset($row->extensao_imagem) and !empty($row->extensao_imagem)) {

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
        if (isset($row->doc_verificados)) {
            $data['docVerificado'] = [
                'status' => $row->doc_verificados,
                'nomeStatus' => $this->getNomeStatusDocVerificacao($row->doc_verificados),
                'dataVerificacao' => $row->doc_verificados_data,
//                'doc_verificados_motivo' => $row->doc_verificados_data,
            ];
        }



        return $data;
    }

    private function mapFieldsToDB() {
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
            'nomeEmergencia' => 'emergencia_nome',
            'telefoneEmergencia' => 'emergencia_telefone',
        ];
        return $arrayMapTabelaPacientes;
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

    public function getById($idDominio, $pacienteId, $params = null) {



        $PacienteRepository = new PacienteRepository;
        $qr = $PacienteRepository->getAll($idDominio, ['id' => $pacienteId]);

        if (count($qr) > 0) {
            $DominioService = new DominioService;
            $rowDominio = $DominioService->getById($qr[0]->identificador);
            $nomesDominio = $rowDominio['data']->dominio;
            $idDominio = $qr[0]->identificador;
            $qr = $this->fieldsResponse($qr[0], $nomesDominio);

            if (isset($params['showDependentes']) and $params['showDependentes'] == true) {


                $DefinicaoMarcacaoGlobalRepository = new DefinicaoMarcacaoGlobalRepository;
                $rowDef = $DefinicaoMarcacaoGlobalRepository->getDadosDefinicao($idDominio, ['habilita_doc_dependentes']);

                $dependentes = null;
                $PacienteDependentesService = new PacienteDependenteService;
                $qrDependente = $PacienteDependentesService->getByPaciente($idDominio, $pacienteId);
                //  var_dump($qrDependente);

                $qr['dependentes'] = $dependentes;

                $dependentesAprov = null;
                if ($rowDef->habilita_doc_dependentes == 1) {
                    $PlanoAprovacoesRepository = new PlanoAprovacoesRepository;
                    $qrAprovacoes = $PlanoAprovacoesRepository->getAprovacoesDependentesByPacienteId($idDominio, $pacienteId, 1);
                    foreach ($qrAprovacoes as $rowAprov) {

                        $dependentesAprov[] = [
                            'id' => $rowAprov->pacientes_dep_id_assoc,
                            'pacienteDepId' => $rowAprov->pacientes_dep_id,
                            'nomeDependente' => $rowAprov->nomeDependente,
                            'sobrenomeDependente' => $rowAprov->sobrenomeDependente,
                            'cpfDependente' => $rowAprov->cpfDependente,
                            'data_nascimento' => Functions::dateBrToDB($rowAprov->data_nascimento),
                            'filiacao' => $rowAprov->filiacao,
                            'status' => $rowAprov->status,
                            'tipo' => $rowAprov->tipo,
                            'data_cad' => $rowAprov->data_cad,
                            'doc_exigidos_ids_hist' => $rowAprov->doc_exigidos_ids_hist,
                        ];
                    }
//                    var_dump($qrAprovacoes);
                }

                $qr['dependentesAprovacoes'] = $dependentes;
            }


            if (isset($params['exibeAutorizacoes']) and $params['exibeAutorizacoes'] == true) {
                $LgpdAutorizacoesRepository = new LgpdAutorizacoesRepository;

                $verificaTermoSimdoctor = $LgpdAutorizacoesRepository->verificaTermoCondicoes($idDominio, 1, $pacienteId);
                $verificaTermoSimdoctor = ($verificaTermoSimdoctor and $verificaTermoSimdoctor->status_autorizacao == 1) ? true : false;

                $verificaTermoClinica = $LgpdAutorizacoesRepository->verificaTermoCondicoes($idDominio, 2, $pacienteId);
                $verificaTermoClinica = ($verificaTermoClinica and $verificaTermoClinica->status_autorizacao == 1) ? true : false;
                $qr['autorizacoes'] = [
                    'termoSimdoctor' => $verificaTermoSimdoctor,
                    'termoClinica' => $verificaTermoClinica,
                ];
            }


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
                    'telefone' => $row->telefone,
                    'cpf' => Functions::cpfToNumber($row->cpf),
                    'celular' => $row->celular,
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
        $dadosInsert['link_google'] = (isset($dados['link_google']) and !empty($dados['link_google'])) ? $dados['link_google'] : '';

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
        $dadosInsert['link_facebook'] = (isset($dados['link_facebook']) and !empty($dados['link_facebook'])) ? $dados['link_facebook'] : '';

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

    public function atualizarPaciente($idDominio, $pacienteId, $dadosInput, $dadosFiles) {

        $DominioService = new DominioService;
        $rowDominio = $DominioService->getById($idDominio);
        $nomesDominio = $rowDominio['data']->dominio;

        $rowPaciente = $this->pacienteRepository->getById($idDominio, $pacienteId);
        if (!$rowPaciente) {
            return $this->returnError('Paciente não encontrado');
        }
        $rowPaciente = $rowPaciente[0];

        $DefinicaoMarcacaoGlobalRepository = new DefinicaoMarcacaoGlobalRepository();
        $rowDEfGlobal = $DefinicaoMarcacaoGlobalRepository->getDadosDefinicao($idDominio, ['habilita_doc_dependentes', 'hbt_aprov_dependente']);

        $arrayMapTabelaPacientes = $this->mapFieldsToDB();

        $dadosInsert = null;
        foreach ($dadosInput as $nomeCampo => $valor) {

            if (isset($arrayMapTabelaPacientes[$nomeCampo])) {

                if ($nomeCampo == 'sexo' and !empty($valor) and $valor != 'Masculino' and $valor != 'Feminino') {
                    return $this->returnError('', 'O campo sexo deve ser Masculino ou Feminino');
                }
                if ($nomeCampo == 'envia_sms' and !empty($valor) and $valor != 1 and $valor != 0) {
                    return $this->returnError('', 'O campo envia_sms deve ser 1 ou 0');
                }
                if ($nomeCampo == 'envia_email' and !empty($valor) and $valor != 1 and $valor != 0) {
                    return $this->returnError('', 'O campo envia_email deve ser 1 ou 0');
                }

                if ($nomeCampo == 'dataNascimento') {
                    $valor = Functions::dateDbToBr($valor);
                }
                if ($nomeCampo == 'uf' and !empty($valor)) {
                    $UfRepository = new UfRepository;
                    $dadosUf = $UfRepository->getBySigla($valor);
                    $valor = $dadosUf->cd_uf;
                    $dadosInsert['estado'] = $dadosUf->ds_uf_nome;
                }

                $dadosInsert[$arrayMapTabelaPacientes[$nomeCampo]] = $valor;
            }
        }
        ///documentos do pacientes
        $DocumentosExigidosRepository = new DocumentosExigidosRepository();
        $rowDocExigidosPacientes = $DocumentosExigidosRepository->getAllExibeCadastro($idDominio, 2);
        if (isset($dadosInput['pacienteDoc'])) {
            $verificaDoc = $this->verificaDocumentosExigidosDependentes($idDominio, $dadosInput, $dadosFiles, $rowDocExigidosPacientes, 2);
            if (!$verificaDoc['success']) {
                return $verificaDoc;
            }
        }

        if ($dadosInsert != null) {


            if ($rowDEfGlobal->hbt_aprov_dependente == 1 && $rowDEfGlobal->habilita_doc_dependentes == 1 && ($rowPaciente->doc_verificados == 0 or $rowPaciente->doc_verificados == 3)) {
                //Documentos do paciente
                if (isset($dadosInput['pacienteDoc']) and count($dadosInput['pacienteDoc']) > 0) {

                    $idsDocsPac = [];
                    if ($rowDocExigidosPacientes) {
                        $idsDocsPac = implode(', ', array_map(function ($item) {
                                    return $item->id;
                                }, $rowDocExigidosPacientes));
                    }


                    $PlanoAprovacoesService = new PlanoAprovacoesService;
                    $PlanoAprovacoesService->setIdentificador($idDominio);
                    $PlanoAprovacoesService->setPacientes_id($rowPaciente->id);
                    $PlanoAprovacoesService->setTipo(3);
                    $PlanoAprovacoesService->setNome($rowPaciente->nome);
                    $PlanoAprovacoesService->setSobrenome($rowPaciente->sobrenome);
                    $PlanoAprovacoesService->setDoc_exigidos_ids_hist($idsDocsPac);
                    $idPlAprov = $PlanoAprovacoesService->insert($idDominio);
                    $dirDocFiles = $PlanoAprovacoesService->getPathDoc($nomesDominio) . '/' . $idPlAprov;

//                    if (!isset($dadosInput['isDependente']) or $dadosInput['isDependente'] != true) {
//                        $rowPac = $this->pacienteRepository->getById($idDominio, $rowPacPrincipal->id);
//                        $rowPacPrincipal = $rowPac[0];
//                        $rowPac = $this->fieldsResponse($rowPac[0], $nomesDominio);
//                        $dadosRetorno['data'] = $rowPac;
//                    }

                    foreach ($dadosFiles['pacienteDoc'] as $chave => $arquivoDoc) {

                        if (!empty($arquivoDoc['arquivo'])) {
                            $idDocExigido = $dadosInput['pacienteDoc'][$chave]['id'];
                            $arquivo = $arquivoDoc['arquivo'];
                            $nomeArquivo = $arquivo->getClientOriginalName();
                            $moveFile = $arquivo->move($dirDocFiles, $nomeArquivo);
                            $PlanoAprovacoesService->insertArquivoDocExigido($idDominio, $idPlAprov, $idDocExigido, $nomeArquivo);

                            $this->pacienteRepository->update($idDominio, $rowPaciente->id, [
                                'doc_verificados' => 1,
                                'doc_verificados_data' => date('Y-m-d H:i:s'),
                            ]);
                        }
                    }
                }
            }
            if (isset($dadosInput['enviaDepCodConfirmacao']) and $dadosInput['enviaDepCodConfirmacao'] == true) {

                $CodigoConfirmacaoService = new CodigoConfirmacaoService;

                if (!isset($dadosInput['email']) and empty($rowPaciente->email)) {
                    return $this->returnError(null, 'Informe o e-mail');
                }

                $email = (isset($dadosInput['email']) and !empty($dadosInput['email'])) ?$dadosInput['email']: $rowPaciente->email;
                $celular = (isset($dadosInput['celular']) and !empty($dadosInput['celular'])) ? $dadosInput['celular'] : $rowPaciente->celular;

                if (isset($dadosInput['codigoConfirmacao'])) {


                    if (empty($dadosInput['codigoConfirmacao'])) {
                        return $this->returnError(null, "Código invalido");
                    }


                    $verificaCodigo = $CodigoConfirmacaoService->verificaCodigo($idDominio, 1, $pacienteId, $dadosInput['codigoConfirmacao']);
                    if (!$verificaCodigo['success']) {
                        return $this->returnError(null, "Código invalido");
                    }
                } else {
                    $enviandoCodigo = $CodigoConfirmacaoService->enviarCodigo($idDominio, 1, $pacienteId, $rowPaciente->nome, $email, $celular, $dadosInsert);
                    return $enviandoCodigo;
                }
            }


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

    /**
     * 
     * @param \App\Services\Clinicas\type $idDominio
     * @param \App\Services\Clinicas\type $dadosInput
     * @param \App\Services\Clinicas\type $dadosFiles
     * @param \App\Services\Clinicas\type $rowDocExigidos
     * @param \App\Services\Clinicas\type $tipo
     * @return \App\Services\Clinicas\type
     * @param type $idDominio
     * @param type $dadosInput
     * @param type $dadosFiles
     * @param type $rowDocExigidos
     * @param type $tipo 1-Dependente, 2 - Paciente
     * @return type
     */
    private function verificaDocumentosExigidosDependentes($idDominio, $dadosInput, $dadosFiles, $rowDocExigidos, $tipo = 1) {

        $inputDoc = ($tipo == 1) ? 'dependenteDoc' : 'pacienteDoc';

        ///verificando documentos exigidos dos dependentes
        $nomesDocumentos = implode(', ', array_map(function ($item) {
                    return $item->nome;
                }, $rowDocExigidos));
//        dd($rowDocExigidos);
//        if (!isset($dadosInput['dependenteDoc'])) {
//            return $this->returnError(null, "É necessário o envio dos documentos exigidos: " . $nomesDocumentos);
//        }

        foreach ($rowDocExigidos as $rowDoc) {
            $dataError = ['documentoId' => $rowDoc->id];
            if ($rowDoc->obrigatorio == 1 and !isset($dadosInput[$inputDoc])) {
                return $this->returnError($dataError, "O documento: '" . utf8_decode($rowDoc->nome) . "' é obrigatório.");
            }


            $chaveInput = array_search($rowDoc->id, array_column($dadosInput[$inputDoc], 'id'));

            if ($chaveInput === false) {
                return $this->returnError($dataError, "É necessário o envio do documento: " . utf8_decode($rowDoc->nome));
            } else if (!isset($dadosFiles[$inputDoc][$chaveInput]['arquivo'])) {
                return $this->returnError($dataError, "Arquivo não enviado do documento: " . utf8_decode($rowDoc->nome));
            } else {
                $arquivoIn = $dadosFiles[$inputDoc][$chaveInput]['arquivo'];
                $extArqIn = $arquivoIn->extension();

                if ($rowDoc->formato_arquivo == 1 and $extArqIn != 'pdf' and !Functions::isImageExtension($extArqIn)) {
                    return $this->returnError($dataError, "O  arquivo do documento deve ser do tipo PDF ou uma imagem: " . utf8_decode($rowDoc->nome));
                } elseif ($rowDoc->formato_arquivo == 2 and !Functions::isImageExtension($extArqIn)) {
                    return $this->returnError($dataError, "O  arquivo do documento deve ser  uma imagem: " . utf8_decode($rowDoc->nome));
                } elseif ($rowDoc->formato_arquivo == 3 and $extArqIn != 'pdf' and $extArqIn != 'PDF') {
                    return $this->returnError($dataError, "O  arquivo do documento deve ser  um PDF: " . utf8_decode($rowDoc->nome));
                }
            }
        }


        return $this->returnSuccess();
    }

    private function aceitaTermos($idDominio, $pacienteId, $termoSimdoctor, $termoClinica) {
        ///Termos
        if ($termoSimdoctor == true) {
            $LgpdAutorizacoesService = new LgpdAutorizacoesService;
            $LgpdAutorizacoesService->salvarTermosCondicoes($idDominio, $pacienteId, 1, 1, 1);
        }
        if ($termoClinica == true) {
            $LgpdAutorizacoesService = new LgpdAutorizacoesService;
            $LgpdAutorizacoesService->salvarTermosCondicoes($idDominio, $pacienteId, 2, 1, 1);
        }
    }

    public function store($idDominio, $dadosInput, $dadosFiles = null) {

//        var_dump($_FILES):
//        var_dump($_POST):
//        exit;
        $DominioService = new DominioService;
        $rowDominio = $DominioService->getById($idDominio);
        $nomesDominio = $rowDominio['data']->dominio;

        $DefinicaoMarcacaoGlobalRepository = new DefinicaoMarcacaoGlobalRepository();
        $rowDEfGlobal = $DefinicaoMarcacaoGlobalRepository->getDadosDefinicao($idDominio, ['habilita_doc_dependentes', 'hbt_aprov_dependente']);

        $DocumentosExigidosRepository = new DocumentosExigidosRepository();
        $rowDocExigidos = $DocumentosExigidosRepository->getAllExibeCadastro($idDominio, 1);
        $rowDocExigidosPacientes = $DocumentosExigidosRepository->getAllExibeCadastro($idDominio, 2);

        $CodigoConfirmacaoService = new CodigoConfirmacaoService;

        ///documentos do dependentes
        if (isset($dadosInput['isDependente']) and $dadosInput['isDependente'] == true
                and $rowDEfGlobal->habilita_doc_dependentes == 1
                and count($rowDocExigidos) > 0
        ) {

            $verificaDoc = $this->verificaDocumentosExigidosDependentes($idDominio, $dadosInput, $dadosFiles, $rowDocExigidos);
            if (!$verificaDoc['success']) {
                return $verificaDoc;
            }


            $verificaCpfDep = $this->pacienteRepository->getAll($idDominio, ['cpf' => Functions::cpfToNumber($dadosInput['cpf'])]);
            if (count($verificaCpfDep) > 0) {
                return $this->returnError(null, 'Cpf já existe no sitema');
            }
        }

        ///documentos do pacientes
        if (isset($dadosInput['pacienteDoc'])) {
            $verificaDoc = $this->verificaDocumentosExigidosDependentes($idDominio, $dadosInput, $dadosFiles, $rowDocExigidosPacientes, 2);
            if (!$verificaDoc['success']) {
                return $verificaDoc;
            }
        }



        //Termos
        if (isset($dadosInput['termoSimdoctor']) and $dadosInput['termoSimdoctor'] != true) {
            return $this->returnError(null, 'Você deve concordar com os termos do Simdoctor para continuar');
        }
        if (isset($dadosInput['termoClinica']) and $dadosInput['termoClinica'] != true) {
            return $this->returnError(null, 'Você deve concordar com os termos da clínica para continuar');
        }
        $termoSimdoctor = (isset($dadosInput['termoSimdoctor'])) ? $dadosInput['termoSimdoctor'] : null;
        $termoClinica = (isset($dadosInput['termoClinica'])) ? $dadosInput['termoClinica'] : null;

        $arrayMapTabelaPacientes = $this->mapFieldsToDB();

        $dadosInsert = null;
        foreach ($dadosInput as $nomeCampo => $valor) {

            if (isset($arrayMapTabelaPacientes[$nomeCampo])) {

                if ($nomeCampo == 'sexo' and !empty($valor) and $valor != 'Masculino' and $valor != 'Feminino') {
                    return $this->returnError('', 'O campo sexo deve ser Masculino ou Feminino');
                }
                if ($nomeCampo == 'envia_sms' and !empty($valor) and $valor != 1 and $valor != 0) {
                    return $this->returnError('', 'O campo envia_sms deve ser 1 ou 0');
                }
                if ($nomeCampo == 'envia_email' and !empty($valor) and $valor != 1 and $valor != 0) {
                    return $this->returnError('', 'O campo envia_email deve ser 1 ou 0');
                }
                if ($nomeCampo == 'senha' and empty($valor)) {
                    return $this->returnError('', 'Informe a senha.');
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

        ///////////


        if ($dadosInsert != null) {
            $dadosRetorno = ['data' => null, 'msg' => ''];

            ///dados se for dependente
            if (isset($dadosInput['isDependente']) and $dadosInput['isDependente'] == true) {


                $rowPacPrincipal = $this->pacienteRepository->getById($idDominio, $dadosInput['pacIdResponsavel']);
                $rowPacPrincipal = $rowPacPrincipal[0];

                ///codigo de confirmacao
                if (isset($dadosInput['enviaDepCodConfirmacao']) and $dadosInput['enviaDepCodConfirmacao'] == true) {



                    if (isset($dadosInput['codigoConfirmacao'])) {

                        if (empty($dadosInput['codigoConfirmacao'])) {
                            return $this->returnError(null, "Código invalido");
                        }

                        $verificaCodigo = $CodigoConfirmacaoService->verificaCodigo($idDominio, 1, $dadosInput['pacIdResponsavel'], $dadosInput['codigoConfirmacao']);
                        if (!$verificaCodigo['success']) {
                            return $this->returnError(null, "Código invalido");
                        }

                        ///confirmando codigo nos registros
//                        $PacienteQrCodeRepository = new PacienteQrCodeRepository;
//                        $rowQrcodeVerifica = $PacienteQrCodeRepository->verificaExiste($idDominio, $dadosInput['pacIdResponsavel'], 4);
//                        if ($rowQrcodeVerifica) {
//                            $PacienteQrCodeRepository->alterarStatus($idDominio,$dadosInput['pacIdResponsavel'], 0);
//                        }
                        //confirmando aprovações verificadas
                        $PlanoAprovacoes = new PlanoAprovacoesRepository;
                        $qrAprovNaoConfirmado = $PlanoAprovacoes->getAprovacoesCodigoNaoConfirmado($idDominio, $dadosInput['pacIdResponsavel']);

                        if ($qrAprovNaoConfirmado) {
                            foreach ($qrAprovNaoConfirmado as $rowAprovConf) {
                                $PlanoAprovacoes->alterarAprovacao($idDominio, $rowAprovConf->id, 1);
                            }
                        }
                        $dadosRetorno['data'] = ['status' => 'em_analise'];
                        $dadosRetorno['msg'] = 'Dependente cadastrado com sucesso. O cadastro está em análise para aprovação';
                        return $this->returnSuccess($dadosRetorno['data'], $dadosRetorno['msg']);
                    } else {

                        if ($rowDEfGlobal->hbt_aprov_dependente == 1) {


                            $PlanoAprovacoesService = new PlanoAprovacoesService;

                            if (isset($dadosFiles['foto']) and !empty($dadosFiles['foto'])) {
                                $insertFotos = $PlanoAprovacoesService->salvaFotoPerfilAprovacao($idDominio, $dadosFiles['foto']);
                                if ($insertFotos['success']) {
                                    $PlanoAprovacoesService->setNome_foto($insertFotos['data']['nomeFoto']);
                                }
                            }


                            if ($rowDEfGlobal->habilita_doc_dependentes == 1) {
                                ////DOcumentos dos dependentes
                                $idsDocs = null;
                                if ($rowDocExigidos) {
                                    $idsDocs = implode(', ', array_map(function ($item) {
                                                return $item->id;
                                            }, $rowDocExigidos));
                                }


                                $PlanoAprovacoesService->setIdentificador($idDominio);
                                $PlanoAprovacoesService->setPacientes_id($dadosInput['pacIdResponsavel']);
                                $PlanoAprovacoesService->setTipo(1);
                                $PlanoAprovacoesService->setStatus(4);
                                $PlanoAprovacoesService->setNome($dadosInput['nome']);
                                $PlanoAprovacoesService->setSobrenome($dadosInput['sobrenome']);

                                $PlanoAprovacoesService->setSexo((isset($dadosInput['sexo'])) ? $dadosInput['sexo'] : null);
                                $PlanoAprovacoesService->setCpf((isset($dadosInput['cpf'])) ? $dadosInput['cpf'] : null);
                                $PlanoAprovacoesService->setData_nascimento((isset($dadosInput['dataNascimento'])) ? $dadosInput['dataNascimento'] : null );
                                $PlanoAprovacoesService->setFiliacao((isset($dadosInput['tipoDependente'])) ? $dadosInput['tipoDependente'] : null );
                                $PlanoAprovacoesService->setDoc_exigidos_ids_hist($idsDocs);
                                $idPlAprov = $PlanoAprovacoesService->insert($idDominio);

                                if (isset($dadosFiles['dependenteDoc']) and count($dadosFiles['dependenteDoc']) > 0) {

                                    $dirDocFiles = $PlanoAprovacoesService->getPathDoc($nomesDominio) . '/' . $idPlAprov;

                                    foreach ($dadosFiles['dependenteDoc'] as $chave => $arquivoDoc) {

                                        if (!empty($arquivoDoc['arquivo'])) {
                                            $idDocExigido = $dadosInput['dependenteDoc'][$chave]['id'];
                                            $arquivo = $arquivoDoc['arquivo'];
                                            $nomeArquivo = $arquivo->getClientOriginalName();
                                            $moveFile = $arquivo->move($dirDocFiles, $nomeArquivo);
                                            $PlanoAprovacoesService->insertArquivoDocExigido($idDominio, $idPlAprov, $idDocExigido, $nomeArquivo);
                                        }
                                    }
                                }
                            }

                            $dadosRetorno['data'] = ['status' => 'em_analise'];
                            $dadosRetorno['msg'] = 'Dependente cadastrado com sucesso. O cadastro está em análise para aprovação';
                        } else {
                            $pacienteId = $this->pacienteRepository->insert($idDominio, $dadosInsert);

                            $PacienteDependentesRepository = new PacienteDependentesRepository();
                            $camposDep['paciente_id'] = $dadosInput['pacIdResponsavel'];
                            $camposDep['dependente_id'] = $pacienteId;
                            $camposDep['filiacao'] = $dadosInput['tipoDependente'];
                            $camposDep['identificador'] = $idDominio;
                            $idPacDepAssoc = $PacienteDependentesRepository->store($idDominio, $camposDep);
                            if (isset($dadosFiles['foto']) and !empty($dadosFiles['foto'])) {
                                $insertFotos = $this->alterarFotoPerfil($idDominio, $pacienteId, $dadosFiles['foto']);
                            }
                            $PacienteDependentesRepository->alteraAprovacaoDependente($idDominio, $idPacDepAssoc, 1);

                            ///Termos
                            if (!empty($termoSimdoctor) or !empty($termoClinica)) {
                                $this->aceitaTermos($idDominio, $pacienteId, $termoSimdoctor, $termoClinica);
                            }

                            $rowPac = $this->pacienteRepository->getById($idDominio, $pacienteId);
                            $rowPac = $this->fieldsResponse($rowPac[0], $nomesDominio);

                            $dadosRetorno['data'] = ['status' => 'aprovado'];
                            $dadosRetorno['msg'] = 'Dependente cadastrado com sucesso.';
                        }

                        $rowPacienteResp = $this->pacienteRepository->getById($idDominio, $dadosInput['pacIdResponsavel']);
                        $rowPacienteResp = $rowPacienteResp[0];
                        $enviandoCodigo = $CodigoConfirmacaoService->enviarCodigo($idDominio, 1, $dadosInput['pacIdResponsavel'], $dadosInput[''], $rowPacienteResp->email, $rowPacienteResp->celular);
                        return $enviandoCodigo;
                    }
                }
            } else {



                if (isset($dadosInput['enviaDepCodConfirmacao']) and $dadosInput['enviaDepCodConfirmacao'] == true) {

                    if (!isset($dadosInput['email'])) {
                        return $this->returnError(null, 'Informe o e-mail');
                    }

                    //email
                    $verificaEmail = $this->pacienteRepository->buscaPorEmail($idDominio, $dadosInput['email']);

                    if ($verificaEmail) {
                        return $this->returnError(null, 'Este e-mail já está cadastrado!');
                    }

                    if (isset($dadosInput['codigoConfirmacao'])) {

                        if (empty($dadosInput['codigoConfirmacao'])) {
                            return $this->returnError(null, "Código invalido");
                        }
                        $verificaCodigo = $CodigoConfirmacaoService->verificaCodigoPorEmail($idDominio, 2, $dadosInput['email'], $dadosInput['codigoConfirmacao']);
                        if (!$verificaCodigo['success']) {
                            return $this->returnError(null, "Código invalido");
                        }
                    } else {
                        $celular = (isset($dadosInsert['celular_cript'])) ? $dadosInsert['celular_cript'] : null;
                        $enviandoCodigo = $CodigoConfirmacaoService->enviarCodigo($idDominio, 2, null, $dadosInsert['nome_cript'], $dadosInsert['email_cript'], $celular, $dadosInsert);
                        return $enviandoCodigo;
                    }
                }


                $pacienteId = $this->pacienteRepository->insert($idDominio, $dadosInsert);
                $rowPac = $this->pacienteRepository->getById($idDominio, $pacienteId);
                $rowPacPrincipal = $rowPac[0];
                $rowPac = $this->fieldsResponse($rowPac[0], $nomesDominio);

                ///Termos
                if (!empty($termoSimdoctor) or !empty($termoClinica)) {
                    $this->aceitaTermos($idDominio, $pacienteId, $termoSimdoctor, $termoClinica);
                }

                $dadosRetorno['data'] = $rowPac;
                $dadosRetorno['msg'] = 'Cadastrado com sucesso';
            }


            if ($rowDEfGlobal->hbt_aprov_dependente == 1 && $rowDEfGlobal->habilita_doc_dependentes == 1 && $rowPacPrincipal->doc_verificados == 0) {
                //Documentos do paciente
                if (isset($dadosInput['pacienteDoc']) and count($dadosInput['pacienteDoc']) > 0) {

                    $idsDocsPac = [];
                    if ($rowDocExigidosPacientes) {
                        $idsDocsPac = implode(', ', array_map(function ($item) {
                                    return $item->id;
                                }, $rowDocExigidosPacientes));
                    }


                    $PlanoAprovacoesService = new PlanoAprovacoesService;
                    $PlanoAprovacoesService->setIdentificador($idDominio);
                    $PlanoAprovacoesService->setPacientes_id($rowPacPrincipal->id);
                    $PlanoAprovacoesService->setTipo(3);
                    $PlanoAprovacoesService->setStatus(4);
                    $PlanoAprovacoesService->setNome($rowPacPrincipal->nome);
                    $PlanoAprovacoesService->setSobrenome($rowPacPrincipal->sobrenome);
                    $PlanoAprovacoesService->setDoc_exigidos_ids_hist($idsDocsPac);
                    $idPlAprov = $PlanoAprovacoesService->insert($idDominio);
                    $dirDocFiles = $PlanoAprovacoesService->getPathDoc($nomesDominio) . '/' . $idPlAprov;

                    if (!isset($dadosInput['isDependente']) or $dadosInput['isDependente'] != true) {
                        $rowPac = $this->pacienteRepository->getById($idDominio, $rowPacPrincipal->id);
                        $rowPacPrincipal = $rowPac[0];
                        $rowPac = $this->fieldsResponse($rowPac[0], $nomesDominio);
                        $dadosRetorno['data'] = $rowPac;
                    }

                    foreach ($dadosFiles['pacienteDoc'] as $chave => $arquivoDoc) {

                        if (!empty($arquivoDoc['arquivo'])) {
                            $idDocExigido = $dadosInput['pacienteDoc'][$chave]['id'];
                            $arquivo = $arquivoDoc['arquivo'];
                            $nomeArquivo = $arquivo->getClientOriginalName();
                            $moveFile = $arquivo->move($dirDocFiles, $nomeArquivo);
                            $PlanoAprovacoesService->insertArquivoDocExigido($idDominio, $idPlAprov, $idDocExigido, $nomeArquivo);
                            $this->pacienteRepository->update($idDominio, $rowPacPrincipal->id, [
                                'doc_verificados' => 1,
                                'doc_verificados_data' => date('Y-m-d H:i:s'),
                            ]);
                        }
                    }
                }
            }

            return $this->returnSuccess($dadosRetorno['data'], $dadosRetorno['msg']);
        }
    }

    public function proximasConsultas($idDominio, $pacienteId, $request) {

        $ConsultaService = new ConsultaService();

        $dadosQuery['pacienteId'] = $pacienteId;
        $dadosQuery['dataHoraApartirDe'] = date('Y-m-d H:i');
        $dadosQuery['page'] = ($request->has('page') and !empty($request->has('page'))) ? $request->query('page') : 1;
        $dadosQuery['perPage'] = ($request->has('perPage') and !empty($request->has('perPage'))) ? $request->query('perPage') : 50;
        $dadosQuery['orderBy'] = 'dataConsulta.asc,horaCOnsultas.asc';
//        $dadosQuery['data'] = $pacienteId;
        $qrConsultas = $ConsultaService->getAll($idDominio, null, $dadosQuery);

        return $qrConsultas;
    }

    public function getHistoricoConsulta($idDominio, $pacienteId, $request) {

        $ConsultaService = new ConsultaService();

        $dadosQuery['pacienteId'] = $pacienteId;
        $dadosQuery['showProcedimentos'] = true;
        $dadosQuery['dataHoraLimite'] = ($request->has('dataHoraLimite') and !empty($request->has('dataHoraLimite'))) ? $request->query('dataHoraLimite') : date('Y-m-d H:i');

        $dadosQuery['page'] = ($request->has('page') and !empty($request->has('page'))) ? $request->query('page') : 1;
        $dadosQuery['perPage'] = ($request->has('perPage') and !empty($request->has('perPage'))) ? $request->query('perPage') : 50;
        $dadosQuery['orderBy'] = ($request->has('orderBy') and !empty($request->has('orderBy'))) ? $request->query('orderBy') : 'dataConsulta.desc,horaCOnsultas.desc';
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

    public function getDependentes($idDominio, $idPaciente, $dadosQuery = null) {

        $PacienteDependenteService = new PacienteDependenteService;
        $qrPacDep = $PacienteDependenteService->getByPaciente($idDominio, $idPaciente, $dadosQuery);
        return $qrPacDep;
    }

    public function enviarCodigo($idDominio, $pacienteId, $dadosInput) {

        $rowPaciente = $this->pacienteRepository->getById($idDominio, $pacienteId);
        if (!$rowPaciente) {
            return $this->returnError('Paciente não encontrado');
        }
        $rowPaciente = $rowPaciente[0];
//        dd($rowPaciente);

        $CodigoConfirmacaoService = new CodigoConfirmacaoService;
        $enviandoCodigo = $CodigoConfirmacaoService->enviarCodigo($idDominio, 1, $pacienteId, $rowPaciente->nome, $rowPaciente->email, $rowPaciente->celular);
        return $enviandoCodigo;
    }

    public function verificarCodigo($idDominio, $pacienteId, $dadosInput) {




        $rowPaciente = $this->pacienteRepository->getById($idDominio, $pacienteId);
        if (!$rowPaciente) {
            return $this->returnError('Paciente não encontrado');
        }
        $rowPaciente = $rowPaciente[0];
//        dd($rowPaciente);
        $CodigoConfirmacaoService = new CodigoConfirmacaoService;
        $verificaCodigo = $CodigoConfirmacaoService->verificaCodigo($idDominio, 1, $pacienteId, $dadosInput['codigoConfirmacao']);
        if (!$verificaCodigo['success']) {
            return $this->returnError(null, "Código inválido");
        }

        if ($dadosInput['tipo'] == 'dependente') {

            $PacienteQrCodeRepository = new PacienteQrCodeRepository;
            $rowQrcodeVerifica = $PacienteQrCodeRepository->verificaExiste($idDominio, $rowPaciente->id, 4);
            if ($rowQrcodeVerifica) {
                $PacienteQrCodeRepository->alterarStatus($idDominio, $rowQrcodeVerifica->id, 0);
            }

            //confirmando aprovações verificadas
            $PlanoAprovacoes = new PlanoAprovacoesRepository;
            $qrAprovNaoConfirmado = $PlanoAprovacoes->getAprovacoesCodigoNaoConfirmado($idDominio, $rowPaciente->id);

//            dd($qrAprovNaoConfirmado);

            if ($qrAprovNaoConfirmado) {
                foreach ($qrAprovNaoConfirmado as $rowAprovConf) {
                    $PlanoAprovacoes->alterarAprovacao($idDominio, $rowAprovConf->id, 1);
                }
            }
        }

        return $this->returnSuccess(null, 'Confirmado com sucesso');
    }
}
