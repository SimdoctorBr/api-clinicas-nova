<?php
namespace App\Services\MultiatendimentoAPI;
/*
 * 
  /**
 * Description of MultiatendimentoAPI
 *
 * @author ander
 */

class MultiatendimentoContratacao {

    private $Conexao = 49;
    private $cliente_id;
    private $data_inicio;
    private $qnt_profissionais;
    private $valor;
    private $dominio_id;
    private $id_registro_cob_asaas;
    private $id_asaas_cli_pagamentos_assin;
    private $tipo_contrato;
    private $status;

    public function __construct($ConexaoClass) {
        $this->Conexao = $ConexaoClass;
    }

    public function setStatus($status) {
        $this->status = $status;
    }

    public function setTipo_contrato($tipo_contrato) {
        $this->tipo_contrato = $tipo_contrato;
    }

    public function setDominio_id($dominio_id) {
        $this->dominio_id = $dominio_id;
    }

    public function setPrecoBase($precoBase) {
        $this->precoBase = $precoBase;
    }

    public function setCliente_id($cliente_id) {
        $this->cliente_id = $cliente_id;
    }

    public function setData_inicio($data_inicio) {
        $this->data_inicio = $data_inicio;
    }

    public function setQnt_profissionais($qnt_profissionais) {
        $this->qnt_profissionais = $qnt_profissionais;
    }

    public function setValor($valor) {
        $this->valor = $valor;
    }

    public function setId_registro_cob_asaas($id_registro_cob_asaas) {
        $this->id_registro_cob_asaas = $id_registro_cob_asaas;
    }

    public function setId_asaas_cli_pagamentos($id_asaas_cli_pagamentos_assin) {
        $this->id_asaas_cli_pagamentos_assin = $id_asaas_cli_pagamentos_assin;
    }

    private function formulaCalc($qntDoutores) {

//        return $this->precoBase + (($qntDoutores -1)*);
    }

    ////
    public function calculaPreco($qntDoutores) {
        $qr = $this->Conexao->select("SELECT * FROM multidialogo_precos WHERE $qntDoutores >= qnt_min_prof  AND $qntDoutores <= qnt_max_prof  ", 'gerenciamento');
        return $qr->fetch(PDO::FETCH_OBJ);
    }

    public function insertContratacao() {
        $campos['cliente_id'] = $this->cliente_id;
        $campos['dominio_id'] = $this->dominio_id;
        $campos['qnt_profissionais'] = $this->qnt_profissionais;
        $campos['valor'] = $this->valor;
        $campos['tipo_contrato'] = $this->tipo_contrato;
        $campos['data_inicio'] = $this->data_inicio;
        $campos['id_asaas_cli_pagamentos_assin'] = $this->id_asaas_cli_pagamentos_assin;
        $campos['id_registro_cob_asaas'] = $this->id_registro_cob_asaas;
        $campos['categoria'] = 1;
        $campos['administrador_id_cad'] = $_SESSION['id_LOGADO'];
        $campos['data_cad'] = date('Y-m-d H:i:s');
        if (!empty($this->status)) {
            $campos['status'] = $this->status;
        }

        return $this->Conexao->insere('clientes_contratacao_adicional', $campos, 'gerenciamento');
    }

    public function pagar($identificador, $idContratacao, $dataPago = null) {
        $campos['status'] = 2;
        $campos['data_pago'] = (!empty($dataPago)) ? $dataPago : date('Y-m-d H:i:s');
        return $this->Conexao->update('clientes_contratacao_adicional', $campos, " dominio_id = $identificador AND id = $idContratacao LIMIT 1", 'gerenciamento');
    }

    public function cancelaContratacao($identificador, $idContratacao, $dataFinalCancelado) {

        $campos['data_cancelamento'] = date('Y-m-d H:i:s');
        $campos['prazo_final_cancelamento'] = $dataFinalCancelado;
        $campos['administrador_id_cancel'] = $_SESSION['id_LOGADO'];

        return $this->Conexao->update('clientes_contratacao_adicional', $campos, " dominio_id = $identificador AND id = $idContratacao LIMIT 1", 'gerenciamento');
    }

