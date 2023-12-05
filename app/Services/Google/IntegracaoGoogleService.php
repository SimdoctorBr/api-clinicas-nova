<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\Google;

use App\Services\BaseService;
use DateTime;
use App\Helpers\Functions;
use App\Repositories\Clinicas\IntegracaoGoogleRepository;

//require_once $_SERVER['DOCUMENT_ROOT']. '/vendor/autoload.php';
require_once '/home/simdoctorcombr/public_html/api.clinicanova-dev/app/Services/GoogleAPI/vendor/autoload.php';

use Google_Service_Calendar;
use Google_Service_PeopleService;
use Google_Client;
use Google_Service_Calendar_Event;

/**
 * Description of Activities
 *
 * @author ander
 */
class IntegracaoGoogleService extends BaseService {

    private $id;
    private $credencial;
    private $doutores_id;
    private $idDominio;
    private $habilita_calendario;
    private $calendario_nome;
    private $habilita_contato;
    private $client;
    private $eventoTitulo;
    private $eventoLocal;
    private $eventoDescricao;
    private $eventoDataInicio;
    private $eventoHoraInicio;
    private $eventoDataTermino;
    private $eventoHoraTermino;
    private $eventoTermino;
    private $eventoTimezone;
    private $calendarId;
    private $contatoNome;
    private $contatoSobrenome;
    private $contatoTelefone;
    private $habilita_lembrete_calendario;
    private $integracaoGoogleRepository;
    private $jsonClienteGoogle;

    public function __construct() {
        $this->integracaoGoogleRepository = new IntegracaoGoogleRepository;
        $this->jsonClienteGoogle = ENV('APP_PATH_APP') . 'googleApi/client_secret2.json';
    }

    public function getId() {
        return $this->id;
    }

    public function getCredencial() {
        return $this->credencial;
    }

    public function getDoutores_id() {
        return $this->doutores_id;
    }

    public function getIdentificador() {
        return $this->identificador;
    }

    public function getHabilita_calendario() {
        return $this->habilita_calendario;
    }

    public function getCalendario_nome() {
        return $this->calendario_nome;
    }

    public function getHabilita_contato() {
        return $this->habilita_contato;
    }

    public function getClient() {
        return $this->client;
    }

    public function getEventoTitulo() {
        return $this->eventoTitulo;
    }

    public function getEventoLocal() {
        return $this->eventoLocal;
    }

    public function getEventoDescricao() {
        return $this->eventoDescricao;
    }

    public function getEventoDataInicio() {
        return $this->eventoDataInicio;
    }

    public function getEventoHoraInicio() {
        return $this->eventoHoraInicio;
    }

    public function getEventoDataTermino() {
        return $this->eventoDataTermino;
    }

    public function getEventoHoraTermino() {
        return $this->eventoHoraTermino;
    }

    public function getEventoTermino() {
        return $this->eventoTermino;
    }

    public function getEventoTimezone() {
        return $this->eventoTimezone;
    }

    public function getCalendarId() {
        return $this->calendarId;
    }

    public function getContatoNome() {
        return $this->contatoNome;
    }

    public function getContatoSobrenome() {
        return $this->contatoSobrenome;
    }

    public function getContatoTelefone() {
        return $this->contatoTelefone;
    }

