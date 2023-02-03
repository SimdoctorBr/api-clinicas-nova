<?php

namespace App\Repositories\Clinicas\Consulta;

use Illuminate\Support\Facades\DB;
use App\Repositories\BaseRepository;
use App\Helpers\Functions;

class ConsultaProcedimentoRepository extends BaseRepository {

    public function getHistoricoProcedimentosPagos($idDominio, $dadosFiltro = null, $dadosPaginacao = null) {

        $sqlFiltro = '';
        $ordenacao = 'ORDER BY dataProc desc';

        if (isset($dadosFiltro['pacienteId']) and ! empty($dadosFiltro['pacienteId'])) {
            $sqlFiltro .= " AND (B.pacientes_id = {$dadosFiltro['pacienteId']} OR D.paciente_id = {$dadosFiltro['pacienteId']})";
        }

        if (isset($dadosFiltro['data']) and ! empty($dadosFiltro['data'])) {

            if (isset($dadosFiltro['dataFim']) and ! empty($dadosFiltro['dataFim'])) {
                $sqlFiltro .= " AND B.data_consulta >='{$dadosFiltro['data']}' AND B.data_consulta <= '{$dadosFiltro['dataFim']}'";
            } else {
                $sqlFiltro .= " AND B.data_consulta ='{$dadosFiltro['data']}' ";
            }
        }


        $dadosInsertSql = "A.idRecebimento, A.pago, A.recebimento_data,
                                    A.recebimento_competencia,
                                    A.recebimento_valor,
                                    A.orcamentos_id,A.consulta_id,
                                    A.ano_codigo,A.rec_codigo, 
                                  /*  C.nome_proc,
                                     E.nome_proc,
                                    C.procedimentos_id,
                                    E.procedimentos_id,*/
                                   
                                    D.codigo,
                                    AES_DECRYPT(F.nome_cript,'$this->ENC_CODE') AS  nomeDoutor,
                                    B.data_consulta,
                                          D.data_cad,
                                    if(A.orcamentos_id IS NOT NULL,
                                          D.data_cad,
                                          B.data_consulta
                                    ) AS dataProc,
                                      if(A.orcamentos_id IS NOT NULL,
                                        E.nome_proc,
                                        C.nome_proc
                                  ) AS nome_procedimento,
                                      if(A.orcamentos_id IS NOT NULL,
                                        E.procedimentos_id,
                                        C.procedimentos_id
                                  ) AS idProcedimento
                    ";

        $from = " ( SELECT $dadosInsertSql

                                    FROM financeiro_recebimento AS A
                                      LEFT JOIN consultas AS B
                                      ON (A.identificador = B.identificador AND A.consulta_id = B.id)
                                      LEFT JOIN consultas_procedimentos AS C
                                      ON (C.consultas_id = B.id AND  C.identificador = A.identificador) 

                                      LEFT JOIN orcamentos AS D
                                      ON (A.identificador = D.identificador AND D.id = A.orcamentos_id)
                                      LEFT JOIN orcamentos_proc_itens AS E
                                      ON (E.orcamentos_id = A.orcamentos_id AND  E.identificador = A.identificador) 


                                                  LEFT JOIN doutores AS F
                                                  ON (F.identificador = A.identificador 
                                                  AND (F.id = B.doutores_id OR F.id = D.doutores_id))

                                     WHERE A.identificador = '$idDominio'  AND A.status= 1 $sqlFiltro
                                         GROUP BY C.id, E.id
                                    $ordenacao   ) as qr";

        if (empty($dadosPaginacao)) {

            $qr = $this->connClinicas()->select("SELECT * FROM $from");
        } else {
            $qr = $this->paginacao($dadosInsertSql, $from, 'clinicas', $dadosPaginacao['page'], $dadosPaginacao['perPage']);
        }


        return $qr;
    }

    public function store($idDominio, $dadosInsert) {
        $dadosInsertCript = ['executante_nome_cript'];
        $dadosInsert['identificador'] = $idDominio;
        $dadosInsert['data_cad'] = date('Y-m-d H:i:s');
        $qr = $this->insertDB('consultas_procedimentos', $dadosInsert, $dadosInsertCript, 'clinicas');
        return $qr;
    }

    public function update($idDominio, $id, $dadosInsert) {
        $dadosCript = ['executante_nome_cript'];
//        $dadosCript ['user_cad'] = auth('clinicas')->user()->id; 
        return $qr = $this->updateDB('consultas_procedimentos', $dadosInsert, "identificador = $idDominio AND id = $id LIMIT 1", $dadosCript, 'clinicas');
    }

    public function getByConsultaId($idsDominio, $idConsulta, Array $idsConsultaProcedimentos = null) {
        if (is_array($idsDominio)) {
            $sql = 'A.identificador IN(' . implode(',', $idsDominio) . ")";
        } else {
            $sql = "A.identificador = $idsDominio";
        }

        if ($idsConsultaProcedimentos != null and count($idsConsultaProcedimentos) > 0) {
            $sql .= " AND A.id IN(" . implode(',', $idsConsultaProcedimentos) . ")";
        }


        
      
        $qr = $this->connClinicas()->select("SELECT  A.*, C.codigo AS codCarteiraVirtual, C.nome_pacote, AES_DECRYPT(E.nome_cript, '$this->ENC_CODE') as nomeParceiro,D.exibir_app_docbizz
            FROM consultas_procedimentos AS A 
          LEFT JOIN carteira_virtual_itens AS B
            ON B.id = A.id_carteira_item
            LEFT JOIN carteira_virtual AS C
            ON C.id = B.carteira_virtual_id
            INNER JOIN procedimentos AS D
            ON A.procedimentos_id = D.idProcedimento
            LEFT JOIN doutores AS E
            ON E.id = D.doutor_parceiro_id
            WHERE $sql AND A.consultas_id = $idConsulta AND A.status = 1");
        return $qr;
    }

    public function excluir($idDominio, $consultaProcedimentoId, $consultaId = null) {

        $sqlConsulta = '';
        if (!empty($consultaId)) {
            $sqlConsulta = "AND consultas_id = $consultaId";
        }


        $qrVerificaItemCarteira = $this->connClinicas()->select("SELECT * FROM consultas_procedimentos 
                                                WHERE identificador = $idDominio AND id = $consultaProcedimentoId $sqlConsulta  AND id_carteira_item IS NOT NULL  LIMIT 1");
        if (count($qrVerificaItemCarteira) > 0) {
            $row = $qrVerificaItemCarteira[0];
            $this->updateDB('carteira_virtual_itens', array('consulta_id' => null), "identificador = $idDominio AND id = $row->id_carteira_item LIMIT 1");
        }
        $qr = $this->connClinicas()->select("DELETE FROM consultas_procedimentos WHERE identificador = $idDominio AND id = $consultaProcedimentoId  $sqlConsulta LIMIT 1");
    }

}
