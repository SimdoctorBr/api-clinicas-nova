<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Repositories\Clinicas;

use App\Repositories\BaseRepository;

/**
 * Description of ProcedimentosRepository
 *
 * @author ander
 */
class ProcedimentosRepository extends BaseRepository {

    public function getProcedimentoPorDoutor($idDominio, $idDoutor = null, $dadosFiltro = null, $page = null, $perPage = null, $idProcAssoc = null, $somenteProcedimentosGroup = false) {

        $groupBy = null;

        if (is_array($idDominio)) {
            $sql = 'A.identificador IN(' . implode(',', $idDominio) . ")";
        } else {
            $sql = "A.identificador = $idDominio";
        }


        if (!empty($somenteProcedimentosGroup)) {

            $groupBy .= "GROUP BY A.procedimentos_id";
        }

        if (!empty($idDoutor)) {
            $sql .= " AND A.doutores_id = $idDoutor";
        }
        if (isset($dadosFiltro['convenioId']) and!empty($dadosFiltro['convenioId'])) {
            $sql .= " AND A.proc_convenios_id={$dadosFiltro['convenioId']}";
        }

        if (isset($dadosFiltro['procedimentoId']) and!empty($dadosFiltro['procedimentoId'])) {
            $sql .= " AND A.procedimentos_id={$dadosFiltro['procedimentoId']}";
        }
        if (isset($dadosFiltro['search']) and!empty($dadosFiltro['search'])) {
            $sql .= " AND C.procedimento_nome like '%{$dadosFiltro['search']}'";
        }
        if (isset($dadosFiltro['exibeDocBizz']) and!empty($dadosFiltro['exibeDocBizz'])) {
            $sql .= " AND C.exibir_app_docbizz = 1";
        }
        if (!empty($idProcAssoc)) {
            $sql .= " AND A.id= '$idProcAssoc'";
        }
        if ($somenteProcedimentosGroup === true) {
            $groupBy = " GROUP BY A.procedimentos_id";
        }




        $camposSQL = " A.*, AES_DECRYPT(B.nome_cript, '$this->ENC_CODE') AS nomeDoutor, C.procedimento_nome AS nomeProcedimento, C.procedimento_descricao, C.idProcedimento, C.retorno,
                                                D.proc_cat_nome, E.valor AS valor_proc,  (CONVERT(CAST(CONVERT( F.nome USING latin1) AS BINARY) USING UTF8)) AS nomeConvenioProc,
                                                IF(A.tipo_repasse = 1, (E.valor - (A.valor_percentual/100)), IF(A.tipo_repasse = 2, A.valor_real, '')) AS valorRepasse,C.procedimento_categoria_id,
                                              C.duracao,C.possui_parceiro, AES_DECRYPT(G.nome_cript, '$this->ENC_CODE') AS nomeDoutorParceiro, F.imposto as impostoConv,C.doutor_parceiro_id,E.cod_procedimento as codigoProc,
                                                  C.exibir_app_docbizz";

        $from = "FROM procedimentos_doutores_assoc AS A
                                            INNER JOIN doutores AS B 
                                            ON A.doutores_id = B.id
                                            INNER JOIN procedimentos AS C 
                                            ON C.idProcedimento = A.procedimentos_id
                                            LEFT JOIN procedimentos_categorias AS D 
                                            ON D.idProcedimentoCat = C.procedimento_categoria_id
                                            INNER JOIN procedimentos_convenios_assoc AS E 
                                            ON (E.identificador = A.identificador AND E.procedimentos_id = A.procedimentos_id AND E.convenios_id = A.proc_convenios_id)
                                            LEFT JOIN convenios AS F 
                                            ON ((F.identificador = A.identificador AND F.id = A.proc_convenios_id) OR (F.identificador IS NULL AND F.id = A.proc_convenios_id))
                                               LEFT JOIN doutores AS G
                                            ON G.id = C.doutor_parceiro_id
                                            WHERE $sql 
                                            AND A.status = 1 AND C.`status` = 1 $groupBy ORDER BY C.procedimento_nome";
//dd("SELECT " . $camposSQL . " " . $from );
        if (!empty($page) and!empty($perPage)) {


            $qr = $this->paginacao($camposSQL, $from, 'clinicas', $page, $perPage, false);
        } else {

            $qr = $this->connClinicas()->select("SELECT $camposSQL $from");
        }



        return $qr;
    }

