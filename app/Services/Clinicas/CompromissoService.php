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
use App\Repositories\Clinicas\CompromissoRepository;
use App\Services\Clinicas\HorariosService;
use App\Repositories\Clinicas\StatusRefreshRepository;
use App\Helpers\Functions;
use App\Services\Clinicas\LogAtividadesService;
use App\Services\Google\IntegracaoGoogleService;

/**
 * Description of Activities
 *
 * @author ander
 */
class CompromissoService extends BaseService {

    private function responseFields($row) {
        $retorno['id'] = $row->id;
        $retorno['nome'] = Functions::utf8ToAccentsConvert($row->nome);
        $retorno['impedeConsultas'] = ($row->impedeConsultas == 1) ? true : false;
        $retorno['data'] = $row->data_compromisso;
        $retorno['hora'] = $row->hora_agendamento;
        $retorno['horaFim'] = $row->hora_fim;
        $retorno['doutorId'] = $row->doutores_id;
        $retorno['realizado'] = ($row->realizado == 1) ? true : false;
        if (isset($row->nomeDoutor)) {
            $retorno['nomeDoutor'] = $row->nomeDoutor;
        }

        return $retorno;
    }

    /**
     * 
     * @param type $idDominio
     * @param type $idDoutor
     * @param type $dadosFiltro
     * @param type $tipoRetorno 1- Normal, 2 - Por data/hora
     * @return type
     */
    public function getAll($idDominio, $idDoutor, $dadosFiltro = null, $tipoRetorno = 1) {

        $CompromissoRepository = new CompromissoRepository;
        $qrCompromisso = $CompromissoRepository->getAll($idDominio, $idDoutor, $dadosFiltro);

        $COMPROMISSOS = null;
        if ($tipoRetorno == 1) {

            if (count($qrCompromisso) > 0) {
                foreach ($qrCompromisso as $row) {
                    $COMPROMISSOS[] = $this->responseFields($row);
                }
            }
        } elseif ($tipoRetorno == 2) {

            $i = 0;
            $horarioAnt = null;

            if (count($qrCompromisso) > 0) {
                foreach ($qrCompromisso as $row) {

                    $horario = substr($row->hora_agendamento, 0, 5);

                    if ($horario != $horarioAnt) {
                        $i = 0;
                    }

                    $COMPROMISSOS[$row->data_compromisso][$horario][$i] = $row;

                    $horarioAnt = $horario;
                    $i++;
                }
            }
        }
        return $COMPROMISSOS;
    }

    public function getById($idDominio, $idCompromisso) {

        $CompromissoRepository = new CompromissoRepository;
        $rowCompromisso = $CompromissoRepository->getById($idDominio, $idCompromisso);

        if ($rowCompromisso) {
            return $this->returnSuccess($this->responseFields($rowCompromisso));
        } else {
            return $this->returnError(NULL, 'Compromisso não encontrado');
        }
    }

    public function store($idDominio, $dadosInput, $idCompromissoUpdate = null) {

        $CompromissoRepository = new CompromissoRepository;
        $HorariosService = new HorariosService;

        $horario = $dadosInput['horario'];
        $horarioFim = null;
        if (isset($dadosInput['horarioFim']) and!empty($dadosInput['horarioFim'])) {
            $camposInsert['hora_fim'] = $dadosInput['horarioFim'];
            $horarioFim = $dadosInput['horarioFim'];
        }


        //verifica horários
        $qrVerificaHorarios = $HorariosService->verificaHorarioDisponivel($idDominio, $dadosInput['doutorId'], $dadosInput['data'], $dadosInput['horario'], $horarioFim);

        if (!$qrVerificaHorarios['success']) {
            return $qrVerificaHorarios;
        }



        $camposInsert['nome'] = Functions::accentsToUtf8Convert($dadosInput['compromisso']);
        $camposInsert['data'] = Functions::dateDbToBr($dadosInput['data']);
        $camposInsert['data_agendamento'] = time();
        $camposInsert['hora_agendamento'] = $dadosInput['horario'];
        $camposInsert['data_compromisso'] = $dadosInput['data'];
        $camposInsert['impedeConsultas'] = (isset($dadosInput['impedeConsultas']) and $dadosInput['impedeConsultas']) ? $dadosInput['impedeConsultas'] : 0;

        if (isset($dadosInput['horarioFim']) and!empty($dadosInput['horarioFim'])) {
            $camposInsert['hora_fim'] = $dadosInput['horarioFim'];
        }

        if (!empty($idCompromissoUpdate)) {
            $CompromissoRepository->update($idDominio, $camposInsert);
            $idInsert = $idCompromissoUpdate;
        } else {
            $camposInsert['identificador'] = $idDominio;
            $camposInsert['doutores_id'] = $dadosInput['doutorId'];
            $idInsert = $CompromissoRepository->store($idDominio, $camposInsert);
        }


        $rowCompromisso = $this->getById($idDominio, $idInsert);

        if ($rowCompromisso) {
            $rowCompromisso = $rowCompromisso['data'];
            $LogAtividadesService = new LogAtividadesService();
            $LogAtividadesService->store($idDominio, 2, "Agendou o compromisso " . addslashes($camposInsert['nome']) . " para o(a) doutor(a) " . utf8_encode($rowCompromisso['nomeDoutor']) . " no dia " . Functions::dateDbToBr($dadosInput['data']) . utf8_encode(" às ") . $dadosInput['horario'] . "h", $rowCompromisso['id'], 24);

            $StatusRefreshRepository = new StatusRefreshRepository;
            $StatusRefreshRepository->insertAgenda($idDominio, $dadosInput['doutorId']);

            //integração com a agenda do Google
            $msgGoogleEvent = 'Compromisso - ' . $dadosInput['compromisso'];

            $IntegracaoGoogleService = new IntegracaoGoogleService;

            $qrHorarios = $HorariosService->listHorarios($idDominio, $dadosInput['doutorId'], $dadosInput['data'], $dadosInput['horario'], $dadosInput['data'], $dadosInput['horario'], null, null, false, false, ['exibeConsultaTermino' => true]);
            $horaConsultaFim = (empty($dadosInput['horarioFim'])) ? date('H:i:00', strtotime($dadosInput['horario'] . " +" . $qrHorarios[0]['intervalo'] . " minutes ")) : substr($dadosInput['horarioFim'], 0, 5) . ':00';

            $dataHoraIni = $dadosInput['data'] . ' ' . substr($dadosInput['horario'], 0, 5) . ':00';
            $dataHoraFim = $dadosInput['data'] . ' ' . $horaConsultaFim;
            $tt = $IntegracaoGoogleService->insertUpdateEventoCalendarioDoutor($idDominio, $dadosInput['doutorId'], 2, $idInsert, $msgGoogleEvent, $dataHoraIni, $dataHoraFim);

            return $rowCompromisso;
        } else {
            return $this->returnError(NULL, 'Erro ao cadastrar o compromisso');
        }
    }

