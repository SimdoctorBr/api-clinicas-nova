<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\TuoTempo\Clinicas;

use App\Services\BaseService;
use App\Repositories\Clinicas\Paciente\PacienteRepository;
use App\Repositories\Clinicas\Paciente\PacienteFotosRepository;
use App\Repositories\Clinicas\Paciente\PacienteArquivoRepository;
use App\Repositories\Clinicas\PacienteResultadoExameRepository;
use App\Repositories\Clinicas\Paciente\PacienteLaudoRepository;
use App\Repositories\Gerenciamento\DominioRepository;
use App\Services\TuoTempo\Clinicas\CryptService;
use App\Helpers\Functions;

/**
 * Description of Activities
 *
 * @author ander
 */
class PacienteService extends BaseService {

    private $pacienteRepository;
    private $dominioRepository;

    public function __construct(PacienteRepository $PacRep, DominioRepository $domRep) {
        $this->pacienteRepository = $PacRep;
        $this->dominioRepository = $domRep;
    }

    public function filters($request) {

        $dadosFiltro = null;
        $arraySexo = array('M' => 'Masculino', 'F' => 'Feminino');

        if ($request->has('FIRST_NAME') and!empty($request->get('FIRST_NAME'))) {
            $dadosFiltro['nome'] = trim(addslashes($request->get('FIRST_NAME')));
        }
        if ($request->has('SECOND_NAME') and!empty($request->get('SECOND_NAME'))) {
            $dadosFiltro['sobrenome'] = trim(addslashes($request->get('SECOND_NAME')));
        }
        if ($request->has('THIRD_NAME') and!empty($request->get('THIRD_NAME'))) {
//            $dadosFiltro['sobrenome'] = $request->get('THIRD_NAME');
        }
        if ($request->has('USER_LID') and!empty($request->get('USER_LID'))) {
            $dadosFiltro['id'] = trim(addslashes($request->get('USER_LID')));
        }
        if ($request->has('GENDER') and!empty($request->get('GENDER'))) {
            $dadosFiltro['sexo'] = Functions::siglaSexoToNome($request->get('GENDER'));
        }
        if ($request->has('ID_NUMBER') and!empty($request->get('ID_NUMBER'))) {
            $dadosFiltro['cpf'] = $request->get('ID_NUMBER');
        }
        if ($request->has('ID_TYPE') and!empty($request->get('ID_TYPE'))) {
//            $dadosFiltro['rg'] = $arraySexo[$request->get('ID_NUMBER')];
        }
        if ($request->has('MOBILE_PHONE') and!empty($request->get('MOBILE_PHONE'))) {
            $dadosFiltro['celular'] = trim(addslashes($request->get('MOBILE_PHONE')));
        }
        if ($request->has('EMAIL') and!empty($request->get('EMAIL'))) {
            $dadosFiltro['email'] = trim(addslashes($request->get('EMAIL')));
        }
        if ($request->has('DATE_OF_BIRTH') and!empty($request->get('DATE_OF_BIRTH'))) {
            $dadosFiltro['data_nascimento'] = Functions::dateBrToDB(($request->get('DATE_OF_BIRTH')));
        }
        if ($request->has('BIRTHDAY_MIN') and!empty($request->get('BIRTHDAY_MIN'))) {
            $dadosFiltro['data_nascimento_min'] = Functions::dateTuotempoToDB($request->get('BIRTHDAY_MIN'));
        }
        if ($request->has('BIRTHDAY_MAX') and!empty($request->get('BIRTHDAY_MAX'))) {
            $dadosFiltro['data_nascimento_max'] = Functions::dateTuotempoToDB($request->get('BIRTHDAY_MAX'));
        }
        if ($request->has('LAST_REQUEST_TIMESTAMP') and!empty($request->get('LAST_REQUEST_TIMESTAMP'))) {
            $dadosFiltro['ultima_alteracao'] = trim(addslashes($request->get('LAST_REQUEST_TIMESTAMP')));
        }
        if ($request->has('EPISODE_LID') and!empty($request->get('EPISODE_LID'))) {
//            $dadosFiltro['data_nascimento_max'] = trim(addslashes($request->get('LAST_REQUEST_TIMESTAMP')));
        }

        return $dadosFiltro;
    }

