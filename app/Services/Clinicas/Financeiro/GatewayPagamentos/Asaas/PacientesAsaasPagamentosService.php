<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\Clinicas\Financeiro\GatewayPagamentos\Asaas;

use App\Services\BaseService;
use DateTime;
use App\Helpers\Functions;
use App\Repositories\Clinicas\PacientesAsaasPagamentosRepository;

/**
 * Description of Activities
 *
 * @author ander
 */
class PacientesAsaasPagamentosService extends BaseService {

    private $id;
    private $tipo_registro;
    private $id_registro;
    private $idDominio;
    private $pacientes_id;
    private $link_pagamento;
    private $valor;
    private $id_assinatura;
    private $plano_beneficio_id;
    private $link_boleto_pdf;
    private $data_vencimento;
    private $data_pagamento;
    private $assas_cobrancas_tp_id;
    private $forma_pag;
    private $link_comprovante;
    private $pac_assas_pag_id;
    private $consultas_id;
    private $valor_bruto;
    private $plano_percent_desconto;
    private $categoria_cobranca;
    private $numero_fatura;
    private $netValue;
    private $cobranca_alteracao;

    public function setCobranca_alteracao($cobranca_alteracao) {
        $this->cobranca_alteracao = $cobranca_alteracao;
    }

    public function setNetValue($netValue) {
        $this->netValue = $netValue;
    }

    public function setNumero_fatura($numero_fatura) {
        $this->numero_fatura = $numero_fatura;
    }

    public function setCategoria_cobranca($categoria_cobranca) {
        $this->categoria_cobranca = $categoria_cobranca;
    }

    public function setValor_bruto($valor_bruto) {
        $this->valor_bruto = $valor_bruto;
    }

    public function setPlano_percent_desconto($plano_percent_desconto) {
        $this->plano_percent_desconto = $plano_percent_desconto;
    }

    public function setConsultas_id($consultas_id) {
        $this->consultas_id = $consultas_id;
    }

    /**
     * Usado para cobranças de uma assinatura
     * @param type $pac_assas_pag_id
     */
    public function setPac_assas_pag_id($pac_assas_pag_id) {
        $this->pac_assas_pag_id = $pac_assas_pag_id;
    }

    public function setData_pagamento($data_pagamento) {
        $this->data_pagamento = $data_pagamento;
    }

    public function setLink_comprovante($link_comprovante) {
        $this->link_comprovante = $link_comprovante;
    }

    public function setForma_pag($forma_pag) {
        $this->forma_pag = $forma_pag;
    }

    public function setAssas_cobrancas_tp_id($assas_cobrancas_tp_id) {
        $this->assas_cobrancas_tp_id = $assas_cobrancas_tp_id;
    }

    public function setData_vencimento($data_vencimento) {
        $this->data_vencimento = $data_vencimento;
    }

    public function setLink_boleto_pdf($link_boleto_pdf) {
        $this->link_boleto_pdf = $link_boleto_pdf;
    }

    public function setPlano_beneficio_id($plano_beneficio_id) {
        $this->plano_beneficio_id = $plano_beneficio_id;
    }

    public function setId($id) {
        $this->id = $id;
    }

    /**
     * 
     * @param type $tipo_registro 1-Assinaturas, 2-cobrança, 3 - Links de pagamento
     */
    public function setTipo_registro($tipo_registro) {
        $this->tipo_registro = $tipo_registro;
    }

    public function setId_registro($id_registro) {
        $this->id_registro = $id_registro;
    }

    public function setIdDominio($idDominio) {
        $this->idDominio = $idDominio;
    }

    public function setPacientes_id($pacientes_id) {
        $this->pacientes_id = $pacientes_id;
    }

    public function setLink_pagamento($link_pagamento) {
        $this->link_pagamento = $link_pagamento;
    }

    public function setValor($valor) {
        $this->valor = $valor;
    }

    public function setId_assinatura($id_assinatura) {
        $this->id_assinatura = $id_assinatura;
    }

