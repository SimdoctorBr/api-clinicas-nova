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
        if (isset($row->nomeEspecialidade) and!empty($row->nomeEspecialidade)) {
            $retorno['especialidades'][] = array('nome' => $row->nomeEspecialidade);
        } elseif (isset($row->outra_especialidade) and!empty($row->outra_especialidade)) {
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

        if (isset($row->tags_tratamentos) and!empty($row->tags_tratamentos)) {

            $tagsTrat = json_decode($row->tags_tratamentos);

            $retorno['tagsTratamentos'] = $tagsTrat;
        } else {
            $retorno['tagsTratamentos'] = null;
        }


        $retorno['sobre'] = $row->sobre;
        $retorno['pontuacao'] = $row->pontuacao;
        $retorno['perfilId'] = $row->identificador;

        $retorno['favoritoPaciente'] = (isset($row->favoritoPaciente) and $row->favoritoPaciente > 0) ? true : false;

        $retorno['conselho'] = [
            'nome' => (!empty($row->nomeConselhoProfissional)) ? $row->nomeConselhoProfissional : null,
            'sigla' => (!empty($row->codigoConselhoProfisssional)) ? $row->codigoConselhoProfisssional : null,
            'numero' => (!empty($row->conselho_profissional_numero)) ? $row->conselho_profissional_numero : null,
            'uf' => (!empty($row->siglaUFConselhoProfisional)) ? $row->siglaUFConselhoProfisional : null,
            'codCBO' => (!empty($row->codigoCBO)) ? $row->codigoCBO : null,
            'nomeCBO' => (!empty($row->nomeCBO)) ? $row->nomeCBO : null,
        ];

        $retorno['procedimentoPadrao'] = null;
        if (isset($row->proc_doutor_id_presencial) and!empty($row->proc_doutor_id_presencial)) {
            $retorno['procedimentoPadrao'] = [
                'idProcedimentoDoutor' => $row->proc_doutor_id_presencial,
                'idProcedimento' => $row->procPadraoIdProcedimento,
                'nomeProcedimento' => $row->procPadraoNome,
                'convenio' => ['id' => $row->procPadraoIdConvenio,
                    'nome' => $row->procPadraoNomeConvenio,
                ],
                'valor' => $row->procPadraoValor,
            ];
        }

        return $retorno;
    }

    public function getAll($idDominio, $dadosFiltro = null, $page = 1, $perPage = 100) {



        $filtroOrderBy = null;
        if (isset($dadosFiltro['orderBy']) and!empty($dadosFiltro['orderBy'])) {
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


        $retorno = ['precoVideo' => ['min', 'max'], 'precoPresencial' => ['min', 'max'], 'especialidades' => [], 'grupoAtendimento' => [], 'formacaoAcademica' => []];

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
//        dd($resultSexo);

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

//           
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

}
