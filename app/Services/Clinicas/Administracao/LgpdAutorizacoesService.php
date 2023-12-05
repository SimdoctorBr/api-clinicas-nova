<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\Clinicas\Administracao;

use App\Services\BaseService;
use DateTime;
use App\Helpers\Functions;
use App\Repositories\Clinicas\Administracao\LgpdAutorizacoesRepository;

/**
 * Description of Activities
 *
 * @author ander
 */
class LgpdAutorizacoesService extends BaseService {

    private $lgpdAutorizacoesRepository;
    private $id;
    private $identificador;
    private $pacientes_id;
    private $status_autorizacao;
    private $modelos_documentos_id;
    private $origem_autorizacao;

    function setId($id) {
        $this->id = $id;
    }

    function setIdentificador($identificador) {
        $this->identificador = $identificador;
    }

    function setPacientes_id($pacientes_id) {
        $this->pacientes_id = $pacientes_id;
    }

    function setStatus_autorizacao($status_autorizacao) {
        $this->status_autorizacao = $status_autorizacao;
    }

    function setModelos_documentos_id($modelos_documentos_id) {
        $this->modelos_documentos_id = $modelos_documentos_id;
    }

    function setOrigem_autorizacao($origem_autorizacao) {
        $this->origem_autorizacao = $origem_autorizacao;
    }

    public function __construct() {
        $this->lgpdAutorizacoesRepository = new LgpdAutorizacoesRepository;
    }

    public function store() {
        $campos['pacientes_id'] = $this->pacientes_id;
        $campos['status_autorizacao'] = $this->status_autorizacao;
        $campos['origem_autorizacao'] = $this->origem_autorizacao;
        $campos['administradores_id_autorizacao'] = ( $this->origem_autorizacao == 2) ? $this->administradores_id_autorizacao : null;

        $rowLanc = $this->lgpdAutorizacoesRepository->verificaDocAutorizacaoLancado($this->identificador, $this->modelos_documentos_id, $this->pacientes_id);
        if (!$rowLanc) {
            $campos['identificador'] = $this->identificador;
            $campos['modelos_documentos_id'] = ($this->modelos_documentos_id);
            $campos['data_cad'] = date('Y-m-d H:i:s');
            $id = $this->lgpdAutorizacoesRepository->store($this->identificador, $campos);
        } else {
            $campos['data_alter'] = date('Y-m-d H:i:s');
            $qr = $this->lgpdAutorizacoesRepository->update($this->identificador, $rowLanc->id, $campos);
            $id = $rowLanc->id;
        }

        return $this->returnSuccess(['id' => $qr]);
    }

    public function verificaDocAutorizacaoLancado($idDominio, $pacientesId, $modeloDocId = null) {

        $qr = $this->lgpdAutorizacoesRepository->verificaDocAutorizacaoLancado($idDominio, $pacientesId, $modeloDocId);

        if ($qr) {
            return $this->returnSuccess($qr);
        } else {
            return $this->returnError();
        }
    }

    public function verificaTermoCondicoes($idDominio, $tipoTermo, $pacientesId) {
        $qr = $this->lgpdAutorizacoesRepository->verificaTermoCondicoes($idDominio, $tipoTermo, $pacientesId);
        if ($qr) {
            return $this->returnSuccess($qr);
        } else {
            return $this->returnError();
        }
    }

    /**
     * 
     * @param type $idDominio
     * @param type $idPaciente
     * @param type $tipoTermo
     * @param type $origemAutorizacao
     * @param type $statusAutorizacao
     * @return type
     */
    public function salvarTermosCondicoes($idDominio, $idPaciente, $tipoTermo, $origemAutorizacao, $statusAutorizacao) {

        if ($tipoTermo == 1) {
            $campos['termos_simdoctor'] = 1;
        } else if ($tipoTermo == 2) {
            $campos['termos_clinica'] = 1;
        }
        $administradorId = (isset(auth('clinicas')->user()->id)) ? auth('clinicas')->user()->id : null;

        $campos['status_autorizacao'] = $statusAutorizacao;
        $campos['origem_autorizacao'] = $origemAutorizacao;
        $campos['administradores_id_autorizacao'] = ($this->origem_autorizacao == 2) ? $administradorId : null;

        $rowVerifica = $this->lgpdAutorizacoesRepository->verificaTermoCondicoes($idDominio, $tipoTermo, $idPaciente);
        $campos['pacientes_id'] = $idPaciente;
        $campos['identificador'] = $idDominio;
        $campos['data_cad'] = date('Y-m-d H:i:s');
        if (!$rowVerifica) {
            $qr = $this->lgpdAutorizacoesRepository->store($idDominio, $campos);
        } else {

            $campos['data_alter'] = date('Y-m-d H:i:s');
            $qr = $this->lgpdAutorizacoesRepository->update($idDominio, $rowVerifica->id, $campos);
        }


        return $qr;
    }
}
