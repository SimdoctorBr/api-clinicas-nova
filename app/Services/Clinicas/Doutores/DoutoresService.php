<?php

namespace App\Services\Clinicas\Doutores;

use App\Repositories\Clinicas\DoutoresRepository;
use App\Services\BaseService;
use App\Services\Clinicas\EspecialidadeService;
use App\Services\Clinicas\GrupoAtendimentoService;
use App\Services\Clinicas\IdiomaClinicaService;
use App\Services\Clinicas\DoutorFormacaoService;
use App\Services\Gerenciamento\DominioService;
use App\Services\Clinicas\PacienteService;
use App\Repositories\Clinicas\Doutores\DoutorAvaliacaoRepository;
use App\Repositories\Clinicas\GrupoAtendimentoRepository;
use App\Repositories\Clinicas\IdiomaClinicaRepository;
use App\Repositories\Clinicas\DoutorFormacaoRepository;
use App\Repositories\Clinicas\ConvenioRepository;
use App\Repositories\Clinicas\ProcedimentosRepository;
use App\Helpers\Functions;
use App\Repositories\Clinicas\EspecialidadeRepository;
use App\Repositories\Clinicas\ConselhosProfissionaisRepository;
use App\Repositories\Clinicas\CboRepository;
use App\Repositories\Clinicas\UfRepository;
use App\Services\Clinicas\AprovacoesAlteracaoService;
use DateTime;

class DoutoresService extends BaseService {

    private $doutoresRepository;
    private $especialidadeService;
    private $grupoAtendimentoService;
    private $idiomaClinicaService;
    private $doutorFormacaoService;
    private $dominioService;

    public function __construct() {
        $this->doutoresRepository = new DoutoresRepository();
        $this->especialidadeService = new EspecialidadeService();
        $this->grupoAtendimentoService = new GrupoAtendimentoService();
        $this->idiomaClinicaService = new IdiomaClinicaService();
        $this->doutorFormacaoService = new DoutorFormacaoService();
        $this->dominioService = new DominioService();
    }

    public function fieldsResponse($row, $nomeDominio) {

//        dd($row);
        $retorno['id'] = $row->id;
        $retorno['nome'] = $row->nome;
        $retorno['email'] = $row->email;
//        $retorno['telefone'] = $row->telefone;
//        $retorno['celular'] = $row->celular;
//        $retorno['celular2'] = $row->celular2;
        $retorno['sexo'] = $row->sexo;
        $retorno['dataCad'] = $row->dataCad;
        $retorno['possuiVideoconsulta'] = ($row->possui_videoconf == 1) ? true : false;
        $retorno['duracaoVideoConsulta'] = (!empty($row->duracao_videocons)) ? $row->duracao_videocons : null;
        $retorno['precoConsulta'] = ($row->possui_videoconf == 1) ? str_replace(',', '.', str_replace('.', '', $row->precoConsulta)) : null;

        if (isset($row->totalConsultasAtendidas)) {
            $retorno['totalConsultasAtendidas'] = $row->totalConsultasAtendidas;
        }

        if (!empty($row->nome_foto)) {
            $retorno['urlFoto'] = env('APP_URL_CLINICAS') . '/' . $nomeDominio . '/arquivos/fotos_doutor/' . $row->nome_foto;
        } else {
            $retorno['urlFoto'] = null;
        }


        $row->possui_videoconf;
//        $retorno['somenteVideoconsulta'] = $row->somente_videoconf;
//        $retorno['idDominio'] = $row->identificador;
//        dd($row);

        $retorno['especialidades'] = null;
        if (isset($row->nomeEspecialidade) and !empty($row->nomeEspecialidade)) {
            $retorno['especialidades'][] = array('nome' => utf8_decode($row->nomeEspecialidade));
        } elseif (isset($row->outra_especialidade) and !empty($row->outra_especialidade)) {
            $retorno['especialidades'][] = array('nome' => $row->outra_especialidade);
        }


        $qrEspecialidades = $this->especialidadeService->getByDoutorId($row->identificador, $row->id);
        if ($qrEspecialidades['success']) {
            $retorno['especialidades'] = $qrEspecialidades['data'];
        }

        $qrGrpAtend = $this->grupoAtendimentoService->getByDoutorId($row->identificador, $row->id);
        if ($qrGrpAtend['success']) {
            $retorno['gruposAtendimento'] = $qrGrpAtend['data'];
        } else {
            $retorno['gruposAtendimento'] = null;
        }

        $qrIdioma = $this->idiomaClinicaService->getByDoutorId($row->identificador, $row->id);

        if ($qrIdioma['success']) {
            $retorno['idiomas'] = $qrIdioma['data'];
        } else {
            $retorno['idiomas'] = null;
        }

        $qrFormacao = $this->doutorFormacaoService->getByDoutorId($row->identificador, $row->id);

        if ($qrFormacao ['success']) {
            $retorno['formacoes'] = $qrFormacao ['data'];
        } else {
            $retorno['formacoes'] = null;
        }

        if (isset($row->tags_tratamentos) and !empty($row->tags_tratamentos)) {

            $tagsTrat = json_decode($row->tags_tratamentos);

            $retorno['tagsTratamentos'] = $tagsTrat;
        } else {
            $retorno['tagsTratamentos'] = null;
        }

//dd($row);
        $retorno['sobre'] = html_entity_decode($row->sobre);
        $retorno['linkVideoProfissional'] = $row->link_video;
        $retorno['pontuacao'] = $row->pontuacao;
        $retorno['perfilId'] = $row->identificador;
        $retorno['dataIniAtividadeProf'] = $row->dt_ini_atividade_prof;

        $retorno['favoritoPaciente'] = (isset($row->favoritoPaciente) and !empty($row->favoritoPaciente)) ? true : false;
        $retorno['favoritoId'] = (isset($row->favoritoPaciente) and !empty($row->favoritoPaciente)) ? $row->favoritoPaciente : null;

        $retorno['conselho'] = [
            'nome' => (!empty($row->nomeConselhoProfissional)) ? $row->nomeConselhoProfissional : null,
            'sigla' => (!empty($row->codigoConselhoProfisssional)) ? $row->codigoConselhoProfisssional : null,
            'numero' => (!empty($row->conselho_profissional_numero)) ? $row->conselho_profissional_numero : null,
            'uf' => (!empty($row->siglaUFConselhoProfisional)) ? $row->siglaUFConselhoProfisional : null,
            'codCBO' => (!empty($row->codigoCBO)) ? $row->codigoCBO : null,
            'nomeCBO' => (!empty($row->nomeCBO)) ? $row->nomeCBO : null,
        ];

        $retorno['procedimentoPadrao'] = null;
        if (isset($row->proc_doutor_id_presencial) and !empty($row->proc_doutor_id_presencial)) {
            $retorno['procedimentoPadrao'] = [
                'idProcedimentoDoutor' => $row->proc_doutor_id_presencial,
                'idProcedimento' => $row->procPadraoIdProcedimento,
                'nomeProcedimento' => $row->procPadraoNome,
                'duracao' => $row->procDuracao,
                'convenio' => ['id' => $row->procPadraoIdConvenio,
                    'nome' => $row->procPadraoNomeConvenio,
                ],
                'valor' => $row->procPadraoValor,
            ];
        }
        $retorno['procedimentoPadraoVideo'] = null;
        if (isset($row->proc_doutor_id_video) and !empty($row->proc_doutor_id_video)) {
            $ProcedimentosRepository = new ProcedimentosRepository();
            $rowProc = $ProcedimentosRepository->getAllProcedimentosVinculados($row->identificador, null, null, $row->proc_doutor_id_video);
            $rowProc = $rowProc[0];

            $retorno['procedimentoPadraoVideo'] = [
                'idProcedimentoDoutor' => $row->proc_doutor_id_video,
                'idProcedimento' => $rowProc->procedimentos_id,
                'nomeProcedimento' => $rowProc->nomeProcedimento,
                'duracao' => $rowProc->duracao,
                'convenio' => ['id' => $rowProc->proc_convenios_id,
                    'nome' => $rowProc->nomeConvenioProc,
                ],
                'valor' => $rowProc->valor_proc,
            ];
        }

        return $retorno;
    }

