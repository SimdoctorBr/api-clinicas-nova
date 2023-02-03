<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\Clinicas\Financeiro\GatewayPagamentos;

use App\Services\BaseService;
use DateTime;
use App\Helpers\Functions;
use App\Services\Clinicas\Financeiro\GatewayPagamentos\CobrancaGatewayInterface;
use App\Services\PagSeguroApi\PagSeguroItensAPI;
use App\Services\PagSeguroApi\PagSeguroApiService;

/**
 * Description of Activities
 *
 *  Gera os links de pagamento pelo Pagseguro
 * @author ander
 */
class CobrancaPagSeguroService extends BaseService implements CobrancaGatewayInterface {

    public function create($idDominio, array $dados) {


        $PagSeguroItensAPI = new PagSeguroItensAPI;
        $PagSeguroItensAPI->setItemId('0001');
        $PagSeguroItensAPI->setItemDescription($dados['descricao']);
        $PagSeguroItensAPI->setItemAmount(number_format($dados['valor'], 2, '.', ''));
        $PagSeguroItensAPI->setItemQuantity(1);
        $PagSeguroItensAPI->setItemWeight(1000);
        $PagSeguroApiService = new PagSeguroApiService;
        $linkPagSeguro = $PagSeguroApiService->gerarLinkPagamentoConsulta($idDominio, $dados['idConsulta'], $dados['email'], $dados['ambiente'], $PagSeguroItensAPI);

        return $linkPagSeguro;
    }

    public function delete($idDominio, $id) {
        
    }

    public function refunds($idDominio, $id) {
        
    }

    public function update($idDominio, $id, array $dados) {
        
    }

}
