<?php

namespace App\Repositories\Clinicas;

use Illuminate\Support\Facades\DB;
use App\Repositories\BaseRepository;
use App\Helpers\Functions;

class PacientesAsaasPagamentosRepository extends BaseRepository {

    public function getAll($idDominio, $filtro) {

        $sqlBusca = '';

        if (isset($filtro['search']) and!empty($filtro['search'])) {
            $sqlBusca .= " AND (nome like '%" . $filtro['search'] . "%'  )";
        }
        if (isset($filtro['id']) and!empty($filtro['id'])) {
            $sqlBusca .= " AND id = " . $filtro['id'] . "";
        }

        $qr = $this->connClinicas()->select("SELECT * FROM  planos_beneficios WHERE identificador = '$idDominio' AND status = 1  $sqlBusca");
        return $qr;
    }

    public function store($idDominio, $dados) {

        $qr = $this->insertDB("paciente_assas_pagamentos", $dados, null, 'clinicas');
        return $qr;
    }

    public function update($idDominio, $id, $dados) {

        $qr = $this->updateDB("paciente_assas_pagamentos", $dados, "identificador = $idDominio AND id = $id", null, 'clinicas');
    }

    public function delete($idDominio, $id) {

        $qr = $this->updateDB("paciente_assas_pagamentos", ['status' => 0], "identificador = $idDominio AND id = $id", null, 'clinicas');
    }