    public function store($idDominio, $dadosInput) {


        $dadosInsert = $this->validateStoreUpdate($idDominio, $dadosInput);
        if (isset($dadosInsert['success']) and !$dadosInsert['success']) {
            return $dadosInsert;
        }



//        if (isset($dadosInput['senha'])) {
//            //cria usuario
//            $dadosInput['senha_confirm'];
//        }




        if (isset($dadosInput['aprovacao']) and $dadosInput['aprovacao'] == true) {
            //cria usuario
//            $dadosInput['senha_confirm'];
            $jsonDadosAprovacao = $this->getDadosJSONAprovacaoInput($idDominio, $dadosInsert, $dadosInput);

            $AprovacoesAlteracaoService = new AprovacoesAlteracaoService;
            $AprovacoesAlteracaoService->setIdentificador($idDominio);
            $AprovacoesAlteracaoService->setTipo(1);
            $AprovacoesAlteracaoService->setJson_alteracao($jsonDadosAprovacao);
            $AprovacoesAlteracaoService->setDescricao($dadosInput['nome']);
            $AprovacoesAlteracaoService->insert();

            return $this->returnSuccess(null, "Cadastro enviado para aprovação com sucesso!");
        } else {



            $idDoutor = $this->doutoresRepository->store($idDominio, $dadosInsert);

//            $idDoutor = 8937;
            if (!empty($idDoutor)) {


                $this->insertEndereco($idDominio, $idDoutor, $dadosInput);

                /////GRUPO DE ATENDIMENTO
                if (isset($dadosInput['grupoAtendimentoId']) and count($dadosInput['grupoAtendimentoId']) > 0) {
                    foreach ($dadosInput['grupoAtendimentoId'] as $idGrupo) {
                        $this->grupoAtendimentoService->insertGrupoDoutor($idDominio, $idDoutor, $idGrupo);
                    }
                }
                /////IDIOMAS
                if (isset($dadosInput['idiomasId']) and count($dadosInput['idiomasId']) > 0) {
                    foreach ($dadosInput['idiomasId'] as $idIdioma) {
                        $this->idiomaClinicaService->insertIdiomasDoutor($idDominio, $idDoutor, $idIdioma);
                    }
                }
                /////FORMAÇÃO ACADÊMICA
                if (isset($dadosInput['formAcademicaTipo']) and count($dadosInput['formAcademicaTipo']) > 0) {
                    foreach ($dadosInput['formAcademicaTipo'] as $chave => $formTipo) {
                        $DoutorFormacaoService = new DoutorFormacaoService;
                        $DoutorFormacaoService->setDoutores_id($idDoutor);
                        $DoutorFormacaoService->setTipo_formacao($formTipo);
                        $DoutorFormacaoService->setInstituicao_ensino((isset($dadosInput['formAcademicaInst'][$chave])) ? Functions::accentsToUtf8Convert($dadosInput['formAcademicaInst'][$chave]) : null);
                        $DoutorFormacaoService->setNome_formacao((isset($dadosInput['formAcademicaFormacao'][$chave])) ? Functions::accentsToUtf8Convert($dadosInput['formAcademicaFormacao'][$chave]) : null);
                        $DoutorFormacaoService->setPeriodo_de((isset($dadosInput['formAcademicaDtDe'][$chave])) ? $dadosInput['formAcademicaDtDe'][$chave] : null);
                        $DoutorFormacaoService->setPeriodo_ate((isset($dadosInput['formAcademicaDtAte'][$chave])) ? $dadosInput['formAcademicaDtAte'][$chave] : null);
                        $DoutorFormacaoService->insertFormacaoDoutor($idDominio, $idDoutor);
                    }
                }


                //Especialidades
                if (isset($dadosInput['especialidadeNome']) and count($dadosInput['especialidadeNome']) > 0) {
                    $EspecialidadeRepository = new EspecialidadeRepository;
                    foreach ($dadosInput['especialidadeNome'] as $rowNomeEsp) {
                        $qrEsp = $EspecialidadeRepository->findByName($idDominio, $rowNomeEsp);
                        $especialidadeID = null;
                        $outraEsp = null;
                        if (!$qrEsp) {
                            $outraEsp = $rowNomeEsp;
                        } else {
                            $especialidadeID = $qrEsp->id;
                        }
                        $this->especialidadeService->insertEspecialidadeDoutor($idDominio, $idDoutor, $especialidadeID, $outraEsp);
                    }
                }


                $rowDoutor = $this->doutoresRepository->getById($idDominio, $idDoutor);
                $Dominio = new DominioService;
                $rowDominio = $Dominio->getById($idDominio);
                return $this->returnSuccess($this->fieldsResponse($rowDoutor, $rowDominio['data']->dominio));
            }
        }





        //Grupo atendimento
        //Idiomas
        //Formação academica
        //tagsTratamento
    }