    public function getAll($request) {


//        if (!$request->has('LOCATION_LID') or empty($request->get('LOCATION_LID'))) {
//            return $this->returnError(null, "uninformed LOCATION_LID ");
//        }
        $dadosFiltro = $this->filters($request);

        $idDoutor = str_replace('dout', '', $request->get('RESOURCE_LID'));
        $convenioId = !empty($request->get('INSURANCE_LID')) ? $request->get('INSURANCE_LID') : null;

        $qrDominios = $this->dominioRepository->getAllByUser(auth()->user()->id);
//        if ($request->has('LOCATION_LID')) {
//            $idsDominio = $request->get('LOCATION_LID');
//        } else {
        $idsDominio = array_map(function($item) {
            return $item->id;
        }, $qrDominios);
//        }

        $dadosPaginacao = $this->getPaginate($request);
        $qrPacientes = $this->pacienteRepository->getAll($idsDominio, $dadosFiltro, $dadosPaginacao['page'], $dadosPaginacao['perPage']);

        if (count($qrPacientes) > 0) {
            $PACIENTE = array();
            $arraySexo = array('Masculino' => 'M', 'Feminino' => 'F');
            foreach ($qrPacientes['RESULTS'] as $chave => $row) {
                $PACIENTE[$chave]['USER_LID'] = $row->id;
                $PACIENTE[$chave]['FIRST_NAME'] = $row->nome;
                $PACIENTE[$chave]['SECOND_NAME'] = $row->sobrenome;
                $PACIENTE[$chave]['THIRD_NAME'] = null;
                $PACIENTE[$chave]['DATE_OF_BIRTH'] = trim($row->data_nascimento);
                $PACIENTE[$chave]['PLACE_OF_BIRTH'] = null;
                $PACIENTE[$chave]['ID_NUMBER'] = str_replace('.', '', str_replace('-', '', $row->cpf));
                $PACIENTE[$chave]['ID_TYPE'] = 'CPF';
                $PACIENTE[$chave]['GENDER'] = (!empty(Functions::sexoToSigla($row->sexo))) ? Functions::sexoToSigla($row->sexo) : null;
                $PACIENTE[$chave]['ZIP_CODE'] = str_replace('-', '', str_replace('.', '', $row->cep));
                $PACIENTE[$chave]['LANGUAGE'] = 'pt-br';
                $PACIENTE[$chave]['MOBILE_PHONE'] = str_replace('-', '', str_replace('.', '', trim($row->celular)));
                $PACIENTE[$chave]['LANDLINE_PHONE'] = str_replace('-', '', str_replace('.', '', trim($row->telefone)));
                $PACIENTE[$chave]['WORK_PHONE'] = null;
                $PACIENTE[$chave]['EMAIL'] = $row->email;
                $PACIENTE[$chave]['PRIVACY'] = ($row->permitir_dados_tuotempo == 1) ? 1 : 0;  //incluir no cadastro do paciente
                $PACIENTE[$chave]['PRIVACY_PROMOTIONS'] = $row->envia_email;
                $PACIENTE[$chave]['PRIVACY_DIGITAL_RESULTS'] = ($row->permite_prontuario_tuotempo == 1) ? 1 : 0; //incluir no cadastro do paciente ( acesso aos prontuarios
                $PACIENTE[$chave]['CERTIFIED'] = 0;
                $PACIENTE[$chave]['ERROR_MESSAGE'] = null;
            }

            $RESULTS = $qrPacientes;
            $RESULTS['RESULTS'] = $PACIENTE;
            return $this->returnSuccess($RESULTS, null);
        } else {
            return $this->returnError(null, "No users found");
        }
    }

