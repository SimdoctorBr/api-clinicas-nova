<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\Clinicas\Paciente\PlanoBeneficio;

use App\Services\BaseService;
use App\Repositories\Clinicas\PlanoBeneficio\PlanoBeneficioRepository;
use App\Repositories\Clinicas\PlanoBeneficio\PlanoBeneficioConsOrcRepository;

/**
 * Description of Activities
 *
 * @author ander
 */
class PlanoBeneficioConsOrcService extends BaseService {

    private $id;
    private $tipo;
    private $id_tipo;
    private $idDominio;
    private $planos_beneficios_id;
    private $pl_percentual;
    private $pl_nome;
    private $possui_pendencia;

    public function setPossui_pendencia($possui_pendencia) {
        $this->possui_pendencia = $possui_pendencia;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function setTipo($tipo) {
        $this->tipo = $tipo;
    }

    public function setId_tipo($id_tipo) {
        $this->id_tipo = $id_tipo;
    }

    public function setIdDominio($idDominio) {
        $this->idDominio = $idDominio;
    }

    public function setPlanos_beneficios_id($planos_beneficios_id) {
        $this->planos_beneficios_id = $planos_beneficios_id;
    }

    public function setPl_percentual($pl_percentual) {
        $this->pl_percentual = $pl_percentual;
    }

    public function setPl_nome($pl_nome) {
        $this->pl_nome = $pl_nome;
    }

    public function insertUpdate() {

        $campos['planos_beneficios_id'] = $this->planos_beneficios_id;
        $campos['pl_percentual'] = $this->pl_percentual;
        $campos['pl_nome'] = $this->pl_nome;
        $campos['possui_pendencia'] = $this->possui_pendencia;

        $PlanoBeneficioConsOrcRepository = new PlanoBeneficioConsOrcRepository;
        $qrVerifica = $PlanoBeneficioConsOrcRepository->getByIdTipo($this->idDominio, $this->tipo, $this->id_tipo);

        if (!$qrVerifica) {
            $campos['tipo'] = $this->tipo;
            $campos['id_tipo'] = $this->id_tipo;
            $campos['identificador'] = $this->idDominio;
            $qr = $PlanoBeneficioConsOrcRepository->store($this->idDominio, $campos);
        } else {
            $row = $qrVerifica;
            $qr = $PlanoBeneficioConsOrcRepository->update($this->idDominio, $row->id, $campos);
        }
        return $qr;
    }

}