    public function getAll($idDominio, $dadosFiltro = null, $page = 1, $perPage = 100) {



        $filtroOrderBy = null;
        if (isset($dadosFiltro['orderBy']) and !empty($dadosFiltro['orderBy'])) {
            $arrayOrders = ['nome', 'pontuacao', 'precoConsulta', 'totalConsultasAtendidas', 'dataCad'];
            $orders = explode(',', $dadosFiltro['orderBy']);
            foreach ($orders as $order) {


                if (strpos($order, '.')) {
                    $exOrder = explode('.', $order);
                    if (!in_array($exOrder[0], $arrayOrders) or ( $exOrder[1] != 'asc' and $exOrder[1] != 'desc')) {
                        return $this->returnError('', 'Tipo de ordenação inválida');
                    }

                    $filtroOrderBy[] = $exOrder[0] . ' ' . $exOrder[1];
                } else {

                    if (!in_array($order, $arrayOrders)) {
                        return $this->returnError('', 'Tipo de ordenação inválida');
                    }
                    $filtroOrderBy[] = $order;
                }
            }

            $dadosFiltro['orderBy'] = implode(',', $filtroOrderBy);
        }



        $qr = $this->doutoresRepository->getAll($idDominio, $dadosFiltro, $page, $perPage);

        if ($qr) {
            $retorno = null;

            $nomesDominio = null;
            foreach ($qr['results'] as $row) {



                if (!isset($nomesDominio[$row->identificador])) {
                    $rowDominio = $this->dominioService->getById($row->identificador);
                    $nomesDominio[$row->identificador] = $rowDominio['data']->dominio;
                }

                $retorno[] = $this->fieldsResponse($row, $nomesDominio[$row->identificador]);
            }

            $qr['results'] = $retorno;
            return $qr;
        } else {
            return $this->returnError('', 'Nenhum profissional encontrado');
        }
    }

    public function getById($idDominio, $idDoutor) {

        $rowDominio = $this->dominioService->getById($idDominio);
        $rowDominio = $rowDominio['data'];

        $qr = $this->doutoresRepository->getById($idDominio, $idDoutor);

        if ($qr) {
            $retorno = $this->fieldsResponse($qr, $rowDominio->dominio);
            return $this->returnSuccess($retorno);
        } else {
            return $this->returnError('', 'Nenhum profissional encontrado');
        }
    }

    public function storeAvaliacoes($idDominio, $doutorId, $pacienteId, $pontuacao) {


        $qr = $this->doutoresRepository->getById($idDominio, $doutorId);
        if ($qr) {

            $PacienteService = new PacienteService;
            $qrPaciente = $PacienteService->getById($idDominio, $pacienteId);
            if (!$qrPaciente) {
                return $this->returnError('', 'Paciente não encontrado');
            }

            $DoutorAvaliacaoRepository = new DoutorAvaliacaoRepository;
            $DoutorAvaliacaoRepository->store($idDominio, $doutorId, $pacienteId, $pontuacao);

            return $this->returnSuccess('', 'Avaliação realizada com sucesso');
        } else {
            return $this->returnError('', 'Nenhum profissional encontrado');
        }
    }