    /**
     * Retorna todos os procedimentos que possuem vinculo com doutores e convênios
     * 
     * @param type $param
     */
    public function getAllProcedimentosVinculados($idDominio, $page = null, $perPage = null) {


        if (is_array($idDominio)) {
            $sql = 'A.identificador IN(' . implode(',', $idDominio) . ")";
        } else {
            $sql = "A.identificador = $idDominio";
        }


        $camposSQL = " A.*, AES_DECRYPT(B.nome_cript, '$this->ENC_CODE') AS nomeDoutor, C.procedimento_nome AS nomeProcedimento, C.procedimento_descricao, C.idProcedimento, C.retorno,
                                                 (CONVERT(CAST(CONVERT( D.proc_cat_nome USING latin1) AS BINARY) USING UTF8)) as proc_cat_nome, E.valor AS valor_proc, F.nome AS nomeConvenioProc,
                                                IF(A.tipo_repasse = 1, (E.valor - (A.valor_percentual/100)), IF(A.tipo_repasse = 2, A.valor_real, '')) AS valorRepasse,C.procedimento_categoria_id,
                                                C.duracao";

        $from = "FROM procedimentos_doutores_assoc AS A
                                            INNER JOIN doutores AS B 
                                            ON A.doutores_id = B.id
                                            INNER JOIN procedimentos AS C 
                                            ON C.idProcedimento = A.procedimentos_id
                                            LEFT JOIN procedimentos_categorias AS D 
                                            ON D.idProcedimentoCat = C.procedimento_categoria_id
                                            INNER JOIN procedimentos_convenios_assoc AS E 
                                            ON (E.identificador = A.identificador AND E.procedimentos_id = A.procedimentos_id AND E.convenios_id = A.proc_convenios_id)
                                            LEFT JOIN convenios AS F 
                                            ON ((F.identificador = A.identificador AND F.id = A.proc_convenios_id) OR (F.identificador IS NULL AND F.id = A.proc_convenios_id))
                                            WHERE $sql 
                                            AND A.status = 1 AND C.`status` = 1 	
                                            GROUP BY A.procedimentos_id
                                            ORDER BY C.procedimento_nome";

        if (!empty($page) and!empty($perPage)) {


            $qrPagTotalReg = $this->connClinicas()->select("SELECT COUNT(*)  as total FROM ( SELECT " . $camposSQL . " " . $from . " ) as ql");

            $totalRegistro = $qrPagTotalReg[0]->total;
            $pageSql = $page - 1;
            $inicioSql = $pageSql * $perPage;
            $totalPages = (int) ceil($totalRegistro / $perPage);
            $qrPaginada = $this->connClinicas()->select("SELECT " . $camposSQL . " " . $from . " LIMIT $inicioSql,$perPage");

            $result = array(
                'TOTAL_RESULTS' => $totalRegistro,
                'TOTAL_PAGES' => $totalPages,
                'PAGE' => $page,
                'PER_PAGE' => $perPage,
                'RESULTS' => $qrPaginada
            );

            return $result;
        } else {

            $qr = $this->connClinicas()->select("SELECT $camposSQL $from");
        }
        return $qr;
    }

    public function getByActivity($idDominio, $idDoutor, $procedimentoId, $id_convenio) {

        $qr = $this->getProcedimentoPorDoutor($idDominio, $idDoutor, $id_convenio, $procedimentoId);
        return $qr;
    }