    public function verificaContratacaoAPagarBydIdCobranca($idAsaasCobranca) {

        $qr = $this->Conexao->select("SELECT * FROM clientes_contratacao_adicional AS A
                 WHERE id_registro_cob_asaas = '$idAsaasCobranca' AND A.categoria = 1 AND A.`status` = 1", 'gerenciamento');
        return $qr->fetch(PDO::FETCH_OBJ);
    }

    public function verificaContratacaoAPagar($identificador) {
        $qr = $this->Conexao->select("SELECT * FROM clientes_contratacao_adicional AS A
                 WHERE dominio_id = $identificador AND A.categoria = 1 AND A.`status` = 1", 'gerenciamento');
        return $qr->fetch(PDO::FETCH_OBJ);
    }

    public function verificaContratacaoPaga($identificador) {
        $datahoje = date('Y-m-d');
        $qr = $this->Conexao->select("SELECT * FROM clientes_contratacao_adicional AS A
                 WHERE dominio_id = $identificador AND A.categoria = 1 AND A.`status` = 2
                     AND (prazo_final_cancelamento IS  NULL OR  prazo_final_cancelamento >= '$datahoje')
                ", 'gerenciamento');
        return $qr->fetch(PDO::FETCH_OBJ);
    }

    public function verificaContratacaoPagaClientesId($clientes_id, $tipoRetorno = 1, $idContratacaoExceto = null) {
        $datahoje = date('Y-m-d');
        if (!empty($idContratacaoExceto)) {
            $sql = " AND id != $idContratacaoExceto";
        }
        $qr = $this->Conexao->select("SELECT * FROM clientes_contratacao_adicional AS A
                 WHERE cliente_id = $clientes_id AND A.categoria = 1 AND A.`status` = 2 
                      AND (prazo_final_cancelamento IS  NULL OR  prazo_final_cancelamento >= '$datahoje')
                          $sql
                ", 'gerenciamento');
        $retorno = null;
        if ($tipoRetorno == 1) {
            return $qr->fetchAll(PDO::FETCH_OBJ);
        } elseif ($tipoRetorno == 2) {
            foreach ($qr->fetchAll(PDO::FETCH_OBJ) as $row) {
                $retorno['valorTotal'] += $row->valor;
            }
        }
        return $retorno;
    }

    public function atualizaAssinaturaTotalDoutores($identificador, $clientesId) {
        $rowContratacaoPaga = $this->verificaContratacaoPaga($identificador);
        if ($rowContratacaoPaga) {
            $Clientes = new Cliente();
            $rowCliente = $Clientes->getById($clientesId);
            $valorPlanoNovo = $rowCliente->valor;

            $ClientesAssasPagamentos = new ClientesAssasPagamentos();
            $dadosFiltro['clienteId'] = $clientesId;
            $dadosFiltro['status'] = 1;
            $rowAssinaturaAtiva = $ClientesAssasPagamentos->getAllAssinaturas(false, $dadosFiltro);

            $Doutor = new Doutor_Clinicas();
            $rowDoutores = $Doutor->getTotaisDoutores($identificador);
            $totalDoutores = $rowDoutores->totalDoutor + $rowDoutores->totalParceiroAgenda;

            $rowPreco = $this->calculaPreco($totalDoutores);

            $rowMultidialogoPago = $this->verificaContratacaoPagaClientesId($rowCliente->id, 2, $rowContratacaoPaga->id);
            if (isset($rowMultidialogoPago['valorTotal'])) {
                $valorPlanoNovo = $valorPlanoNovo + $rowMultidialogoPago['valorTotal'];
            }
            $valorPlanoNovo += $rowPreco->preco;

            //DEscriçao
            $Dominio = new Dominio();
            $dadosDominios = $Dominio->getByClienteId($rowCliente->id, 2);
//            var_Dump($rowContratacaoPaga);
//            var_Dump($totalDoutores);
//            var_Dump($valorPlanoNovo);

            $AssasApiAssinaturas = new AssasApiAssinaturas(AMBIENTE_ASSAS_SIMDOCTOR, APIKEY_ASSAS_SIMDOCTOR);
            $rowAssinaturaASAAS = $AssasApiAssinaturas->getById($rowAssinaturaAtiva[0]->id_registro);
            $dadosAssinaturaUpdate['updatePendingPayments'] = 'true';
            $dadosAssinaturaUpdate['value'] = $valorPlanoNovo;
            $dadosAssinaturaUpdate['cycle'] = $rowAssinaturaASAAS->cycle; //   AssasApiCommons::sistemaToTipoPeriodoApiAssas($periodoPagAsaas);
            $dadosAssinaturaUpdate['description'] = $dadosDominios."\n Multidialogo $totalDoutores profissionais";
            $updateAssinatura = $AssasApiAssinaturas->updateFieldsAssinatura($rowAssinaturaAtiva[0]->id_registro, $dadosAssinaturaUpdate);
//            var_Dump($updateAssinatura);
//            var_Dump($rowContratacaoPaga->id);
            if (!isset($updateAssinatura->errors) and!empty($updateAssinatura)) {

                $this->Conexao->update('clientes_contratacao_adicional', [
                    'qnt_profissionais' => $totalDoutores,
                    'valor' => $rowPreco->preco
                        ], "dominio_id = $identificador AND id = $rowContratacaoPaga->id LIMIT 1", 'gerenciamento');
            }
        }
    }

}