    public function insertUpdate() {

        $PacientesAsaasPagamentosRepository = new PacientesAsaasPagamentosRepository;

        $campos['tipo_registro'] = $this->tipo_registro;
        $campos['id_registro'] = $this->id_registro;
        $campos['identificador'] = $this->idDominio;
        $campos['pacientes_id'] = $this->pacientes_id;
        $campos['categoria_cobranca'] = $this->categoria_cobranca;
        $campos['data_cad'] = date('Y-m-d H:i:s');
        $campos['status'] = 1;
        $campos['link_pagamento'] = $this->link_pagamento;
        $campos['link_boleto_pdf'] = $this->link_boleto_pdf;
        $campos['valor_bruto'] = $this->valor_bruto;
        $campos['valor'] = $this->valor;
        $campos['id_assinatura'] = $this->id_assinatura;
        $campos['plano_beneficio_id'] = $this->plano_beneficio_id;
        $campos['plano_percent_desconto'] = $this->plano_percent_desconto;
        $campos['data_vencimento'] = $this->data_vencimento;
        $campos['assas_cobrancas_tp_id'] = $this->assas_cobrancas_tp_id;
        $campos['forma_pag'] = $this->forma_pag;
        $campos['link_comprovante'] = $this->link_comprovante;
        $campos['netValue'] = $this->netValue;

        if (!empty($this->numero_fatura)) {
            $campos['numero_fatura'] = $this->numero_fatura;
        }
        if (!empty($this->consultas_id)) {
            $campos['consultas_id'] = $this->consultas_id;
        }
        if (!empty($this->data_pagamento)) {
            $campos['data_pagamento'] = $this->data_pagamento;
        }
        if (!empty($this->pac_assas_pag_id)) {
            $campos['pac_assas_pag_id'] = $this->pac_assas_pag_id;
        }
        if (!empty($this->cobranca_alteracao)) {
            $campos['cobranca_alteracao'] = $this->cobranca_alteracao;
        }

        if (!empty($this->id)) {
            $campos['data_alter'] = date('Y-m-d H:i:s');
            $PacientesAsaasPagamentosRepository->update($this->idDominio, $this->id);
            return $this->id;
        } else {
            return $PacientesAsaasPagamentosRepository->store($this->idDominio, $campos);
        }
    }

    public function statusCobrancaPorId($idStatusPagamento) {

        switch ($idStatusPagamento) {
            case 0: return ['status' => 'Cancelado', 'color' => '#e96b4a'];
                break;
            case 1: return ['status' => 'Aguardando pagamento', 'color' => '#dc8622'];
                break;
            case 2: return ['status' => 'Pago', 'color' => '#3c763d'];
                break;
            case 3: return ['status' => 'Confirmado', 'color' => '#dc8622'];
                break;
            case 4: return ['status' => 'Vencida', 'color' => '#e96b4a'];
                break;
        }
    }

    public function insertHistoricoAlterPlBeneficio($idDominio, $pacienteId, $plBenOrigemId, $plBenOrigemNome,
            $plBenOrigemDestinoId, $plBenOrigemDestinoNome, $pacAssasPagId = null, $dataUltimoPag = null, $tipoAlteracao = null) {
        $campos['identificador'] = $idDominio;
        $campos['pacientes_id'] = $pacienteId;
        $campos['pl_beneficio_id_de'] = $plBenOrigemId;
        $campos['pl_beneficio_nome_de'] = $plBenOrigemNome;
        $campos['pl_beneficio_id_para'] = $plBenOrigemDestinoId;
        $campos['pl_beneficio_nome_para'] = $plBenOrigemDestinoNome;
        $campos['data_cad'] = date('Y-m-d H:i:s');
        if (!empty($dataUltimoPag)) {
            $campos['data_ultimo_pag'] = $dataUltimoPag;
        }
        if (!empty($pacAssasPagId)) {
            $campos['pac_assas_pag_id'] = $pacAssasPagId;
        }
        if (!empty($tipoAlteracao)) {
            $campos['tipo_alteracao'] = $tipoAlteracao;
        }

        $PacientesAsaasPagamentosRepository = new PacientesAsaasPagamentosRepository;
        $qr = $PacientesAsaasPagamentosRepository->insertHistoricoAlterPlBeneficio($idDominio, $campos);

        return $this->returnSuccess(['id' => $qr]);
    }