    public function getFiltros($idDominio, $dadosFiltro = null) {


        $retorno = ['precoVideo' => ['min', 'max'], 'precoPresencial' => ['min', 'max'], 'especialidades' => [], 'grupoAtendimento' => [], 'formacaoAcademica' => [],
            'tags' => []];

        $retorno['grupoAtendimento'] = null;

        $resultPreco = $this->doutoresRepository->getValorConsultaMinMax($idDominio);

        if ($resultPreco) {
            $retorno['precoVideo'] = [
                'min' => $resultPreco['valorMinimoVideo'],
                'max' => $resultPreco['valorMaximoVideo'],
            ];
            $retorno['precoPresencial'] = [
                'min' => $resultPreco['valorMinimoPresencial'],
                'max' => $resultPreco['valorMaximoPresencial'],
            ];
        }

//        $resultSexo = $this->doutoresRepository->getAll($idDominio, ['sexo' => ['M']]);





        $dadosFiltro['exibeListaDoutores'] = true;
        $resultEspecialidades = $this->especialidadeService->getAll($idDominio, $dadosFiltro);
        if ($resultEspecialidades['success']) {
            $retorno['especialidades'] = $resultEspecialidades['data'];
        }

//VERIFICANDO DOUTORES
        $dadosFiltro['agruparIdDoutor'] = true;
        $qrDoutores = $this->doutoresRepository->getAll($idDominio, $dadosFiltro);
        $idsDoutores = [];
        $siglasSexos = [];
        if (!empty($qrDoutores[0]->idsDoutores)) {
            $idsDoutores = explode(',', $qrDoutores[0]->idsDoutores);
            $siglasSexos = array_unique(explode(',', $qrDoutores[0]->sexos));
        }
//        dd($qrDoutores);
////SEXO
        $retorno['sexo'] = [
            [
                'cod' => 'M',
                'nome' => 'Masculino',
                'disabled' => (!in_array('M', $siglasSexos)) ? true : false,
            ], [
                'cod' => 'F',
                'nome' => 'Feminino',
                'disabled' => (!in_array('F', $siglasSexos)) ? true : false,
            ], [
                'cod' => 'O',
                'nome' => 'Outros',
                'disabled' => (!in_array('O', $siglasSexos)) ? true : false,
        ]];
        unset($qrDoutores);

///TAGS TRATAMENTO
        $dadosFiltro['agruparIdDoutor'] = false;
        $qrDoutoresTags = $this->doutoresRepository->getAll($idDominio, $dadosFiltro);
        foreach ($qrDoutoresTags as $rowDout) {

            if (!empty($rowDout->tags_tratamentos)) {
                $tags = json_decode($rowDout->tags_tratamentos);
                foreach ($tags as $nomeTag) {
                    $retorno['tags'][] = array(
                        'nome' => $nomeTag,
                        'disabled' => false,
                    );
                }
            }
        }


        $temp = array_unique(array_column($retorno['tags'], 'nome'));
        $retorno['tags'] = array_values(array_intersect_key($retorno['tags'], $temp));

        //GRUPO DE ATENDIMENTO
        $GrupoAtendimentoRepository = new GrupoAtendimentoRepository;
        $idsGruposAtend = [];
        if (count($idsDoutores) > 0) {
            $qrDoutoresGrupoAtend = $GrupoAtendimentoRepository->getDoutoresFiltro($idDominio, $idsDoutores, true);
            if (count($qrDoutoresGrupoAtend) > 0) {
                $idsGruposAtend = explode(',', $qrDoutoresGrupoAtend[0]->idsGruposAtend);
            }
        }

        $qrGrupoAtend = $GrupoAtendimentoRepository->getAll($idDominio);
        if (count($qrGrupoAtend) > 0) {
            foreach ($qrGrupoAtend as $rowGr) {
                $retorno['grupoAtendimento'][] = [
                    'id' => $rowGr->id,
                    'nome' => utf8_decode($rowGr->nome),
                    'disabled' => (!in_array($rowGr->id, $idsGruposAtend)) ? true : false,
                ];
            }
        }



//FORMAÇÕES
//        DoutorFormacaoRepository
        $DoutorFormacaoRepository = new DoutorFormacaoRepository;
        $idsFormacoes = [];
        if (count($idsDoutores) > 0) {
            $qrDoutoresFormacoes = $DoutorFormacaoRepository->getDoutoresFiltro($idDominio, $idsDoutores, true);
            if (count($qrDoutoresFormacoes) > 0) {
                $idsFormacoes = explode(',', $qrDoutoresFormacoes[0]->nomesFormacao);
            }
        }
        $qrFormacoes = $DoutorFormacaoRepository->getAll($idDominio);
        if (count($qrFormacoes) > 0) {
            foreach ($qrFormacoes as $rowForm) {

                if (in_array($rowForm->nome_formacao, $idsFormacoes)) {
                    $retorno['formacaoAcademica'][] = [
                        'nome' => utf8_decode($rowForm->nome_formacao),
                    ];
                }
            }
        }

//
//
//        $resultFormacaoAc = $this->doutorFormacaoService->getAll($idDominio, null);
//        if ($resultFormacaoAc['success']) {
//            $retorno['formacaoAcademica'] = $resultFormacaoAc['data'];
//        }
//
//IDIOMA
        $IdiomaClinicaRepository = new IdiomaClinicaRepository;
        $idsIdiomas = [];
        if (count($idsDoutores) > 0) {
            $qrDoutoresIdiomas = $IdiomaClinicaRepository->getDoutoresFiltro($idDominio, $idsDoutores, true);

            if (count($qrDoutoresIdiomas) > 0) {
                $idsIdiomas = explode(',', $qrDoutoresIdiomas[0]->idsIdiomas);
            }
        }


        $qrIdiomas = $IdiomaClinicaRepository->getAll($idDominio);
        if (count($qrIdiomas) > 0) {
            foreach ($qrIdiomas as $rowIdioma) {
                $retorno['idiomas'][] = [
                    'id' => $rowIdioma->id,
                    'nome' => utf8_decode($rowIdioma->nome),
                    'disabled' => (!in_array($rowIdioma->id, $idsIdiomas)) ? true : false,
                ];
            }
        }


        return $this->returnSuccess($retorno, '');
    }