    public function getEpisodes($request) {

        $PacienteFotosRep = new PacienteFotosRepository();
        $PacienteArquivosRep = new PacienteArquivoRepository();
        $PacienteResultadoExamRep = new PacienteResultadoExameRepository();
        $PacienteLaudoRep = new PacienteLaudoRepository();


        $qrDominios = $this->dominioRepository->getAllByUser(auth()->user()->id);
        $idsDominio = array_map(function($item) {
            return $item->id;
        }, $qrDominios);


        $dadosFiltro = null;
        $dadosFiltro['habilitado_paciente'] = 1;
        if ($request->has('START_DATE') and!empty($request->get('START_DATE'))) {
            $dadosFiltro['dataIniStart'] = Functions::dateBrToDB($request->get('START_DATE'));
        }

        $idPaciente = $request->get('USER_LID');
        $episodeId = $request->get('EPISODE_LID');
        $episodeId = explode('_', $episodeId);


        if (!empty($request->get('EPISODE_LID')) and count($episodeId) != 2) {
            return $this->returnError(['error' => 'Episode not found']);
        }

        switch ($episodeId[0]) {
            case 'arq':
                $dadosFiltro['id'] = $episodeId[1];
                $qrArquivos = $PacienteArquivosRep->getAll($idsDominio, $idPaciente, $dadosFiltro);

                break;
            case 'resexame':
                $dadosFiltro['id'] = $episodeId[1];
                $qrResultExames = $PacienteResultadoExamRep->getAll($idsDominio, $idPaciente, $dadosFiltro);
                break;
            case 'laud':
                $dadosFiltro['id'] = $episodeId[1];
                $qrLaudos = $PacienteLaudoRep->getAll($idsDominio, $idPaciente, $dadosFiltro);
                break;

            default:
//                $qr = $PacienteFotosRep->getAll($idsDominio, $idPaciente, $dadosFiltro);
                $qrArquivos = $PacienteArquivosRep->getAll($idsDominio, $idPaciente, $dadosFiltro);
                $qrResultExames = $PacienteResultadoExamRep->getAll($idsDominio, $idPaciente, $dadosFiltro);
                $qrLaudos = $PacienteLaudoRep->getAll($idsDominio, $idPaciente, $dadosFiltro);
//        dd($qr);

                break;
        }
        $RESULTS = array();
        $countI = 0;

        if (isset($qrArquivos) and count($qrArquivos) > 0) {
            foreach ($qrArquivos as $rowArq) {

                $RESULTS[$countI]['EPISODE_LID'] = 'arq_' . $rowArq->id;
                $RESULTS[$countI]['CATEGORY'] = 'Arquivos';
                $RESULTS[$countI]['STATUS'] = 'Valid';
                $RESULTS[$countI]['DATE'] = (!empty($rowArq->data_cad)) ? $rowArq->data_cad : null;
                $RESULTS[$countI]['TITLE'] = (!empty($rowArq->title)) ? utf8_decode($rowArq->title) : $rowArq->arquivo;
                $RESULTS[$countI]['DESCRIPTION'] = (!empty($rowArq->title)) ? utf8_decode($rowArq->title): $rowArq->arquivo;
                $RESULTS[$countI]['PRICE'] = null;
                $RESULTS[$countI]['USER_LID'] = $rowArq->pacientes_id;
                $RESULTS[$countI]['USER_MOBILE_PHONE'] = $rowArq->celular;
                $RESULTS[$countI]['USER_EMAIL'] = $rowArq->email;
                $RESULTS[$countI]['USER_ID_NUMBER'] = (!empty($rowArq->cpf)) ? $rowArq->cpf : null;
                $RESULTS[$countI]['USER_ID_TYPE'] = 'CPF';
                $RESULTS[$countI]['USER_GENDER'] = (!empty(Functions::sexoToSigla($rowArq->sexo))) ? Functions::sexoToSigla($rowArq->sexo) : null;
                $RESULTS[$countI]['USER_DATE_OF_BIRTH'] = (!empty($rowArq->data_nascimento)) ? $rowArq->data_nascimento : null;
                $RESULTS[$countI]['USER_PRIVACY'] = $rowArq->permitir_dados_tuotempo;
                $RESULTS[$countI]['USER_PRIVACY_DIGITAL_RESULTS'] = $rowArq->permite_prontuario_tuotempo;
                $RESULTS[$countI]['USER_FIRST_NAME'] = $rowArq->nomePaciente;
                $RESULTS[$countI]['USER_SECOND_NAME'] = $rowArq->sobrenomePaciente;
                $RESULTS[$countI]['USER_THIRD_NAME'] = '';
                $RESULTS[$countI]['APP_LID'] = $rowArq->consultas_id;
                $countI++;
            }
        }

        if (isset($qrResultExames) and count($qrResultExames) > 0) {
            foreach ($qrResultExames as $rowArq) {
                $RESULTS[$countI]['EPISODE_LID'] = 'resexame_' . $rowArq->id;
                $RESULTS[$countI]['CATEGORY'] = 'Resultado de exames';
                $RESULTS[$countI]['STATUS'] = null;
                $RESULTS[$countI]['DATE'] = (!empty($rowArq->data_cad)) ? $rowArq->data_cad : null;
                $RESULTS[$countI]['TITLE'] = (!empty($rowArq->title)) ? utf8_decode($rowArq->title): $rowArq->arquivo;
                $RESULTS[$countI]['DESCRIPTION'] = (!empty($rowArq->title)) ? utf8_decode($rowArq->title) : $rowArq->arquivo;
                $RESULTS[$countI]['PRICE'] = null;
                $RESULTS[$countI]['USER_LID'] = $rowArq->pacientes_id;
                $RESULTS[$countI]['USER_MOBILE_PHONE'] = $rowArq->celular;
                $RESULTS[$countI]['USER_EMAIL'] = $rowArq->email;
                $RESULTS[$countI]['USER_ID_NUMBER'] = (!empty($rowArq->cpf)) ? $rowArq->cpf : null;
                $RESULTS[$countI]['USER_ID_TYPE'] = 'CPF';
                $RESULTS[$countI]['USER_GENDER'] = (!empty(Functions::sexoToSigla($rowArq->sexo))) ? Functions::sexoToSigla($rowArq->sexo) : null;
                $RESULTS[$countI]['USER_DATE_OF_BIRTH'] = (!empty($rowArq->data_nascimento)) ? $rowArq->data_nascimento : null;
                $RESULTS[$countI]['USER_PRIVACY'] = $rowArq->permitir_dados_tuotempo;
                $RESULTS[$countI]['USER_PRIVACY_DIGITAL_RESULTS'] = $rowArq->permite_prontuario_tuotempo;
                $RESULTS[$countI]['USER_FIRST_NAME'] = $rowArq->nomePaciente;
                $RESULTS[$countI]['USER_SECOND_NAME'] = $rowArq->sobrenomePaciente;
                $RESULTS[$countI]['USER_THIRD_NAME'] = '';
                $RESULTS[$countI]['APP_LID'] = $rowArq->consultas_id;
                $countI++;
            }
        }
        if (isset($qrResultExames) and count($qrLaudos) > 0) {
            foreach ($qrLaudos as $rowArq) {
                $RESULTS[$countI]['EPISODE_LID'] = 'laud_' . $rowArq->id;
                $RESULTS[$countI]['CATEGORY'] = 'Laudos';
                $RESULTS[$countI]['STATUS'] = null;
                $RESULTS[$countI]['DATE'] = (!empty($rowArq->data_cad)) ? $rowArq->data_cad : null;
                $RESULTS[$countI]['TITLE'] = (!empty($rowArq->title)) ? utf8_decode($rowArq->title) : $rowArq->arquivo;
                $RESULTS[$countI]['DESCRIPTION'] = (!empty($rowArq->title)) ? utf8_decode($rowArq->title): $rowArq->arquivo;
                $RESULTS[$countI]['PRICE'] = null;
                $RESULTS[$countI]['USER_LID'] = $rowArq->pacientes_id;
                $RESULTS[$countI]['USER_MOBILE_PHONE'] = $rowArq->celular;
                $RESULTS[$countI]['USER_EMAIL'] = $rowArq->email;
                $RESULTS[$countI]['USER_ID_NUMBER'] = (!empty($rowArq->cpf)) ? $rowArq->cpf : null;
                $RESULTS[$countI]['USER_ID_TYPE'] = 'CPF';
                $RESULTS[$countI]['USER_GENDER'] = (!empty(Functions::sexoToSigla($rowArq->sexo))) ? Functions::sexoToSigla($rowArq->sexo) : null;
                $RESULTS[$countI]['USER_DATE_OF_BIRTH'] = (!empty($rowArq->data_nascimento)) ? $rowArq->data_nascimento : null;
                $RESULTS[$countI]['USER_PRIVACY'] = $rowArq->permitir_dados_tuotempo;
                $RESULTS[$countI]['USER_PRIVACY_DIGITAL_RESULTS'] = $rowArq->permite_prontuario_tuotempo;
                $RESULTS[$countI]['USER_FIRST_NAME'] = $rowArq->nomePaciente;
                $RESULTS[$countI]['USER_SECOND_NAME'] = $rowArq->sobrenomePaciente;
                $RESULTS[$countI]['USER_THIRD_NAME'] = '';
                $RESULTS[$countI]['APP_LID'] = $rowArq->consultas_id;
                $countI++;
            }
        }




        if (count($RESULTS) > 0) {
            return $this->returnSuccess($RESULTS);
        } else {

            $msg = (!empty($request->get('EPISODE_LID'))) ? 'Episode not found' : 'No episodes registered';
            return $this->returnError(['error' => $msg]);
        }
    }