    public function getProcedimentoConvenios($idDominio, $procedimentoIdAssoc, $idConvenio = null) {

        if (is_array($idDominio)) {
            $sql = 'A.identificador IN(' . implode(',', $idDominio) . ")";
        } else {
            $sql = "A.identificador = $idDominio";
        }

        if (!empty($idConvenio)) {
            $sql .= "AND A.convenios_id=$idConvenio";
        }


        $qr = $this->connClinicas()->select("SELECT A.id,A.identificador, A.valor, B.procedimento_nome,A.convenios_id, (CONVERT(CAST(CONVERT( C.nome USING latin1) AS BINARY) USING UTF8)) AS nomeConvenio
                                        FROM procedimentos_convenios_assoc AS A
                                        INNER JOIN procedimentos AS B
                                        ON A.procedimentos_id =B.idProcedimento
                                        LEFT JOIN convenios AS C
                                        ON C.id = A.convenios_id
                                        WHERE $sql AND A.id = $procedimentoIdAssoc
                                        AND A.`status` = 1 ");
        return $qr;
    }

    public function getByConsultaId($idDominio, $idConsulta) {

        if (is_array($idDominio)) {
            $sql = 'A.identificador IN(' . implode(',', $idDominio) . ")";
        } else {
            $sql = "A.identificador = $idDominio";
        }


        $qr = $this->connClinicas()->select("SELECT A.*, IF(A.procedimentos_cat_nome IS NULL OR A.procedimentos_cat_nome = 0,
				 (CONVERT(CAST(CONVERT(C.proc_cat_nome USING latin1) AS BINARY) USING UTF8)),
				 A.procedimentos_cat_nome) AS nomeCategoria,
                                B.procedimento_categoria_id,
                                B.possui_parceiro,
                                B.doutor_parceiro_id,
                                 AES_DECRYPT(D.nome_cript, '$this->ENC_CODE') as nomeParceiro, F.codigo AS codCarteiraVirtual, F.nome_pacote,
                                      (CONVERT(CAST(CONVERT(A.nome_convenio USING latin1) AS BINARY) USING UTF8)) as nome_convenio,
                              AES_DECRYPT(A.executante_nome_cript, '$this->ENC_CODE')  as executante_nome,  AES_DECRYPT(G.nome_cript, '$this->ENC_CODE') AS nomeDoutorParceiro,   B.doutor_parceiro_id

				 FROM consultas_procedimentos as A 
                                    INNER JOIN procedimentos AS B
                                    ON A.procedimentos_id = B.idProcedimento
                                    LEFT JOIN procedimentos_categorias AS C
                                    ON C.idProcedimentoCat = B.procedimento_categoria_id
                                     LEFT JOIN doutores AS D
                                    ON D.id = B.doutor_parceiro_id
                                       LEFT JOIN carteira_virtual_itens AS E
                                    ON E.id = A.id_carteira_item
                                    LEFT JOIN carteira_virtual AS F
                                    ON F.id = E.carteira_virtual_id
                                        LEFT JOIN doutores AS G
                                            ON G.id = B.doutor_parceiro_id
                                    WHERE $sql AND A.consultas_id = '$idConsulta'
                                        AND A.status = 1
                                        ORDER BY A.id_proc_pacote_item,nomeCategoria, A.nome_proc ASC");
        return $qr;
    }

    public function getById($idDominio, $idProcedimento) {

        $qr = $this->connClinicas()->select("SELECT A.*,B.*,  AES_DECRYPT(C.nome_cript, '$this->ENC_CODE') AS nomeDoutorParceiro,D.proc_cat_nome, E.name as dicomName,E.code as dicomCode, F.codigo as codigoTabelaTuss, F.nome as nomeTabelaTuss
            FROM procedimentos as A 
                                         Left JOIN procedimento_taxas as B
                                         ON A.procedimento_taxa_id = B.idProcedimentoTaxa
                                          LEFT JOIN doutores AS C
                                        ON C.id = A.doutor_parceiro_id
                                           LEFT JOIN procedimentos_categorias AS D
                                        ON D.idProcedimentoCat = A.procedimento_categoria_id
                                            LEFT JOIN dicom_modality AS E
                                        ON E.id = A.dicom_modality_id   
                                            LEFT JOIN tiss_tabela_tuss AS F
                                        ON F.id = A.tiss_tabela_tuss_id   
                                        WHERE A.identificador = $idDominio AND  idProcedimento = '$idProcedimento' AND A.status = 1");
        if (count($qr) > 0) {
            return $qr[0];
        } else {
            return false;
        }
    }

    /**
     * 
     * @param type $idDominio
     * @param type $procedimentoId
     * @param type $convenioId
     * @param type $doutorId
     * @param type $tipoRetorno 1- Dados do procedimento, 2- true ou false
     * @return boolean
     */
    public function getByVinculoConvenioDoutor($idDominio, $procedimentoId, $convenioId, $doutorId, $tipoRetorno = 1) {

        if ($tipoRetorno == 1) {
            $campos = " A.idProcedimento, A.procedimento_nome,A.doutor_parceiro_id, A.possui_parceiro, A.duracao,A.procedimento_categoria_id,A.retorno,A.utiliza_dicom,A.dicom_modality_id,
                                                        AES_DECRYPT(D.nome_cript, '$this->ENC_CODE') as nomeDoutorParceiro,
                                                        B.*, C.*, E.proc_cat_nome,F.code as dicomCode";
        } else if ($tipoRetorno == 2) {
            $campos = "COUNT(*) as total";
        }
        $qr = $this->connClinicas()->select("SELECT $campos
                                                        FROM procedimentos AS A
                                            LEFT JOIN procedimentos_convenios_assoc as B
                                            ON A.idProcedimento = B.procedimentos_id
                                            LEFT JOIN procedimentos_doutores_assoc AS C
                                            ON (C.procedimentos_id = A.idProcedimento AND B.convenios_id = C.proc_convenios_id)
                                            LEFT JOIN doutores AS D
                                            ON A.doutor_parceiro_id = D.id
                                            LEFT JOIN procedimentos_categorias AS E
                                            ON A.procedimento_categoria_id = E.idProcedimentoCat
                                            LEFT JOIN dicom_modality AS F
                                            ON A.dicom_modality_id = F.id
                                            WHERE A.identificador = $idDominio AND A.idProcedimento = $procedimentoId AND C.proc_convenios_id = $convenioId AND C.doutores_id = $doutorId");

        if (count($qr) > 0) {
            return $qr[0];
        } else {
            return false;
        }
    }

    public function getDadosRepasse($idDominio, $doutorId, $idProcedimento, $convenioId) {
        $qr = $this->connClinicas()->select("SELECT *
                            FROM procedimentos_doutores_assoc
                            WHERE identificador = $idDominio AND doutores_id = $doutorId 
                                AND status = 1
                              AND procedimentos_id = $idProcedimento AND (proc_convenios_id IS NULL OR proc_convenios_id = '$convenioId');");

        if (count($qr) > 0) {
            return $qr[0];
        } else {
            return false;
        }
    }

}