    public function getConveniosDoutores($idDominio, $idDoutor, $dadosFiltro = null) {



        $ConvenioRepository = new ConvenioRepository();
        $qr = $ConvenioRepository->getAllConveniosDoutores($idDominio, $idDoutor, $dadosFiltro);

        if ($qr) {
            $retorno = [];
            foreach ($qr as $row) {
                $retorno[] = [
                    'idConvDout' => $row->id,
                    'nome' => $row->nomeConvenio,
                    'conveniosId' => $row->convenios_id,
                    'conveniosTipoCodigoOperadoraId' => $row->convenios_tipo_codigo_operadora_id,
                    'codigoOperadora' => $row->codigo_operadora,
                ];
            }

            return $this->returnSuccess($retorno);
        } else {
            return $this->returnError('', 'Nenhum convênio encontrado');
        }
    }

    //PRIVATE
    private function validateStoreUpdate($idDominio, $dadosInput) {

        $arrayTitulos = [1, 2, 3, 4, 5, 6];
        $arraySexo = ['M', 'F', 'O'];
        $arrayTipoContrato = [1, 2];
        $arrayTipoFormacao = ['Curso', 'Faculdade', 'Pós-graduação', 'Mestrado', 'Doutorado'];
        $EspecialidadeRepository = new EspecialidadeRepository;
        $ConselhosProfissionaisRepository = new ConselhosProfissionaisRepository;
        $UfRepository = new UfRepository;
        $CboRepository = new CboRepository;

        if (isset($dadosInput['titulo']) and !empty($dadosInput['titulo']) and !in_array($dadosInput['titulo'], $arrayTitulos)) {
            return $this->returnError(null, "Titulo inválido");
        }
        if (isset($dadosInput['sexo']) and !empty($dadosInput['sexo']) and !in_array($dadosInput['sexo'], $arraySexo)) {
            return $this->returnError(null, "Sexo inválido");
        }
        if (isset($dadosInput['email']) and !empty($dadosInput['email']) and $this->doutoresRepository->isExistsEmail($idDominio, $dadosInput['email'])) {
            return $this->returnError(null, "E-mail já existe");
        }
        if (isset($dadosInput['tipoContrato']) and !empty($dadosInput['tipoContrato']) and !in_array($dadosInput['tipoContrato'], $arrayTipoContrato)) {
            return $this->returnError(null, "Tipo de contrato inválido");
        }

        if (isset($dadosInput['cpf']) and !empty($dadosInput['cpf']) and !Functions::validateCPF($dadosInput['cpf'])) {
            return $this->returnError(null, "CPF inválido");
        }
        if (isset($dadosInput['cnpj']) and !empty($dadosInput['cnpj']) and !Functions::validateCNPJ($dadosInput['cnpj'])) {
            return $this->returnError(null, "CNPJ inválido");
        }
        if (isset($dadosInput['conselhoProfissionalId']) and !empty($dadosInput['conselhoProfissionalId']) and !$ConselhosProfissionaisRepository->getById($dadosInput['conselhoProfissionalId'])) {
            return $this->returnError(null, "Id do conselho profissional inválido");
        }

        if (isset($dadosInput['conselhoProfissionalUf']) and !empty($dadosInput['conselhoProfissionalUf']) and empty($UfRepository->getBySigla($dadosInput['conselhoProfissionalUf']))) {
            return $this->returnError(null, "Uf do conselho profissional inválido");
        }

        if (isset($dadosInput['cboId']) and !empty($dadosInput['cboId']) and empty($CboRepository->getById($dadosInput['cboId']))) {
            return $this->returnError(null, "Cbo inválido");
        }
        if (isset($dadosInput['tipoRepasse']) and !empty($dadosInput['tipoRepasse']) and ($dadosInput['tipoRepasse'] != 1 and $dadosInput['tipoRepasse'] != 2)) {
            return $this->returnError(null, "Tipo de repasse invalido");
        }
        if (isset($dadosInput['uf']) and !empty($dadosInput['uf']) and empty($UfRepository->getBySigla($dadosInput['uf']))) {

            return $this->returnError(null, "Uf inválido");
        }
        if (isset($dadosInput['grupoAtendimentoId']) and count($dadosInput['grupoAtendimentoId']) > 0) {
            foreach ($dadosInput['grupoAtendimentoId'] as $chave => $idG) {
                $GrupoAtendimentoRepository = new GrupoAtendimentoRepository;
                $qr = $GrupoAtendimentoRepository->getById($idDominio, $idG);

                if (!$qr) {
                    return $this->returnError(null, 'grupoAtendimentoId[' . $chave . ']: Id do grupo de atendimento inválido');
                }
            }
        }
        if (isset($dadosInput['idiomasId']) and count($dadosInput['idiomasId']) > 0) {
            foreach ($dadosInput['idiomasId'] as $chave => $idG) {
                $IdiomaClinicaRepository = new IdiomaClinicaRepository;
                $qr = $IdiomaClinicaRepository->getById($idG);
                if (!$qr) {
                    return $this->returnError(null, 'idiomasId[' . $chave . ']: Id do idioma inválido');
                }
            }
        }

        if (isset($dadosInput['formAcademicaTipo']) and count($dadosInput['formAcademicaTipo']) > 0) {
            foreach ($dadosInput['formAcademicaTipo'] as $chave => $tipoform) {
                if (!in_array($tipoform, $arrayTipoFormacao)) {
                    return $this->returnError(null, 'formAcademicaTipo[' . $chave . ']: Tipo formação acadêmica inválido');
                }
                if (isset($dadosInput['formAcademicaDtDe'][$chave]) and !empty($dadosInput['formAcademicaDtDe'][$chave])) {
                    $data = DateTime::createFromFormat('Y-m-d', $dadosInput['formAcademicaDtDe'][$chave]);
                    if (!$data) {
                        return $this->returnError(null, 'formAcademicaDtDe[' . $chave . ']: Data inválida');
                    }
                }
                if (isset($dadosInput['formAcademicaDtAte'][$chave]) and !empty($dadosInput['formAcademicaDtAte'][$chave])) {
                    $data = DateTime::createFromFormat('Y-m-d', $dadosInput['formAcademicaDtAte'][$chave]);
                    if (!$data) {
                        return $this->returnError(null, 'formAcademicaDtAte[' . $chave . ']: Data inválida');
                    }
                }
            }
        }



        $dadosInsert['nome_cript'] = $dadosInput['nome'];
        $dadosInsert['matricula_externa'] = (isset($dadosInput['matriculaExterna']) and !empty($dadosInput['matriculaExterna']) ) ? $dadosInput['matriculaExterna'] : null;
        $dadosInsert['pronome_id'] = (isset($dadosInput['titulo']) and !empty($dadosInput['titulo']) ) ? $dadosInput['titulo'] : null;
        $dadosInsert['sexo'] = (isset($dadosInput['sexo']) and !empty($dadosInput['sexo']) ) ? $dadosInput['sexo'] : null;
        $dadosInsert['dt_nascimento'] = (isset($dadosInput['dataNascimento']) and !empty($dadosInput['dataNascimento']) ) ? $dadosInput['dataNascimento'] : null;
        $dadosInsert['email_cript'] = (isset($dadosInput['email']) and !empty($dadosInput['email']) ) ? $dadosInput['email'] : null;
        $dadosInsert['telefone_cript'] = (isset($dadosInput['telefone']) and !empty($dadosInput['telefone']) ) ? $dadosInput['telefone'] : null;
        $dadosInsert['celular1_cript'] = (isset($dadosInput['celular']) and !empty($dadosInput['celular']) ) ? $dadosInput['celular'] : null;
        $dadosInsert['celular2_cript'] = (isset($dadosInput['celular2']) and !empty($dadosInput['celular2']) ) ? $dadosInput['celular2'] : null;
        $dadosInsert['mensagem_depois_marcar'] = (isset($dadosInput['msgPosMarcacao']) and !empty($dadosInput['msgPosMarcacao']) ) ? $dadosInput['msgPosMarcacao'] : null;
        $dadosInsert['tipo_contrato'] = (isset($dadosInput['tipoContrato']) and !empty($dadosInput['tipoContrato']) ) ? $dadosInput['tipoContrato'] : null;
        $dadosInsert['cpf_cript'] = (isset($dadosInput['cpf']) and !empty($dadosInput['cpf']) ) ? $dadosInput['cpf'] : null;
        $dadosInsert['cnpj_cript'] = (isset($dadosInput['cnpj']) and !empty($dadosInput['cnpj'])) ? $dadosInput['cnpj'] : null;
        $dadosInsert['cns_cript'] = (isset($dadosInput['cns']) and !empty($dadosInput['cns'])) ? $dadosInput['cns'] : null;
        $dadosInsert['website'] = (isset($dadosInput['website']) and !empty($dadosInput['website']) ) ? $dadosInput['website'] : null;
        $dadosInsert['dt_ini_atividade_prof'] = (isset($dadosInput['dataIniProf']) and !empty($dadosInput['dataIniProf']) ) ? $dadosInput['dataIniProf'] : null;
        $dadosInsert['observacoes'] = (isset($dadosInput['observacoes']) and !empty($dadosInput['observacoes']) ) ? $dadosInput['observacoes'] : null;
        $dadosInsert['conselho_profissional_id'] = (isset($dadosInput['conselhoProfissionalId'])) ? $dadosInput['conselhoProfissionalId'] : null;
        $dadosInsert['sobre'] = (isset($dadosInput['sobre'])) ? $dadosInput['sobre'] : null;

        if (isset($dadosInput['conselhoProfissionalUf']) and !empty($dadosInput['conselhoProfissionalUf'])) {
            $ufConselhoId = $UfRepository->getBySigla($dadosInput['conselhoProfissionalUf']);
            $dadosInsert['conselho_uf_id'] = $ufConselhoId->cd_uf;
        }


        if (isset($dadosInput['cboId']) and !empty($dadosInput['cboId'])) {
            $RowCbo = $CboRepository->getById($dadosInput['cboId']);
            $dadosInsert['cbo_s_id'] = $RowCbo->id;
        }


        $dadosInsert['conselho_profissional_numero'] = (isset($dadosInput['conselhoProfissionalNumero']) and !empty($dadosInput['conselhoProfissionalNumero']) ) ? $dadosInput['conselhoProfissionalNumero'] : null;
        $dadosInsert['conselho_profissional_numero_cript'] = (isset($dadosInput['conselhoProfissionalNumero']) and !empty($dadosInput['conselhoProfissionalNumero']) ) ? $dadosInput['conselhoProfissionalNumero'] : null;

        $dadosInsert['possui_repasse'] = (isset($dadosInput['possuiRepasse']) and $dadosInput['possuiRepasse'] == 1) ? 1 : 0;
        $dadosInsert['tipo_repasse'] = (isset($dadosInput['tipoRepasse'])) ? $dadosInput['tipoRepasse'] : null;
        $dadosInsert['valor_repasse'] = (isset($dadosInput['valorRepasse'])) ? $dadosInput['valorRepasse'] : null;
        $dadosInsert['possui_videoconf'] = (isset($dadosInput['possuiVideoConsulta'])) ? $dadosInput['possuiVideoConsulta'] : 0;
        $dadosInsert['somente_videoconf'] = (isset($dadosInput['tipoAtendimento'])) ? $dadosInput['tipoAtendimento'] : null;
        $dadosInsert['banco1_cript'] = (isset($dadosInput['bancoNome'])) ? $dadosInput['bancoNome'] : null;
        $dadosInsert['agencia1_cript'] = (isset($dadosInput['bancoAgencia'])) ? $dadosInput['bancoAgencia'] : null;
        $dadosInsert['conta1_cript'] = (isset($dadosInput['bancoConta'])) ? $dadosInput['bancoConta'] : null;
        $dadosInsert['pix1'] = (isset($dadosInput['bancoPix'])) ? $dadosInput['bancoPix'] : null;
        $dadosInsert['proc_doutor_id_video'] = (isset($dadosInput['procVideoId'])) ? $dadosInput['procVideoId'] : null;
        $dadosInsert['proc_doutor_id_presencial'] = (isset($dadosInput['procPadraoPresencial'])) ? $dadosInput['procPadraoPresencial'] : null;

        ////TAGS DE TRATAMENTO
        $tagsTratamento = null;
        if (isset($dadosInput['tagsTratamento']) and count($dadosInput['tagsTratamento']) > 0) {
            foreach ($dadosInput['tagsTratamento'] as $tag) {
                $tagsTratamento[] = $tag;
            }
            $dadosInsert['tags_tratamentos'] = json_encode($tagsTratamento);
        }


        return $dadosInsert;
    }