    public function getNotificationUsers($request) {

        $RESULTS = array();
        $RESULTS['USER_LIDS'] = null;
        $RESULTS['ERROR_MESSAGE'] = null;

        $PacienteArquivosRep = new PacienteArquivoRepository();
        $PacienteResultadoExamRep = new PacienteResultadoExameRepository();
        $PacienteLaudoRep = new PacienteLaudoRepository();

        if (!$request->has('START_TIMESTAMP') or empty($request->get('START_TIMESTAMP'))) {
            $RESULTS['ERROR_MESSAGE'] = 'START_TIMESTAMP uninformed';
            return $this->returnError($RESULTS);
        }
        if (!$request->has('END_TIMESTAMP') or empty($request->get('END_TIMESTAMP'))) {
            $RESULTS['ERROR_MESSAGE'] = 'END_TIMESTAMP uninformed';
            return $this->returnError($RESULTS);
        }




        $qrDominios = $this->dominioRepository->getAllByUser(auth()->user()->id);
        $IDsDominio = $nomeDominios = null;
        foreach ($qrDominios as $rowDom) {
            $idsDominio[] = $rowDom->id;
            $nomeDominios[$rowDom->id] = $rowDom->dominio;
        }

        $dadosFiltro = null;
        $dadosFiltro['habilitado_paciente'] = 1;
        $dadosFiltro['dateTimeInicio'] = Functions::dateTuotempoToDB($request->get('START_TIMESTAMP'));
        $dadosFiltro['datetimeFim'] = Functions::dateTuotempoToDB($request->get('END_TIMESTAMP'));

        $qrArquivos = $PacienteArquivosRep->getAll($idsDominio, null, $dadosFiltro);
        $qrResultExames = $PacienteResultadoExamRep->getAll($idsDominio, null, $dadosFiltro);
        $qrLaudos = $PacienteLaudoRep->getAll($idsDominio, null, $dadosFiltro);

        $arrayUserLid = array();
        if (isset($qrArquivos) and count($qrArquivos) > 0) {
            foreach ($qrArquivos as $rowArq) {
                if (!in_array($rowArq->pacientes_id, $arrayUserLid)) {
                    $arrayUserLid[] = $rowArq->pacientes_id;
                }
            }
        }

        if (isset($qrResultExames) and count($qrResultExames) > 0) {
            foreach ($qrResultExames as $rowArq) {
                if (!in_array($rowArq->pacientes_id, $arrayUserLid)) {
                    $arrayUserLid[] = $rowArq->pacientes_id;
                }
            }
        }

        if (isset($qrLaudos) and count($qrLaudos) > 0) {
            foreach ($qrLaudos as $rowArq) {
                if (!in_array($rowArq->pacientes_id, $arrayUserLid)) {
                    $arrayUserLid[] = $rowArq->pacientes_id;
                }
            }
        }

        if (count($arrayUserLid) > 0) {

            $RESULTS['USER_LIDS'] = $arrayUserLid;
            return $this->returnSuccess($RESULTS);
        } else {
            $RESULTS['ERROR_MESSAGE'] = "No records found";
            return $this->returnError($RESULTS);
        }
    }

