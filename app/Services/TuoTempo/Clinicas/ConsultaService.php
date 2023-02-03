<?php

namespace App\Services\TuoTempo\Clinicas;

use App\Services\BaseService;
use App\Repositories\Clinicas\ConsultaRepository;
use App\Repositories\Clinicas\ProcedimentosRepository;
use App\Repositories\Gerenciamento\DominioRepository;
use App\Repositories\Clinicas\DefinicaoMarcacaoConsultaRepository;

use App\Repositories\Clinicas\StatusRefreshRepository;
use App\Helpers\Functions;

class ConsultaService extends BaseService {

    private $consultaRepository;
    private $dominioRepository;
    private $procedimentoRepository;
    private $definicaoMarcacaoConsultaRepository;
    private $statusRefreshRepository;

    public function __construct(ConsultaRepository $consRep, DominioRepository $domRep, ProcedimentosRepository $procRep, DefinicaoMarcacaoConsultaRepository $defMarcConsulta) {
        $this->consultaRepository = $consRep;
        $this->dominioRepository = $domRep;
        $this->procedimentoRepository = $procRep;
        $this->definicaoMarcacaoConsultaRepository = $defMarcConsulta;
        $this->statusRefreshRepository = new StatusRefreshRepository;
    }

    private function getDadosFiltro($request) {
        $dadosFiltro = null;
        if ($request->has('USER_LID') and ! empty($request->get('USER_LID'))) {
            $dadosFiltro['pacienteId'] = trim(addslashes($request->get('USER_LID')));
        }
        if ($request->has('APP_LID') and ! empty($request->get('APP_LID'))) {
            $dadosFiltro['consultaId'] = trim(addslashes($request->get('APP_LID')));
        }
        if ($request->has('START_DATE') and ! empty($request->get('START_DATE'))) {
            $dadosFiltro['dataInicio'] = trim(addslashes(Functions::dateTuotempoToDB($request->get('START_DATE'))));
        }
        if ($request->has('END_DATE') and ! empty($request->get('END_DATE'))) {
            $dadosFiltro['dataFim'] = trim(addslashes(Functions::dateTuotempoToDB($request->get('END_DATE'))));
        }
        if ($request->has('RESOURCE_LID') and ! empty($request->get('RESOURCE_LID'))) {
            $dadosFiltro['doutoresId'] = str_replace('dout', '', trim(addslashes($request->get('RESOURCE_LID'))));
        }
        if ($request->has('LAST_REQUEST_TIMESTAMP') and ! empty($request->get('LAST_REQUEST_TIMESTAMP'))) {
            $dadosFiltro['lastQuery'] = trim(addslashes(Functions::dateTuotempoToDB($request->get('LAST_REQUEST_TIMESTAMP')))); //qual campo pode usar
        }
        return $dadosFiltro;
    }

