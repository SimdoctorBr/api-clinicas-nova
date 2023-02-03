<?php

namespace App\Repositories\Clinicas\Financeiro;

use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;
use App\Repositories\Clinicas\DefinicaoMarcacaoGlobalRepository;

class RecebimentoRepository extends BaseRepository {

    public function getUltimoCod($idDominio) {

        $qr = $this->connClinicas()->select("SELECT MAX(A.rec_codigo) as total FROM financeiro_recebimento AS A WHERE identificador = $idDominio");
        $row = $qr[0];

        $qrDefGlobal = new DefinicaoMarcacaoGlobalRepository;
        $rowDefGlobal = $qrDefGlobal->getDadosDefinicao($idDominio);

        if (empty($row->total)) {
            return $rowDefGlobal->nr_inicio_recebimento + 1;
        } else {
            return $row->total + 1;
        }
    }

    public function store($idDominio, $dadosInsert) {

        $dadosInsert['ano_codigo'] = date('Y');
        $dadosInsert['rec_codigo'] = $this->getUltimoCod($idDominio);
        return $this->insertDB('financeiro_recebimento', $dadosInsert, null, 'clinicas');
    }

    /**
     * 
     * @param type $idDominio
     * @param type $tipo
     * @param type $idTipo
     * @param array $idsRecebimentos
     * @param array $dadosFiltro
     * @return type
     */
    public function getAllEfetuados($idDominio, $tipo, $idTipo, Array $idsRecebimentos = null, Array $dadosFiltro = null) {


        $sqlTipo = '';
        $join = '';
        switch ($tipo) {
            case 'consulta':
                $sqlTipo = " AND A.consulta_id = '$idTipo' ";
                break;
            case 'orcamento':
                $sqlTipo = " AND A.orcamentos_id = '$idTipo' ";
                $join = " LEFT JOIN orcamentos as I
                          ON I.id = A.orcamentos_id";
                break;
            case 'carteira_virtual':
                $sqlTipo = " AND A.consulta_id = 'carteira_virtual_id' ";
                $join = " LEFT JOIN orcamentos as I
                          ON I.id = A.orcamentos_id";
                break;
        }



        if (!empty($idsRecebimentos) and is_array($idsRecebimentos)) {
            $sqlTipo .= " AND A.idRecebimento IN(" . implode(',', $idsRecebimentos) . ")";
        }


        $qr = $this->connClinicas()->select("
        SELECT A.*,B.*, C.*, D.*, AES_DECRYPT(F.nome_cript, '$this->ENC_CODE') as nome, AES_DECRYPT(F.sobrenome_cript, '$this->ENC_CODE') as sobrenome,
         AES_DECRYPT(F.cpf_cript, '$this->ENC_CODE') as cpfPaciente,
             F.exibe_sobrenome_impressao,
H.fornecedor_nome, G.periodo as periodoNome,
                (SELECT SUM(recebimento_valor) AS total FROM financeiro_recebimento 
                                WHERE identificador = A.identificador 
                               AND (vinculo_periodo_recebimento_id = A.idRecebimento OR idRecebimento = A.idRecebimento   )
                       ) AS totalParcelado,
                       C.categoria_nome
                                FROM financeiro_recebimento as A
                                    LEFT JOIN financeiro_tipo_pagamento as B
                                    ON A.tipo_pag_id = B.idTipo_pagamento
                                    LEFT JOIN financeiro_categoria as C
                                    ON A.categoria_id = C.idCategoria
                                     LEFT JOIN financeiro_tipo_categoria as D
                                    ON D.idTipo_categoria = C.tipo_categoria_id
                                    LEFT JOIN consultas as E
                                    ON E.id = A.consulta_id 
                                    LEFT JOIN pacientes as F 
                                    ON F.id = E.pacientes_id
                                   LEFT JOIN financeiro_periodo_repeticao as G
                                    ON G.id = A.periodo_repeticao_id
                                   LEFT JOIN financeiro_fornecedor as H
                                   ON H.idFornecedor = A.pagar_com_adm_banco                                   
                                   $join
                                   WHERE A.identificador  = '$idDominio' $sqlTipo AND A.status = 1  
                                          AND vinculo_periodo_recebimento_id IS NULL
                                        
                                ORDER BY vinculo_periodo_recebimento_id ASC");

        return $qr;
    }

}
