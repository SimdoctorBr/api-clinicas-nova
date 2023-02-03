<?php

namespace App\Repositories\Clinicas\Consulta;

use Illuminate\Support\Facades\DB;
use App\Repositories\BaseRepository;
use App\Helpers\Functions;

class ConsultaPedidoExameRepository extends BaseRepository {

    public function getHistoricoProcedimentosPagos($idDominio, $dadosFiltro = null, $dadosPaginacao = null) {

        $sqlFiltro = '';
        $ordenacao = 'ORDER BY dataProc desc';

        if (isset($dadosFiltro['pacienteId']) and ! empty($dadosFiltro['pacienteId'])) {
            $sqlFiltro .= " AND A.pacientes_id = {$dadosFiltro['pacienteId']} ";
        }

        if (isset($dadosFiltro['data']) and ! empty($dadosFiltro['data'])) {

            if (isset($dadosFiltro['dataFim']) and ! empty($dadosFiltro['dataFim'])) {
                $sqlFiltro .= " AND A.data_cad >='{$dadosFiltro['data']}' AND A.data_cad <= '{$dadosFiltro['dataFim']}'";
            } else {
                $sqlFiltro .= " AND A.data_cad ='{$dadosFiltro['data']}' ";
            }
        }



        $camposSql = "A.*,   AES_DECRYPT(C.nome_cript, '$this->ENC_CODE') as  nomeDoutor, D.nome as nomeUserCad, E.cod_ano as CodAnoOrcamento, E.codigo as CodOrcamento ";
        $from = "FROM consultas_pedidos_exames AS A
                                    LEFT JOIN consultas AS B
                                    ON A.consultas_id = B.id
                                    LEFT JOIN doutores AS C
                                    ON C.id = B.doutores_id
                                    LEFT JOIN administradores AS D
                                    ON D.id = A.administrador_id_cad
                                       LEFT JOIN orcamentos AS E
                                    ON E.id = A.orcamentos_id
                                    WHERE A.identificador = $idDominio 
                                        $sqlFiltro";

        if (empty($dadosPaginacao)) {

            $qr = $this->connClinicas()->select("SELECT $camposSql $from");
        } else {
            $qr = $this->paginacao($camposSql, $from, 'clinicas', $dadosPaginacao['page'], $dadosPaginacao['perPage']);
        }


        return $qr;
    }

    public function getItensByPedidoId($idDominio, $idPedidoExame) {


        $qr = $this->connClinicas()->select("SELECT A.*,   C.possui_parceiro,
                                     C.doutor_parceiro_id,
                                    AES_DECRYPT(D.nome_cript, '$this->ENC_CODE') as nomeParceiro,E.valor AS valorProc,
                                        C.codigo_proc
            FROM consultas_pedidos_exames_itens AS A
                            INNER JOIN consultas_pedidos_exames AS B
                            ON A.consultas_ped_exame_id = B.id
                                LEFT JOIN procedimentos AS C
                            ON C.idProcedimento = A.procedimentos_id
                               LEFT JOIN doutores AS D
                            ON D.id = C.doutor_parceiro_id
                            LEFT JOIN procedimentos_convenios_assoc AS E
                            ON (E.procedimentos_id = A.procedimentos_id AND A.convenios_id  = E.convenios_id)
                            WHERE A.identificador = $idDominio AND A.consultas_ped_exame_id = '$idPedidoExame'");
        return $qr;
    }

}