    public function delete($idDominio, $compromissoId) {

        $CompromissoRepository = new CompromissoRepository;
        $rowCompromisso = $this->getById($idDominio, $compromissoId);

        if ($rowCompromisso['success']) {

            $rowCompromisso = $rowCompromisso['data'];

            $CompromissoRepository->delete($idDominio, $compromissoId);
            $StatusRefreshRepository = new StatusRefreshRepository;
            $StatusRefreshRepository->insertAgenda($idDominio, $rowCompromisso['doutorId']);

            $LogAtividadesService = new LogAtividadesService();
            $LogAtividadesService->store($idDominio, 4, "Excluiu o compromisso \"" . addslashes($rowCompromisso['nome']) . "\"  para o(a) doutor(a) " . utf8_encode($rowCompromisso['nomeDoutor']) . " no dia " . Functions::dateDbToBr($rowCompromisso['data']) . utf8_encode(" às ") . substr($rowCompromisso['hora'], 0, 5) . "h", $rowCompromisso['id'], 24);

            ///////////////////////////////////
            ///Integração com o Google
            ///////////////////////////////////
            $IntegracaoGoogleService = new IntegracaoGoogleService;
            $rowGoogleConfigDoutor = $IntegracaoGoogleService->getByDoutoresId($idDominio, $rowCompromisso['doutorId']);
            $tokenGoogleExpiradoDoutor = $IntegracaoGoogleService->verificaTokenExpirado(json_decode($rowGoogleConfigDoutor->credencial, true));

            if ($tokenGoogleExpiradoDoutor) {
                $eventoGoogle = $IntegracaoGoogleService->getEventoPorTipo($idDominio, 2, $compromissoId, $rowGoogleConfigDoutor->email, $rowGoogleConfigDoutor->calendario_google_id);
                if ($eventoGoogle) {
                    $IntegracaoGoogleService->excluirEventoCalendario($idDominio, $eventoGoogle->evento_id, $eventoGoogle->id, $rowCompromisso['doutorId'], $rowGoogleConfigDoutor);
                }
            }



            return $this->returnError('', 'Compromisso excluido com sucesso.');
        } else {
            return $rowCompromisso;
        }
    }

    public function alterarStatus($idDominio, $compromissoId, $status) {

        if ($status != 1 and $status != 0) {
            return $this->returnError('', 'O status deve ser 1 ou 0.');
        }

        $CompromissoRepository = new CompromissoRepository;
        $rowCompromisso = $this->getById($idDominio, $compromissoId);

        if ($rowCompromisso['success']) {
            $CompromissoRepository->alterarStatus($idDominio, $compromissoId, $status);
            $StatusRefreshRepository = new StatusRefreshRepository;
            $StatusRefreshRepository->insertAgenda($idDominio, $rowCompromisso['data']['doutorId']);

            return $this->returnError('', 'Compromisso atualizado com sucesso.');
        } else {
            return $rowCompromisso;
        }
    }

}
