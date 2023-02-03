<?php

namespace App\Services\Clinicas\Financeiro\GatewayPagamentos;

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPInterface.php to edit this template
 */

/**
 *
 * @author ander
 */
interface CobrancaGatewayInterface {

    public function create($idDominio, Array $dados);

    public function update($idDominio, $id, Array $dados);

    public function delete($idDominio, $id);

    public function refunds($idDominio, $id);
}