    public function getAll($request) {

//        echo date_timezone_get();
//        if (!$request->has('LOCATION_LID') or empty($request->get('LOCATION_LID'))) {
//            return $this->returnError(null, 'uninformed LOCATION_LID');
//        }
//        if ($request->has('INSURANCE_LID') and empty($request->get('INSURANCE_LID'))) {
//            return $this->returnError(null, 'uninformed INSURANCE_LID');
//        }
//
//        $dominioId = trim(addslashes($request->get('LOCATION_LID')));
//        $convenioId = trim(addslashes($request->get('INSURANCE_LID')));


        $idsDominio = $this->verifyDominioApi();

        $dadosFiltro = $this->getDadosFiltro($request);

        $dadosPaginacao = $this->getPaginate($request);
        $qrConsultas = $this->consultaRepository->getAll($idsDominio, $dadosFiltro, $dadosPaginacao['page'], $dadosPaginacao['perPage']);


        if (count($qrConsultas['RESULTS']) > 0) {

            $CONSULTAS = array();
            $arrayStatus = array('Aprovado' => 0, 'Confirmado' => 2, 'Desmarcado_paciente' => 3, 'Desmarcado_clinica' => 4, 'Atendido' => 5, 'Faltou' => 6);
            //0: Approved 1: Pending 2: Confirmed by patient 3: Cancelled by patient 4: Cancelled by center 5: Check-out 6: No show

            $statusConsulta = $arrayStatus['Aprovado'];
            foreach ($qrConsultas['RESULTS'] as $chave => $row) {

                if (!empty($row->confirmacao)) {
                    $statusConsulta = $arrayStatus['Confirmado'];
                }
                if ($row->statusConsulta == 'jaFoiAtendido') {
                    $statusConsulta = $arrayStatus['Atendido'];
                }
                if ($row->statusConsulta == 'faltou') {
                    $statusConsulta = $arrayStatus['Faltou'];
                }
                if ($row->statusConsulta == 'desmarcado') {

                    $statusConsulta = ($row->desmarcadoPor == 1) ? $arrayStatus['Desmarcado_paciente'] : $arrayStatus['Desmarcado_clinica'];
                }


                if ((empty($statusConsulta) or $row->statusConsulta == 'jaSeEncontra' ) and empty($row->confirmacao)) {
                    $statusConsulta = $arrayStatus['Aprovado'];
                }

                $dataCadF = Functions::dateDbToBr(substr($row->data_cad_consulta, 0, 10));
                $horaCad = substr($row->data_cad_consulta, 11, 5);
                $HoraJaSeEncontra = Functions::dateDbToBr(substr($row->horaJaSeEncontra, 0, 10));
                $HoraJaSeEncontra = $HoraJaSeEncontra . ' ' . substr($row->horaJaSeEncontra, 11, 5);
                $arraySexo = array('Masculino' => 'M', 'Feminino' => 'F');


                $CONSULTAS[$chave]['APP_LID'] = $row->id;
                $CONSULTAS[$chave]['STATUS'] = $statusConsulta;
                $CONSULTAS[$chave]['APP_DATE'] = Functions::dateDbToBr($row->data_consulta);
                $CONSULTAS[$chave]['START_TIME'] = substr($row->hora_consulta, 0, 5);
                $CONSULTAS[$chave]['END_TIME'] = (!empty($row->hora_consulta_fim)) ? substr($row->hora_consulta_fim, 0, 5) : null;
                $CONSULTAS[$chave]['PRICE'] = null;
                $CONSULTAS[$chave]['CREATED'] = (!empty($row->data_cad_consulta)) ? $dataCadF . ' ' . $horaCad : null;
                $CONSULTAS[$chave]['CHECKEDIN'] = Functions::dateDbToBr($row->data_consulta) . ' ' . substr($row->hora_consulta, 0, 5);
                $CONSULTAS[$chave]['START_VISIT'] = (!empty($row->inicio_atendimento)) ? Functions::datetimeToTuotempo($row->inicio_atendimento) : null;
                $CONSULTAS[$chave]['END_VISIT'] = (!empty($row->fim_atendimento)) ? Functions::datetimeToTuotempo($row->fim_atendimento) : null;

                $CONSULTAS[$chave]['USER_LID'] = $row->pacientes_id;
                $CONSULTAS[$chave]['USER_FIRST_NAME'] = $row->nomePaciente;
                $CONSULTAS[$chave]['USER_SECOND_NAME'] = $row->sobrenomePaciente;
                $CONSULTAS[$chave]['USER_THIRD_NAME'] = null;
                $CONSULTAS[$chave]['USER_DATE_OF_BIRTH'] = $row->data_nascimento;
                $CONSULTAS[$chave]['USER_PLACE_OF_BIRTH'] = null;
                $CONSULTAS[$chave]['USER_ID_NUMBER'] = Functions::cpfToNumber($row->cpfPaciente);
                $CONSULTAS[$chave]['USER_ID_TYPE'] = 'CPF';
                $CONSULTAS[$chave]['USER_GENDER'] = (isset($arraySexo[$row->sexo])) ? $arraySexo[$row->sexo] : null;

                $CONSULTAS[$chave]['USER_ZIP_CODE'] = Functions::cepToNumber($row->cep);
                $CONSULTAS[$chave]['USER_LANGUAGE'] = 'pt-br';
                $CONSULTAS[$chave]['USER_MOBILE_PHONE'] = (!empty($row->celularPaciente)) ? $row->celularPaciente : null;
                $CONSULTAS[$chave]['USER_LANDLINE_PHONE'] = (!empty($row->telefonePaciente)) ? $row->telefonePaciente : null;
                $CONSULTAS[$chave]['USER_WORK_PHONE'] = null;
                $CONSULTAS[$chave]['USER_EMAIL'] = (!empty($row->emailPaciente)) ? $row->emailPaciente : null;

                $CONSULTAS[$chave]['USER_PRIVACY'] = ($row->permitir_dados_tuotempo == 1) ? 1 : 0;
                $CONSULTAS[$chave]['USER_PRIVACY_PROMOTIONS'] = $row->envia_email;
                $CONSULTAS[$chave]['COMMUNICATION_PREFERENCES'] = null;

                $qrProcConsultas = $this->procedimentoRepository->getByConsultaId($idsDominio, $row->id);

                $convenioID = $row->identificador . '-41';
                $nomeConvenio = 'Particular';
                $activityID = null;
                $activityName = null;
                $valor_proc = null;
                $activityGroupID = 0;
                $activityGroupName = "Sem categoria";

                if (count($qrProcConsultas) > 0) { //verifcar om o tuotempo como va ser
                    $activityID = $qrProcConsultas[0]->procedimentos_id;
                    $activityName = $this->utf8Fix($qrProcConsultas[0]->nome_proc);
                    $activityGroupID = (!empty($qrProcConsultas[0]->procedimentos_cat_id) and $qrProcConsultas[0]->procedimentos_cat_id != 0) ? $qrProcConsultas[0]->procedimentos_cat_id : null;
                    $activityGroupName = $qrProcConsultas[0]->nomeCategoria;

                    $convenioID = $qrProcConsultas[0]->convenios_id;
                    $nomeConvenio = $qrProcConsultas[0]->nome_convenio;
                    $valor_proc = ($convenioID != 41) ? null : $qrProcConsultas[0]->valor_proc;
                }

                $CONSULTAS[$chave]['PRICE'] = $valor_proc;

                $CONSULTAS[$chave]['ACTIVITY_LID'] = $activityID;
                $CONSULTAS[$chave]['ACTIVITY_NAME'] = $activityName;
                $CONSULTAS[$chave]['ACTIVITY_GROUP_LID'] = $activityGroupID;
                $CONSULTAS[$chave]['ACTIVITY_GROUP_NAME'] = $activityGroupName;

                $CONSULTAS[$chave]['RESOURCE_LID'] = 'dout' . $row->doutores_id;
                $CONSULTAS[$chave]['RESOURCE_NAME'] = $row->nomeDoutor;
                $CONSULTAS[$chave]['INSURANCE_LID'] = $row->identificador . '-' . $convenioID;  //tem que por o idetificador formato identificador-id_convenio
                $CONSULTAS[$chave]['INSURANCE_NAME'] = $nomeConvenio;

                $CONSULTAS[$chave]['LOCATION_LID'] = $row->identificador;
                $CONSULTAS[$chave]['LOCATION_NAME'] = $row->nomeClinica;
            }

            $qrConsultas['RESULTS'] = $CONSULTAS;
//            dd($CONSULTAS);
            return $this->returnSuccess($qrConsultas);
        } else {
            return $this->returnError(null, 'No appointment registered');
        }

        dd($qrConsultas);
    }