    private function getDadosJSONAprovacaoInput($idDominio, $dadosInsert, $dadosInput) {


        $arrayCampos = [
            'nome_cript' => 'nome',
            'email_cript' => 'email',
            'telefone_cript' => 'telefone',
            'telefone_cript' => 'telefone',
            'mensagem_depois_marcar' => 'mensagemPosmarcacaoConsulta',
            'sobre' => 'sobre',
            'website' => 'website',
            'celular1_cript' => 'celular1',
            'celula2_cript' => 'celular2',
            'tipo_contrato' => 'tipo_contrato',
            'cpf_cript' => 'cpf',
            'cns_cript' => 'cns',
            'cbo_s_id' => 'cboSId',
            'banco1_cript' => 'banco1',
            'agencia1_cript' => 'agencia1',
            'conta1_cript' => 'conta1',
            'pix1' => 'pix1',
            'sexo' => 'sexo',
            'conselho_profissional_id' => 'conselhoProfissionalId',
            'conselho_uf_id' => 'conselhoProfissionalUfId',
            'conselho_profissional_numero_cript' => 'conselhoProfissionalNumero',
            'cnpj_cript' => 'cnpj',
            'possui_repasse' => 'possuiRepasse',
            'tipo_repasse' => 'tipoRepasse',
            'valor_repasse' => 'valorRepasse',
            'possui_videoconf' => 'possuiVideoconf',
            'matricula_externa' => 'matriculaExterna',
            'proc_doutor_id_video' => 'proc_doutor_id_video',
            'proc_doutor_id_presencial' => 'procPresencialId',
            'dt_ini_atividade_prof' => 'dt_ini_atividade_prof',
            'observacoes' => 'observacoes',
            'dt_nascimento' => 'dt_nascimento',
            'cep' => 'cep',
            'logradouro' => 'logradouro',
            'numero' => 'numero',
            'complemento' => 'complemento',
            'bairro' => 'bairro',
            'cidade' => 'cidade',
            'uf' => 'enderecoUfId',
        ];

        $JSON_APROVACAO = null;

        foreach ($arrayCampos as $chave => $valor) {

            if (isset($dadosInsert[$chave]) and !empty($dadosInsert[$chave])) {
                $JSON_APROVACAO[$valor] = [
                    'alterado' => true,
                    'valor' => $dadosInsert[$chave]
                ];
            }
            
            
            if (isset($dadosInput[$chave]) and !empty($dadosInput[$chave])) {

                switch ($chave) {
                    case 'uf':
                        $UfRepository = new UfRepository;
                        $valorJ = $UfRepository->getBySigla($dadosInput[$chave])->cd_uf;
                        break;
                    default:
                        $valorJ = $dadosInput[$chave];
                        break;
                }
                $JSON_APROVACAO[$valor] = [
                    'alterado' => true,
                    'valor' => $valorJ
                ];
            }
        }




        //Grupo de atendimento
        $JSON_APROVACAO['grupoAtendimento'] = [
            'alterado' => true,
            'adicionar' => null,
            'excluir' => null
        ];
        if (isset($dadosInput['grupoAtendimentoId']) and count($dadosInput['grupoAtendimentoId']) > 0) {
            foreach ($dadosInput['grupoAtendimentoId'] as $idGrupo) {
                $JSON_APROVACAO['grupoAtendimento']['adicionar'][] = [
                    'alterado' => true,
                    'id' => $idGrupo
                ];
            }
        }
        //Idiomas
        $JSON_APROVACAO['idiomas'] = [
            'alterado' => true,
            'adicionar' => null,
            'excluir' => null
        ];
        if (isset($dadosInput['idiomasId']) and count($dadosInput['idiomasId']) > 0) {
            foreach ($dadosInput['idiomasId'] as $idIdioma) {
                $JSON_APROVACAO['idiomas']['adicionar'][] = $idIdioma;
            }
        }

        //Formacoes
        $JSON_APROVACAO['formacoes'] = [
            'alterado' => true,
            'adicionar' => null,
            'excluir' => null
        ];
        if (isset($dadosInput['formAcademicaTipo']) and count($dadosInput['formAcademicaTipo']) > 0) {
            foreach ($dadosInput['formAcademicaTipo'] as $chave => $tipoFormacao) {
                $JSON_APROVACAO['formacoes']['adicionar'][] = [
                    'alterado' => true,
                    'tipoFormacao' => $tipoFormacao,
                    'instituicaoEnsino' => (isset($dadosInput['formAcademicaInst'][$chave])) ? $dadosInput['formAcademicaInst'][$chave] : '',
                    'nomeFormacao' => (isset($dadosInput['formAcademicaFormacao'][$chave])) ? $dadosInput['formAcademicaFormacao'][$chave] : '',
                    'periodoDe' => (isset($dadosInput['formAcademicaDtDe'][$chave])) ? Functions::dateDbToBr($dadosInput['formAcademicaDtDe'][$chave]) : '',
                    'periodoAte' => (isset($dadosInput['formAcademicaDtAte'][$chave])) ? Functions::dateDbToBr($dadosInput['formAcademicaDtAte'][$chave]) : '',
                    'id' => null,
                ];
            }
        }


        //Especialidade
        $JSON_APROVACAO['especialidadesLista'] = [
            'alterado' => true,
            'adicionar' => null,
            'excluir' => null
        ];

        if (isset($dadosInput['especialidadeNome']) and count($dadosInput['especialidadeNome']) > 0) {
            $EspecialidadeRepository = new EspecialidadeRepository;
            foreach ($dadosInput['especialidadeNome'] as $rowNomeEsp) {
                $qrEsp = $EspecialidadeRepository->findByName($idDominio, $rowNomeEsp);
                $especialidadeID = null;
                $outraEsp = null;
                if (!$qrEsp) {
                    $outraEsp = $rowNomeEsp;
                } else {
                    $especialidadeID = $qrEsp->id;
                }
                $JSON_APROVACAO['especialidadesLista']['adicionar'][] = [
                    'alterado' => true,
                    'id' => '',
                    'especialidadeId' => $especialidadeID,
                    'outra_especialidade' => $outraEsp,
                ];
            }
        }

        $JSON_APROVACAO['tagsTratamento'] = [
            'alterado' => true,
            'valor' => null
        ];
        //tags de tratameto
        if (isset($dadosInput['tagsTratamento']) and count($dadosInput['tagsTratamento']) > 0) {
            foreach ($dadosInput['tagsTratamento'] as $tag) {
                $JSON_APROVACAO['tagsTratamento']['valor'][] = $tag;
            }
        }

//         dd($JSON_APROVACAO);
        return json_encode($JSON_APROVACAO);
    }