    public function getEpisodeDocuments($request) {

//        if (!$request->has('EPISODE_LID') or empty($request->get('EPISODE_LID'))) {
//            return $this->returnError(['error' => 'EPISODE_LID uninformed']);
//        }


        $PacienteFotosRep = new PacienteFotosRepository();
        $PacienteArquivosRep = new PacienteArquivoRepository();
        $PacienteResultadoExamRep = new PacienteResultadoExameRepository();
        $PacienteLaudoRep = new PacienteLaudoRepository();
        $CryptService = new CryptService;

        $episodeId = $request->get('EPISODE_LID');
        $episodeId = explode('_', $episodeId);
        $documentId = $request->get('DOCUMENT_LID');
        $documentId = explode('_', $documentId);
        $includeContent = $request->get('INCLUDE_CONTENT');


        $qrDominios = $this->dominioRepository->getAllByUser(auth()->user()->id);
        $IDsDominio = $nomeDominios = null;
        foreach ($qrDominios as $rowDom) {
            $idsDominio[] = $rowDom->id;
            $nomeDominios[$rowDom->id] = $rowDom->dominio;
        }


        $dadosFiltro = null;
        $dadosFiltro['habilitado_paciente'] = 1;

        if (empty($request->get('EPISODE_LID')) and empty($request->get('DOCUMENT_LID'))) {
            return $this->returnError(['error' => 'EPISODE_LID and DOCUMENT_LID uninformed']);
        }
        if (!empty($request->get('EPISODE_LID')) and count($episodeId) != 2) {
            return $this->returnError(['error' => 'Episode not found']);
        }


        if ($request->has('DOCUMENT_LID') and!empty($request->get('DOCUMENT_LID')) and count($documentId) != 3) {
            return $this->returnError(['error' => 'Document not found']);
        }

        if (!empty($episodeId[0])) {
            $buscaArq = $episodeId[0];
            $idArq = $episodeId[1];
        }
        if (!empty($documentId[0])) {
            $buscaArq = $documentId[1];
            $idArq = $documentId[2];
        }


        switch ($buscaArq) {
            case 'arq':
                $dadosFiltro['id'] = $idArq;
                $qrArquivos = $PacienteArquivosRep->getAll($idsDominio, null, $dadosFiltro);

                break;
            case 'resexame':
                $dadosFiltro['id'] = $idArq;
                $qrResultExames = $PacienteResultadoExamRep->getAll($idsDominio, null, $dadosFiltro);
                break;
            case 'laud':
                $dadosFiltro['id'] = $idArq;
                $qrLaudos = $PacienteLaudoRep->getAll($idsDominio, null, $dadosFiltro);
                break;

            default:
//                $qr = $PacienteFotosRep->getAll($idsDominio, $idPaciente, $dadosFiltro);
                $qrArquivos = $PacienteArquivosRep->getAll($idsDominio, null, $dadosFiltro);
                $qrResultExames = $PacienteResultadoExamRep->getAll($idsDominio, null, $dadosFiltro);
                $qrLaudos = $PacienteLaudoRep->getAll($idsDominio, null, $dadosFiltro);
//        dd($qr);

                break;
        }




        $RESULTS = array();
        $countR = 0;
        if (isset($qrArquivos) and count($qrArquivos) > 0) {
            foreach ($qrArquivos as $rowArq) {

                $extensao = explode('.', $rowArq->arquivo);
                $extensao = $extensao[count($extensao) - 1];

                $dadosLink = null;
                $dadosLink['dominio_id'] = $rowArq->identificador;
                $dadosLink['tipo'] = 'Arquivos';
                $dadosLink['id'] = $rowArq->id;
                $dadosLink = $CryptService->encrypt(json_encode($dadosLink));
                $dadosLink = rawurlencode($dadosLink);

                $RESULTS[$countR]['DOCUMENT_LID'] = 'doc_arq_' . $rowArq->id;
                $RESULTS[$countR]['EPISODE_LID'] = 'arq_' . $rowArq->id;
                $RESULTS[$countR]['STATUS'] = 'Valid';
                $RESULTS[$countR]['FILENAME'] = (!empty($rowArq->title)) ? utf8_decode($rowArq->title) : ($rowArq->arquivo);
                $RESULTS[$countR]['CATEGORY'] = 'Arquivos';
                $RESULTS[$countR]['TYPE'] = $extensao;
                $RESULTS[$countR]['ISSUED'] = (!empty($rowArq->data_cad)) ? Functions::datetimeToTuotempo($rowArq->data_cad) : null;
                $RESULTS[$countR]['CONTENT'] = null;
                $RESULTS[$countR]['URL'] = url('/v1/docs/download?d=' . $dadosLink);
                $RESULTS[$countR]['ERROR_MESSAGE'] = '';
                $countR++;
            }
        }


        if (isset($qrResultExames) and count($qrResultExames) > 0) {
            foreach ($qrResultExames as $rowArq) {

                $extensao = explode('.', $rowArq->arquivo);
                $extensao = $extensao[count($extensao) - 1];

                $dadosLink = null;
                $dadosLink['dominio_id'] = $rowArq->identificador;
                $dadosLink['tipo'] = 'Resultado de exames';
                $dadosLink['id'] = $rowArq->id;
                $dadosLink = $CryptService->encrypt(json_encode($dadosLink));
                $dadosLink = rawurlencode($dadosLink);

                $RESULTS[$countR]['DOCUMENT_LID'] = 'doc_resexame_' . $rowArq->id;
                $RESULTS[$countR]['EPISODE_LID'] = 'resexame_' . $rowArq->id;
                $RESULTS[$countR]['STATUS'] = 'Valid';
               $RESULTS[$countR]['FILENAME'] = (!empty($rowArq->title)) ?  utf8_decode($rowArq->title): ($rowArq->arquivo);
                $RESULTS[$countR]['CATEGORY'] = 'Resultado de exames';
                $RESULTS[$countR]['TYPE'] = $extensao;
                $RESULTS[$countR]['ISSUED'] = (!empty($rowArq->data_cad)) ? Functions::datetimeToTuotempo($rowArq->data_cad) : null;
                $RESULTS[$countR]['CONTENT'] = null;
                $RESULTS[$countR]['URL'] = url('/v1/docs/download?d=' . $dadosLink);
                $RESULTS[$countR]['ERROR_MESSAGE'] = '';
                $countR++;
            }
        }
        if (isset($qrLaudos) and count($qrLaudos) > 0) {
            foreach ($qrLaudos as $rowArq) {

                $extensao = explode('.', $rowArq->arquivo);
                $extensao = $extensao[count($extensao) - 1];

                $dadosLink = null;
                $dadosLink['dominio_id'] = $rowArq->identificador;
                $dadosLink['tipo'] = 'Laudos';
                $dadosLink['id'] = $rowArq->id;
                $dadosLink = $CryptService->encrypt(json_encode($dadosLink));
                $dadosLink = rawurlencode($dadosLink);

                $RESULTS[$countR]['DOCUMENT_LID'] = 'doc_laud_' . $rowArq->id;
                $RESULTS[$countR]['EPISODE_LID'] = 'laud_' . $rowArq->id;
                $RESULTS[$countR]['STATUS'] = 'Valid';
                $RESULTS[$countR]['FILENAME'] = (!empty($rowArq->title)) ? utf8_decode($rowArq->title): ($rowArq->arquivo);
                $RESULTS[$countR]['CATEGORY'] = 'Laudos';
                $RESULTS[$countR]['TYPE'] = $extensao;
                $RESULTS[$countR]['ISSUED'] = (!empty($rowArq->data_cad)) ? Functions::datetimeToTuotempo($rowArq->data_cad) : null;
                $RESULTS[$countR]['CONTENT'] = null;
                $RESULTS[$countR]['URL'] = url('/v1/docs/download?d=' . $dadosLink);
                $RESULTS[$countR]['ERROR_MESSAGE'] = '';
                $countR++;
            }
        }

        if (empty($RESULTS)) {
            return $this->returnError(['error' => 'Document not found']);
        }
        return $this->returnSuccess($RESULTS);
    }

