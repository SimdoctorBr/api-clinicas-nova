<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\Clinicas\Financeiro;

use App\Services\BaseService;
use DateTime;
use App\Helpers\Functions;
use App\Repositories\Clinicas\Financeiro\FormaPagamentoRepository;

/**
 * Description of Activities
 *
 * @author ander
 */
class FormaPagamentoService extends BaseService {

    private $formaPagRepository;

    public function __construct() {
        $this->formaPagRepository = new FormaPagamentoRepository;
    }

    /**
     * 
     * @param type $idDominio
     * @param type $tipo   Pode ser consulta, orcamento, cateira_virtual
     * @param type $idTipo
     */
    public function getAll($idDominio, $dadosFiltor = null) {


        $qr = $this->formaPagRepository->getAll($idDominio);

        if ($qr) {
            $retorno = [];
            foreach ($qr as $row) {
                $retorno[] = [
                    'id' => $row->idTipo_pagamento,
                    'nome' => html_entity_decode(utf8_decode($row->tipo_pag_nome)),
                    'padrao' => $row->padrao,
                    'possuiTaxa' => $row->possui_taxa,
                    'percentualTaxa' => $row->percentual_taxa,
                ];
            }
            return $this->returnSuccess($retorno, '');
        } else {
            return $this->returnError([]);
        }
    }

}