    private function insertEndereco($idDominio, $idDoutor, $dadosInput) {

        $UfRepository = new UfRepository;
        $dadosEndereco = [];
        if (isset($dadosInput['cep']) and !empty($dadosInput['cep'])) {
            $dadosEndereco['cep'] = $dadosInput['cep'];
        }
        if (isset($dadosInput['logradouro']) and !empty($dadosInput['logradouro'])) {
            $dadosEndereco['logradouro'] = $dadosInput['logradouro'];
        }
        if (isset($dadosInput['numero']) and !empty($dadosInput['numero'])) {
            $dadosEndereco['numero'] = $dadosInput['numero'];
        }
        if (isset($dadosInput['complemento']) and !empty($dadosInput['complemento'])) {
            $dadosEndereco['complemento'] = $dadosInput['complemento'];
        }
        if (isset($dadosInput['bairro']) and !empty($dadosInput['bairro'])) {
            $dadosEndereco['bairro'] = $dadosInput['bairro'];
        }
        if (isset($dadosInput['cidade']) and !empty($dadosInput['cidade'])) {
            $dadosEndereco['cidade'] = $dadosInput['cidade'];
        }
        if (isset($dadosInput['estado']) and !empty($dadosInput['estado'])) {
            $dadosEndereco['estado'] = $dadosInput['estado'];
        }
        if (isset($dadosInput['uf']) and !empty($dadosInput['uf'])) {
            $ufId = $UfRepository->getBySigla($dadosInput['uf']);
            $dadosEndereco['uf_id'] = $ufId->cd_uf;
            $dadosEndereco['estado'] = $ufId->ds_uf_nome;
        }

        if (count($dadosEndereco) > 0) {
            $this->doutoresRepository->storeDadosEndereco($idDominio, $idDoutor, $dadosEndereco);
        }
    }
}