    public function downloadEpisodeDoc($request) {

        $CryptService = new CryptService;
        $PacienteArquivoRepository = new PacienteArquivoRepository;
        $PacienteResultadoExameRepository = new PacienteResultadoExameRepository();
        $PacienteLaudoRepository = new PacienteLaudoRepository();
        $DominioRep = new DominioRepository;
        $dados = $CryptService->decrypt($request->get('d'));
        $dados = json_decode($dados);

        if (!isset($dados->dominio_id)) {
            return $this->returnError(['error' => 'invalid data']);
        }





        $qrDominio = $DominioRep->getById($dados->dominio_id);
        if (count($qrDominio) == 0) {
            return $this->returnError(['error' => 'invalid data']);
        }
        $rowDominio = $qrDominio[0];
        $rootUrl = explode('/', $_SERVER['DOCUMENT_ROOT']);
        $rootUrl = $rootUrl[0] . '/' . $rootUrl[1] . '/' . $rootUrl[2] . '/' . $rootUrl[3] . '/app/perfis/' . $rowDominio->dominio;



        switch ($dados->tipo) {
            case 'Arquivos':
                $qr = $PacienteArquivoRepository->getById($dados->dominio_id, $dados->id);
                if (count($qr) > 0) {
                    $rowArq = $qr[0];
                    $file = $rootUrl . '/arquivos/' . $rowArq->arquivo;
                    $name = (!empty($rowArq->title)) ? utf8_decode($rowArq->title) : 'arqu_' . date('d_m_Y_h_i_s');
                    if (file_exists($file)) {
                        $ext = pathinfo($file);
                        return $this->returnSuccess(['file' => $file, 'name' => $name . '.' . $ext['extension']]);
                    }
                }
                break;
            case 'Resultado de exames':
                $qr = $PacienteResultadoExameRepository->getById($dados->dominio_id, $dados->id);
                if (count($qr) > 0) {
                    $rowArq = $qr[0];
                    $file = $rootUrl . '/arquivos/resultado_exames/' . $rowArq->arquivo;
                    $name = (!empty($rowArq->title)) ? utf8_decode($rowArq->title) : 'result_exame_' . date('d_m_Y_h_i_s');
                    if (file_exists($file)) {
                        $ext = pathinfo($file);
                        return $this->returnSuccess(['file' => $file, 'name' => $name . '.' . $ext['extension']]);
                    }
                }
                break;
            case 'Laudos':
                $qr = $PacienteLaudoRepository->getById($dados->dominio_id, $dados->id);
                if (count($qr) > 0) {
                    $rowArq = $qr[0];
                    $file = $rootUrl . '/arquivos/laudos/' . $rowArq->arquivo;
                    $name = (!empty($rowArq->title)) ? utf8_decode($rowArq->title) : 'laudo_' . date('d_m_Y_h_i_s');
                    if (file_exists($file)) {
                        $ext = pathinfo($file);
                        return $this->returnSuccess(['file' => $file, 'name' => $name . '.' . $ext['extension']]);
                    }
                }
                break;
        }


        return $this->returnError(['error' => 'invalid data']);
    }

}