    public function getAssinaturaById($id, $idDominio = null) {
        $sql = '';
        if (!empty($idDominio)) {
            $sql = " AND identificador  =  $idDominio";
        }
        $qr = $this->connClinicas()->select(" SELECT A.*
                            FROM paciente_assas_pagamentos AS A
                            WHERE A.tipo_registro= 1 AND  id= $id $sql");
        if (count($qr) > 0) {
            return $qr[0];
        } else {
            return false;
        }
        return $qr;
    }

    public function verificaAssinaturaAtivaPacienteId($idDominio, $pacienteId) {

        $qr = $this->connClinicas()->select(" SELECT A.*, B.nome AS nomePlano, B.descricao as descricaoPlano,B.id as idPlano,B.desconto_tipo, B.desconto_valor,
                (SELECT COUNT(*) FROM       paciente_assas_pagamentos WHERE 
                                       tipo_registro=2 
                                       AND pac_assas_pag_id = A.id AND data_vencimento < '" . date('Y-m-d') . "'
                                        AND  (status_cobranca =1 OR     status_cobranca =4)  
                            ) as totalPendencias
                            FROM paciente_assas_pagamentos AS A
                            INNER JOIN planos_beneficios AS B
                            ON A.plano_beneficio_id = B.id
                            WHERE A.identificador = $idDominio AND A.pacientes_id= $pacienteId 
                            AND A.tipo_registro= 1 AND A.STATUS = 1");
        if (count($qr) > 0) {
            return $qr[0];
        } else {
            return false;
        }
    }

    /**
     * 
     * @param type $identificador
     * @param type $idPacAssasPag
     * @param type $tipoRetorno 1 - True ou False, 2 - lista de pagamentos pendentes
     * @return type
     */
    public function verificaAssinaturaPendencias($idDominio, $idPacAssasPag, $tipoRetorno = 1) {

        $qrVerificaPrimeira = $this->connClinicas()->select("SELECT COUNT(*) as total FROM       paciente_assas_pagamentos AS A WHERE 
                            A.tipo_registro=2 
                           AND A.pac_assas_pag_id = '$idPacAssasPag' AND (A.status_cobranca =2 OR A.status_cobranca =3)");
        $rowPrimeiro = $qrVerificaPrimeira[0];

        $qr = $this->connClinicas()->select("SELECT * FROM       paciente_assas_pagamentos AS A WHERE 
                            A.tipo_registro=2 
                           AND A.pac_assas_pag_id = '$idPacAssasPag' AND A.data_vencimento < '" . date('Y-m-d') . "'
                           AND  (A.status_cobranca =1 OR     A.status_cobranca =4)  ");

        if ($tipoRetorno == 1) {
            return (
                    ($rowPrimeiro->total == 0 )
                    or
                    ($rowPrimeiro->total > 0 and count($qr) > 0)
                    ) ? true : false;
        }
        if (count($qr) > 0) {
            return $qr[0];
        } else {
            return false;
        }
    }

    public function atualizaLoteAssinatura($idDominio, $planoBeneficioId) {
        $qr = $this->updateDB("paciente_assas_pagamentos", ['alterar_plano_assas' => 1], "identificador = $idDominio AND tipo_registro = 1 "
                . "AND `status`=1 AND plano_beneficio_id=  $planoBeneficioId", null, 'clinicas');
    }

    public function verificaHistoricoAssinatura($idDominio, $pacienteId, $dadosFiltro = null, $inicioReg = null, $limit = null) {

        $sqlFiltro = '';

        if (isset($dadosFiltro['data']) and!empty($dadosFiltro['data'])) {
            if (isset($dadosFiltro['dataFim']) and!empty($filtro['dataFim'])) {
                $sqlFiltro .= " AND DATE_FORMAT(A.data_cad, '%Y-%m-%d') >= '" . $dadosFiltro['data'] . "' AND DATE_FORMAT(A.data_cad, '%Y-%m-%d') <= '" . $dadosFiltro['dataFim'] . "'";
            } else if (!empty($filtro['data'])) {
                $sqlFiltro .= " AND DATE_FORMAT(A.data_cad, '%Y-%m-%d') = '" . $dadosFiltro['data'] . "'";
            }
        }


        $orderBy = "A.data_cad DESC";
        $camposSQl = "A.*, B.nome AS nomePlano,
                (SELECT COUNT(*) FROM       paciente_assas_pagamentos WHERE 
                                       tipo_registro=2 
                                       AND pac_assas_pag_id = A.id AND data_vencimento < '" . date('Y-m-d') . "'
                                        AND  (status_cobranca =1 OR     status_cobranca =4)  
                            ) as totalPendencias
            ";

        $from = "FROM paciente_assas_pagamentos AS A
                            INNER JOIN planos_beneficios AS B
                            ON A.plano_beneficio_id = B.id
                            WHERE A.identificador = $idDominio AND A.pacientes_id= $pacienteId 
                            AND A.tipo_registro= 1 $sqlFiltro ";

        if (!empty($inicioReg) and!empty($limit)) {
//            var_dump("SELECT $camposSQl $from ORDER BY $orderBy");
            $qr = $this->paginacao($camposSQl, $from, 'clinicas', $inicioReg, $limit, false, $orderBy);
        } else {
            $qr = $this->connClinicas()->select("SELECT $camposSQl $from   ORDER BY $orderBy ");
        }
        return $qr;
    }

    public function verificaHistoricoAssinaturaCobrancas($idDominio, $pacienteId, $pacAssasPagAssinturaId = null, $idPacAssasPag = null, $filtro = null, $inicioReg = null, $limit = null) {


        $sqlFiltro = '';

        if (!empty($filtro)) {
            foreach ($filtro as $tipo => $valor) {

                if (empty($valor)) {
                    continue;
                }
                switch ($tipo) {
                    case 'data':
                        if (isset($filtro['data_fim']) and!empty($filtro['data_fim'])) {

                            $sqlFiltro .= " AND DATE_FORMAT(A.data_cad, '%Y-%m-%d') >= '" . $valor . "' AND DATE_FORMAT(A.data_cad, '%Y-%m-%d') <= '" . $filtro['data_fim'] . "'";
                        } else if (!empty($filtro['data'])) {
                            $sqlFiltro .= " AND DATE_FORMAT(A.data_cad, '%Y-%m-%d') = '" . $valor . "'";
                        }
                        break;
                    case 'dataVencimento':
                        if (isset($filtro['dataVencimentoFim']) and!empty($filtro['dataVencimentoFim'])) {

                            $sqlFiltro .= " AND DATE_FORMAT(A.data_vencimento, '%Y-%m-%d') >= '" . $valor . "' AND DATE_FORMAT(A.data_vencimento, '%Y-%m-%d') <= '" . $filtro['dataVencimentoFim'] . "'";
                        } else if (!empty($filtro['dataVencimento'])) {
                            $sqlFiltro .= " AND DATE_FORMAT(A.data_vencimento, '%Y-%m-%d') = '" . $valor . "'";
                        }
                        break;
                    case 'anterioresDataAtual':
                        if (!empty($filtro['anterioresDataAtual'])) {
                            $sqlFiltro .= " AND (A.data_pagamento <= '" . date('Y-m-d') . "' OR A.data_vencimento <= '" . date('Y-m-d') . "')";
                        }
                        break;
                }
            }
        }
        if (!empty($pacAssasPagAssinturaId)) {
            $sqlFiltro .= " AND A.pac_assas_pag_id = $pacAssasPagAssinturaId";
        }
        if (!empty($idPacAssasPag)) {
            $sqlFiltro .= " AND A.id = $idPacAssasPag";
        }


        $orderBy = "A.data_cad DESC";
        $camposSQl = 'A.*, B.nome AS nomePlano ';

        $from = "FROM paciente_assas_pagamentos AS A
                            INNER JOIN planos_beneficios AS B
                            ON A.plano_beneficio_id = B.id
                            WHERE A.identificador = $idDominio AND A.pacientes_id= $pacienteId 
                            AND A.tipo_registro= 2  $sqlFiltro";

        if (!empty($inicioReg) and!empty($limit)) {
            var_dump("SELECT $camposSQl $from ORDER BY $orderBy");
            ;
            $qr = $this->paginacao($camposSQl, $from, 'clinicas', $inicioReg, $limit, false, $orderBy);
        } else {
            $qr = $this->connClinicas()->select("SELECT $camposSQl $from   ORDER BY $orderBy ");
        }
        return $qr;
    }

    public function cancelarPlano($idDominio, $idPacienteAssasPAg) {
        $campos['status'] = 0;
        $campos['data_cancelamento'] = date('Y-m-d H:i:s');
        $this->updateDB('paciente_assas_pagamentos', $campos, " identificador = $idDominio AND id = $idPacienteAssasPAg LIMIT 1", null, 'clinicas');
    }

    public function getCobrancaByIdRegistroAssas($idRegistroAssas, $idDominio = null, $comDadosPaciente = false) {

        $sql = '';
        $join = $campos = '';
        if (!empty($idDominio)) {
            $sql = " AND identificador  =  $idDominio";
        }
        if ($comDadosPaciente) {
            $campos = ",CONCAT(AES_DECRYPT(B.nome_cript, '$this->ENC_CODE'),' ', AES_DECRYPT(B.sobrenome_cript, '$this->ENC_CODE')) as nomePaciente,   
                 (SELECT id_registro FROM paciente_assas_pagamentos WHERE id = A.pac_assas_pag_id) AS idAssinaturaAssas,
                 C.nome as nomePlano      
                ";
            $join = " INNER JOIN pacientes as B ON A.pacientes_id = B.id";
        }

        $qr = $this->connClinicas()->select(" SELECT A.* $campos
                            FROM paciente_assas_pagamentos AS A
                            $join
                                LEFT JOIN planos_beneficios as C
                                ON C.id = A.plano_beneficio_id
                            WHERE  A.tipo_registro= 2 AND  A.id_registro= '$idRegistroAssas' $sql");
        if (count($qr) > 0) {
            return $qr[0];
        } else {
            return false;
        }
    }

    public function getCobrancaById($idDominio, $idPagAssasPac) {
        $qr = $this->connClinicas()->select(" SELECT A.* 
                            FROM paciente_assas_pagamentos AS A
                            WHERE  A.tipo_registro= 2 AND  id= '$idPagAssasPac'");
        if (count($qr) > 0) {
            return $qr[0];
        } else {
            return false;
        }
    }

    public function insertHistoricoAlterPlBeneficio($idDominio, $dados) {
        $qr = $this->insertDB('pacientes_pl_ben_hist', $dados, null, 'clinicas');
        return $qr;
    }

    public function vinculaCobrancaMudancaPlanoHist($idDominio, $idPacPLanoHistorico, $idPacPagAssasId) {

        $qr = $this->updateDB("pacientes_pl_ben_hist", [
            'pac_assas_pag_id_cobranca' => $idPacPagAssasId
                ], " identificador = $idDominio AND id = $idPacPLanoHistorico LIMIT 1", null, 'clinicas');
        return $qr;
    }

    public function excluirMudancaPlanoHist($idDominio, $pacPlanoHistId, $pacAssasPagIdCobranca = null) {
        if (!empty($pacAssasPagIdCobranca)) {
            $qr = $this->connClinicas()->select("DELETE FROM paciente_assas_pagamentos WHERE identificador = $idDominio AND   id = $pacAssasPagIdCobranca  LIMIT 1");
        }
        $qr = $this->connClinicas()->select("DELETE FROM pacientes_pl_ben_hist WHERE identificador = $idDominio AND   id = $pacPlanoHistId AND alterado =0 LIMIT 1");
        return $qr;
    }

    public function verificaMudancaPlanoHist($idDominio, $pacienteAssasPagamentoId) {

        $qr = $this->connClinicas()->select("SELECT A.*, B.valor AS valorPlanoPara, B.nome as nomePlanoPara, C.valor as valorPlanoDe, C.nome as nomePlanoDe, D.id_registro as idCobrancaAssas,D.link_pagamento FROM pacientes_pl_ben_hist AS A
                    INNER JOIN  planos_beneficios AS B
                    ON A.pl_beneficio_id_para = B.id
                      INNER JOIN  planos_beneficios AS C
                    ON A.pl_beneficio_id_de = C.id
                    LEFT JOIN paciente_assas_pagamentos as D
                    ON D.id = A.pac_assas_pag_id_cobranca
                     WHERE A.identificador = $idDominio
                     AND     A.pac_assas_pag_id = $pacienteAssasPagamentoId 
                                AND A.alterado =0");

        if (count($qr) > 0) {
            return $qr[0];
        } else {
            return false;
        }
    }

}