    public function calculoProporcionalMudancaPlano($valorAtual, $valorNovo, $dataAtual, $dataUltimoPag) {

        $dataProxPag = date('Y-m-d', strtotime($dataUltimoPag . '+1 month'));

//        $dataHoje = new DateTime('2023-01-07');
        $dataHoje = new DateTime($dataAtual);
        $dataUltimoPag = new DateTime($dataUltimoPag);
        $diff = $dataHoje->diff($dataUltimoPag);
        $diffDiasPagos = $diff->d;
        $ultimoDiaMes = date('t', strtotime($dataAtual));

//         var_Dump($diffDiasPagos);  
        if ($diffDiasPagos == 0) {
            $valorNovoDiario = ($valorNovo / 30);
            $valorNovoAPagar = $valorNovoDiario * (30 - $diffDiasProximo) - $valorAtualDiarioRestante;
        } else {

            $diffDiasProximo = $dataHoje->diff($dataUltimoPag);
            $diffDiasProximo = $diffDiasProximo->d;

//            $valorAtualDiarioAntigo = ($valorAtual / 30);
//            $valorAtualDiarioPago = $valorAtualDiarioAntigo * $diffDiasPagos;
//            $valorAtualDiarioRestante = $valorAtualDiario * (30 - $diffDias);

            $valorNovoDiario = ($valorNovo / 30);
            $valorNovoAPagar = $valorNovoDiario * (30 - $diffDiasProximo);

//            var_dump('Atual = 2023-01-07');
//            var_dump('Ultimo pag = ' . $dataUltimoPag->format('Y-m-d'));
//            var_dump('Diferença dias pagos = ' . $diffDiasPagos);
//            var_dump('Valor dias pagos = R$ ' . $valorAtualDiarioPago);
//            var_dump('Valor diario novo = R$ ' . $valorNovoDiario);
//            var_dump('Valor a pagar = R$ ' . $valorNovoAPagar);
//            var_dump('Dif dias novo =  ' . (30 - $diffDiasProximo));
//            var_Dump($dataAtual);
//            var_Dump($dataUltimoPag);
//            var_Dump($diffDiasPagos);
//        var_Dump($dataUltimoPag);
//        var_Dump($valorAtualDiarioPago);
//        var_Dump($valorAtualDiarioRestante);
//        var_Dump('novo: ' . $valorNovoAPagar);
        }
        return [
            'difDiasProximo' => $diffDiasProximo,
            'valorNovoDiario' => $valorNovoDiario,
            'valorNovo' => $valorNovoAPagar,
        ];
    }

    public function vinculaCobrancaMudancaPlanoHist($idDominio, $idPacPLanoHistorico, $idPacPagAssasId) {
        $PacientesAsaasPagamentosRepository = new PacientesAsaasPagamentosRepository;
        $result = $PacientesAsaasPagamentosRepository->vinculaCobrancaMudancaPlanoHist($idDominio, $idPacPLanoHistorico, $idPacPagAssasId);
        return $result;
    }

    public function verificaMudancaPlanoHist($idDominio, $pacienteAssasPagamentoId) {

        $PacientesAsaasPagamentosRepository = new PacientesAsaasPagamentosRepository;
        $result = $PacientesAsaasPagamentosRepository->verificaMudancaPlanoHist($idDominio, $pacienteAssasPagamentoId);
        if ($result) {
            return $this->returnSuccess($result);
        } else {
            return $this->returnError(null, 'Sem alterações de plano.');
        }
    }

//    public function getCobrancaById($idDominio, $idPagAssasPac) {
//
//        $PacientesAsaasPagamentosRepository = new PacientesAsaasPagamentosRepository;
//        $rowCobranca = $PacientesAsaasPagamentosRepository->getCobrancaById($idDominio, $idPagAssasPac);
//
//        return $rowCobranca;
//    }
}