    public function getHabilita_lembrete_calendario() {
        return $this->habilita_lembrete_calendario;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function setCredencial($credencial) {
        $this->credencial = $credencial;
    }

    public function setDoutores_id($doutores_id) {
        $this->doutores_id = $doutores_id;
    }

    public function setIdentificador($idDominio) {
        $this->identificador = $idDominio;
    }

    public function setHabilita_calendario($habilita_calendario) {
        $this->habilita_calendario = $habilita_calendario;
    }

    public function setCalendario_nome($calendario_nome) {
        $this->calendario_nome = $calendario_nome;
    }

    public function setHabilita_contato($habilita_contato) {
        $this->habilita_contato = $habilita_contato;
    }

    public function setClient($client) {
        $this->client = $client;
    }

    public function setEventoTitulo($eventoTitulo) {
        $this->eventoTitulo = $eventoTitulo;
    }

    public function setEventoLocal($eventoLocal) {
        $this->eventoLocal = $eventoLocal;
    }

    public function setEventoDescricao($eventoDescricao) {
        $this->eventoDescricao = $eventoDescricao;
    }

    public function setEventoDataInicio($eventoDataInicio) {
        $this->eventoDataInicio = $eventoDataInicio;
    }

    public function setEventoHoraInicio($eventoHoraInicio) {
        $this->eventoHoraInicio = $eventoHoraInicio;
    }

    public function setEventoDataTermino($eventoDataTermino) {
        $this->eventoDataTermino = $eventoDataTermino;
    }

    public function setEventoHoraTermino($eventoHoraTermino) {
        $this->eventoHoraTermino = $eventoHoraTermino;
    }

    public function setEventoTermino($eventoTermino) {
        $this->eventoTermino = $eventoTermino;
    }

    public function setEventoTimezone($eventoTimezone) {
        $this->eventoTimezone = $eventoTimezone;
    }

    public function setCalendarId($calendarId) {
        $this->calendarId = $calendarId;
    }

    public function setContatoNome($contatoNome) {
        $this->contatoNome = $contatoNome;
    }

    public function setContatoSobrenome($contatoSobrenome) {
        $this->contatoSobrenome = $contatoSobrenome;
    }

    public function setContatoTelefone($contatoTelefone) {
        $this->contatoTelefone = $contatoTelefone;
    }

    public function setHabilita_lembrete_calendario($habilita_lembrete_calendario) {
        $this->habilita_lembrete_calendario = $habilita_lembrete_calendario;
    }

    private function getReminders() {

        $remiders = array(
            'useDefault' => FALSE,
            'overrides' => array(
                array('method' => 'email', 'minutes' => 24 * 60),
                array('method' => 'email', 'minutes' => 30),
                array('method' => 'popup', 'minutes' => 24 * 60),
                array('method' => 'popup', 'minutes' => 30),
            ),
        );

        return $remiders;
    }

    /**
     * Conectar com o Google
     * @param type $googleAcesseToken
     */
    private function googleConect($googleAcesseToken = null) {


        $Scope = implode(' ', array(
            Google_Service_Calendar::CALENDAR,
            Google_Service_PeopleService::CONTACTS,
            Google_Service_PeopleService::USERINFO_PROFILE,
            Google_Service_PeopleService::USERINFO_EMAIL,
                )
        );

        $client = new Google_Client();
        $client->setAuthConfig($this->jsonClienteGoogle);
        $client->addScope($Scope);

        $client->setAccessType("offline");

        if (!empty($googleAcesseToken)) {
            $client->setAccessToken($googleAcesseToken);
        } else {
            $client->setAccessToken($_SESSION['google_access']);
        }

        $this->client = $client;
    }

    /**
     * 
     * @param type $idDominio
     * @param type $rowConfig
     * @param type $doutorId
     */
    public function verifyConfig($idDominio, $rowConfig, $doutorId = null) {
        if (empty($rowConfig)) {
            if (!empty($doutorId)) {
                $rowConfig = $this->integracaoGoogleRepository->getByDoutoresId($idDominio, $doutorId);
            } else {
                $rowConfig = $this->integracaoGoogleRepository->getByIdentificador($idDominio);
            }
        }

        return $rowConfig;
    }

    /**
     * 
     * @param type $googleAcesseToken usado caso seja a agenda do doutor
     * @return boolean
     */
    public function verificaTokenExpirado(Array $googleAcessToken) {

        if (isset($googleAcessToken) and!empty($googleAcessToken)) {

            $this->googleConect($googleAcessToken);

            try {
                $people = new Google_Service_PeopleService($this->client);

                $optParams = array(
                    'requestMask.includeField' => 'person.names,person.emailAddresses',
                    'resourceNames' => 'people/me'
                );
//                  dd($optParams);
                $profile = $people->people->getBatchGet($optParams);
            } catch (Google_Service_Exception $ex) {
//                var_dump($ex->getCode());
//                var_dump($ex->getMessage());
                return false;
            }

            return true;
        } else {
            return false;
        }
    }

    public function getByDoutoresId($idDominio, $doutorId) {
        return $this->integracaoGoogleRepository->getByDoutoresId($idDominio, $doutorId);
    }

    public function getEventoPorTipo($idDominio, $tipo, $id, $email, $calendario_id = null) {
        return $this->integracaoGoogleRepository->getEventoPorTipo($idDominio, $tipo, $id, $email, $calendario_id);
    }

    /**
     * 
     * @param type $idDominio
     * @param type $tipo 1 - COnsulta, 2 - Compromisso
     * @param type $idTipo
     * @return type $arrayEventos
     * @return type $doutorId Caso seja 'null' vai para a gend da clínica
     */
    public function adicionaEventoCalendario($idDominio, $tipo, $idTipo, $arrayEventos = null, $doutorId = null, $rowConfig = null) {

        $IntegracaoGoogleRepository = new IntegracaoGoogleRepository;

        $rowConfig = $this->verifyConfig($idDominio, $rowConfig, $doutorId);

        $credencialDoutor = (!empty($doutorId)) ? json_decode($rowConfig->credencial, true) : null;
        $this->googleConect($credencialDoutor);

        $service = new Google_Service_Calendar($this->client);

        if ($rowConfig->habilita_lembrete_calendario == 1) {
            $remiders = $this->getReminders();
        } else {
            $remiders = array(
                'useDefault' => FALSE,
            );
        }



        if (!empty($arrayEventos) and is_array($arrayEventos)) {
            $GoogleEventos = null;
            foreach ($arrayEventos as $rowEvento) {
                $idConsulta = $rowEvento['idConsulta'];
                $GoogleEventos[] = new Google_Service_Calendar_Event($rowEvento['dadosEvento']);
                $event = $service->events->insert($rowConfig->calendario_google_id, new Google_Service_Calendar_Event($rowEvento['dadosEvento']));

                $campos['tipo'] = $tipo;
                $campos['id_tipo'] = $idConsulta;
                $campos['identificador'] = $idDominio;
                $campos['evento_id'] = $event->id;
                $campos['email'] = $rowConfig->email;
                $campos['data_cad'] = date('Y-m-d H:i:s');
                $campos['administrador_id_cad'] = (auth('clinicas')->check()) ? auth('clinicas')->user()->id : null;
                $qr = $this->integracaoGoogleRepository->insertEvento($idDominio, $campos);
            }
        } else {
            $event = new Google_Service_Calendar_Event(array(
                'summary' => $this->eventoTitulo,
                'location' => $this->eventoLocal,
                'description' => $this->eventoDescricao,
                'start' => array(
                    'dateTime' => $this->eventoDataInicio . 'T' . $this->eventoHoraInicio,
                    'timeZone' => $this->eventoTimezone,
                ),
                'end' => array(
                    'dateTime' => $this->eventoDataTermino . 'T' . $this->eventoHoraTermino,
                    'timeZone' => $this->eventoTimezone),
                'reminders' => $remiders,
            ));

            $event = $service->events->insert($rowConfig->calendario_google_id, $event);

            $campos['tipo'] = $tipo;
            $campos['id_tipo'] = $idTipo;
            $campos['identificador'] = $idDominio;
            $campos['evento_id'] = $event->id;
            $campos['email'] = $rowConfig->email;
            $campos['data_cad'] = date('Y-m-d H:i:s');
            $campos['administrador_id_cad'] = (auth('clinicas')->check()) ? auth('clinicas')->user()->id : null;
            if (!empty($doutorId)) {
                $campos['doutores_id'] = $doutorId;
                $campos['google_credenciais_id'] = $rowConfig->id;
                $campos['calendario_google_id'] = $rowConfig->calendario_google_id;
            }


            $qr = $this->integracaoGoogleRepository->insertEvento($idDominio, $campos);
        }


//        print_r($event);
        return $qr;
    }

    public function atualizaEventoCalendario($idDominio, $idEvento, $doutorId = null, $rowConfig = null) {

        $rowConfig = $this->verifyConfig($idDominio, $rowConfig, $doutorId);
        $credencialDoutor = (!empty($doutorId)) ? json_decode($rowConfig->credencial, true) : null;

        $this->googleConect($credencialDoutor);
        $service = new Google_Service_Calendar($this->client);

        $event = new Google_Service_Calendar_Event(array(
            'summary' => $this->eventoTitulo,
            'location' => $this->eventoLocal,
            'description' => $this->eventoDescricao,
            'start' => array(
                'dateTime' => $this->eventoDataInicio . 'T' . $this->eventoHoraInicio,
                'timeZone' => $this->eventoTimezone,
            ),
            'end' => array(
                'dateTime' => $this->eventoDataTermino . 'T' . $this->eventoHoraTermino,
                'timeZone' => $this->eventoTimezone),
            'reminders' => $this->getReminders()
        ));

        $event = $service->events->update($rowConfig->calendario_google_id, $idEvento, $event);
//        print_r($event);
        return $event->id;
    }

//
    public function excluirEventoCalendario($idDominio, $idEvento, $idGoogleEmalEvento = null, $doutorId = null, $rowConfig = null) {

        $rowConfig = $this->verifyConfig($idDominio, $rowConfig, $doutorId);
        $credencialDoutor = (!empty($doutorId)) ? json_decode($rowConfig->credencial, true) : null;
        $this->googleConect($credencialDoutor);

        $service = new Google_Service_Calendar($this->client);
        $verificaEvento = $service->events->get($rowConfig->calendario_google_id, $idEvento);

        if ($verificaEvento->status != 'cancelled') {
            $event = $service->events->delete($rowConfig->calendario_google_id, $idEvento);
        }
        if (!empty($idGoogleEmalEvento)) {
            $qr = $this->integracaoGoogleRepository->excluirEvento($idDominio, $idGoogleEmalEvento);
        }
    }

    /**
     * 
     * @param type $idDominio
     * @param type $doutorId
     * @param type $tipo   1 - COnsulta, 2 - Compromisso
     * @param type $idTipo
     * @param type $descricao
     * @param type $dataHoraIni
     * @param type $dataHoraFim
     * @param type $rowConfig
     * @return type
     */
    public function insertUpdateEventoCalendarioDoutor($idDominio, $doutorId, $tipo, $idTipo, $descricao, $dataHoraIni, $dataHoraFim, $rowConfig = null) {


        $rowConfig = $this->verifyConfig($idDominio, $rowConfig, $doutorId);
        $credencialDoutor = (!empty($doutorId)) ? json_decode($rowConfig->credencial, true) : null;

        $tokenGoogleExpiradoDoutor = $this->verificaTokenExpirado(json_decode($rowConfig->credencial, true));
        if ($tokenGoogleExpiradoDoutor) {

            $this->googleConect($credencialDoutor);
            $service = new Google_Service_Calendar($this->client);
            $remiders = ($rowConfig->habilita_lembrete_calendario == 1) ? $this->getReminders() : array('useDefault' => FALSE,);

            $event = new Google_Service_Calendar_Event(array(
                'summary' => $descricao,
                'location' => '',
                'description' => $descricao,
                'start' => array(
                    'dateTime' => str_replace(' ', 'T', $dataHoraIni),
                    'timeZone' => date_default_timezone_get(),
                ),
                'end' => array(
                    'dateTime' => str_replace(' ', 'T', $dataHoraFim),
                    'timeZone' => date_default_timezone_get()
                ),
                'reminders' => $remiders,
            ));
           

            $ConsultaGoogleEventoGoogle = $this->integracaoGoogleRepository->getEventoPorTipo($idDominio, $tipo, $idTipo, $rowConfig->email, $rowConfig->calendario_google_id);
            
            if ($ConsultaGoogleEventoGoogle) {
                //atualizando
                $event = $service->events->update($rowConfig->calendario_google_id, $ConsultaGoogleEventoGoogle->evento_id, $event);
                $idRepository = $ConsultaGoogleEventoGoogle->id;
                $googleEventId = $ConsultaGoogleEventoGoogle->evento_id;
            } else {

                $campos['tipo'] = $tipo;
                $campos['id_tipo'] = $idTipo;
                $campos['identificador'] = $idDominio;
                $campos['email'] = $rowConfig->email;
                $campos['data_cad'] = date('Y-m-d H:i:s');
                $campos['administrador_id_cad'] = (auth('clinicas')->check()) ? auth('clinicas')->user()->id : null;
                if (!empty($doutorId)) {
                    $campos['doutores_id'] = $doutorId;
                    $campos['google_credenciais_id'] = $rowConfig->id;
                    $campos['calendario_google_id'] = $rowConfig->calendario_google_id;
                }
                //inserindo
                $event = $service->events->insert($rowConfig->calendario_google_id, $event);
                $campos['evento_id'] = $event->id;
                $qr = $this->integracaoGoogleRepository->insertEvento($idDominio, $campos);
                $idRepository = $qr;
                $googleEventId = $event->id;
            }



            return $this->returnSuccess([
                        'id' => $idRepository,
                        'googleEventId' => $googleEventId,
            ]);
        } else {
            return $this->returnError();
        }
    }

//    
//    
}