    public function cancelar($request) {
        if (!$request->has('APP_LID') or empty($request->get('APP_LID'))) {
            return $this->returnError('', 'uninformed APP_LID');
        }

        $idsDominio = $this->verifyDominioApi();
        $RESULT = array('DELETE_RESULT' => null, 'ERROR_MESSAGE' => null, 'APP_LID' => $request->get('APP_LID'));
        $verificaConsulta = $this->consultaRepository->getById($idsDominio, $request->get('APP_LID'));
        if ($verificaConsulta) {

            if ($verificaConsulta->statusConsulta != 'desmarcado') {
                $qrDesmarcar = $this->consultaRepository->desmarcarConsulta($verificaConsulta->identificador, $verificaConsulta->id, 1, 'Desmarcado pelo TuoTempo');
                $RESULT['DELETE_RESULT'] = 'OK';
                return response()->json($RESULT, 200);
                  $this->statusRefreshRepository->insertAgenda($verificaConsulta->identificador, $verificaConsulta->doutores_id);
            } else {
                $RESULT['DELETE_RESULT'] = 'ERROR';
                $RESULT['ERROR_MESSAGE'] = 'The schedule is already cleared';
                return response()->json($RESULT, 401);
            }
        } else {
            $RESULT['DELETE_RESULT'] = 'ERROR';
            $RESULT['ERROR_MESSAGE'] = 'Appointment not found';
            return response()->json($RESULT, 400);
        }
    }

