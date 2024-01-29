<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\Clinicas;

use App\Services\BaseService;
use DateTime;
use App\Repositories\Clinicas\AprovacaoAlteracoesRepository;
use App\Helpers\Functions;
use Illuminate\Support\Facades\Mail;

/**
 * Description of Activities
 *
 * @author ander
 */
class AprovacoesAlteracaoService extends BaseService {

    private $tipo;
    private $id_tipo;
    private $identificador;
    private $json_alteracao;
    private $descricao;

    
    public function setDescricao($descricao) {
        $this->descricao = $descricao;
    }

        /**
     * 
     * @param type $tipo 1 - tipo
     */
    public function setTipo($tipo) {
        $this->tipo = $tipo;
    }

    public function setId_tipo($id_tipo) {
        $this->id_tipo = $id_tipo;
    }

    public function setIdentificador($identificador) {
        $this->identificador = $identificador;
    }

    public function setJson_alteracao($json_alteracao) {
        $this->json_alteracao = $json_alteracao;
    }

    public function insert() {

        $AprovacaoAlteracoesRepository = new AprovacaoAlteracoesRepository;
        $campos['tipo'] = $this->tipo;
        $campos['id_tipo'] = $this->id_tipo;
        $campos['identificador'] = $this->identificador;
        $campos['data_cad'] = date('Y-m-d H:i:s');
        $campos['administrador_id_cad'] = (isset(auth('clinicas')->user()->id)) ? auth('clinicas')->user()->id : null;
        $campos['administrador_nome_cad'] = (isset(auth('clinicas')->user()->nome)) ? auth('clinicas')->user()->nome : null;
        $campos['json_alteracao'] = $this->json_alteracao;
        $campos['descricao'] = $this->descricao;
       return $qr = $AprovacaoAlteracoesRepository->store($this->identificador, $campos);
    }
}