    public function verificaDisponibilidadeConsultasHorario($idDominio, $doutores_id, $data, $horario, $verificaConsultaNormal = true, $limiteEncaixe = true) {

        if ($verificaConsultaNormal) {
            $sqlCOnsultaEstendida = "  OR  
                            ( A.hora_consulta_fim != '00:00:00' 
                              AND  (A.hora_consulta_fim > '$horario' AND A.hora_consulta <= '$horario') )";
        } else {
            $sqlCOnsultaEstendida = '';
        }

        $qrConsultasMarcadas = $this->consultaRepository->getConsultasMarcadasHorario($idDominio, $doutores_id, $data, $horario, $verificaConsultaNormal);


        $statusDisponivel = false;

        $rowDefinicoesConsultas = $this->definicaoMarcacaoConsultaRepository->getByDoutoresId($idDominio, $doutores_id);

        $limite_consulta = $rowDefinicoesConsultas->limite_consultas;
        if ($limiteEncaixe) {
            $limite_consulta = $rowDefinicoesConsultas->limite_consultas + $rowDefinicoesConsultas->limite_encaixe_consulta;
        }

        $cont = 0;
        $consultaEstendida = false;
        if (count($qrConsultasMarcadas) > 0) {
            foreach ($qrConsultasMarcadas as $row) {

                $idConsulta = $row->id;
                if (!empty($row->hora_consulta_fim) and$row->hora_consulta_fim != '00:00:00') {
                    $consultaEstendida = true;
                }
                $cont++;
                if (!empty($row->statusConsulta) and $row->statusConsulta == 'desmarcado') {
                    $cont--;
                }
            }
        }
        //  var_dump($consultaEstendida);
        if ($consultaEstendida == false) {

            if ($cont >= $limite_consulta) {
                $statusDisponivel = false;
            } else {
                $statusDisponivel = TRUE;
            }
        } else {
            if ($cont >= $limite_consulta) {
                $statusDisponivel = false;
            } else {
                $statusDisponivel = TRUE;
            }
        }
        return (count($qrConsultasMarcadas) == 0 OR $statusDisponivel);
    }

    public function verificaConsultaRetorno($idDominio, $dataConsulta, $idPaciente, $idDoutor, $idConsulta = null) {

        $rowDefConsultas = $this->definicaoMarcacaoConsultaRepository->getByDoutoresId($idDominio, $idDoutor);
        $retorno = array(
            'isRetorno' => false,
            'diasUltConsulta' => null,
            'semConsultasAnteriores' => false,
        );

        $qrPrimeiraConsulta = $this->consultaRepository->verificaPrimeiraConsultaPaciente($idDominio, $idPaciente, $idDoutor);

        if ($qrPrimeiraConsulta == 0) {
            $retorno['semConsultasAnteriores'] = true;
            return $retorno;
        }

        $qrVerificaRetorno = $this->consultaRepository->verificaConsultasAnterioresPaciente($idDominio, $idPaciente, $dataConsulta, $idConsulta, $idDoutor);
        if ($qrVerificaRetorno > 0) {
            $rowVerificaRetorno = $qrVerificaRetorno[0];
            $retorno['diasUltConsulta'] = ($rowVerificaRetorno) ? $rowVerificaRetorno->numero_dias : null;
            if ($rowVerificaRetorno->numero_dias <= $rowDefConsultas->qnt_dias_retorno and $rowVerificaRetorno->retorno == 0 and $rowVerificaRetorno->status == 'jaFoiAtendido') {
                $retorno['isRetorno'] = true;
            }
            return $retorno;
        }
    }

}
