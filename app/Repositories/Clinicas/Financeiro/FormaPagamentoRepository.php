<?php

namespace App\Repositories\Clinicas\Financeiro;

use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;

class FormaPagamentoRepository extends BaseRepository {

    /**
     * 
     * @param type $idDominio
     * @param type $tipo
     * @param type $idTipo
     * @param array $idsRecebimentos
     * @param array $dadosFiltro
     * @return type
     */
    public function getAll($idDominio, Array $dadosFiltro = null) {
        $sqlMostrar = "AND B.mostrar = 1";
        if (isset($dadosFiltro['todos']) and $dadosFiltro['todos'] == 1) {
            $sqlMostrar = "";
        }



        $qr = $this->connClinicas()->select("SELECT A.*,B.id AS idConfigTpPag, B.possui_taxa,B.percentual_taxa,B.mostrar
                                FROM financeiro_tipo_pagamento AS A
                                LEFT JOIN financeiro_tipo_pag_config AS B 
                                ON (A.idTipo_pagamento = B.financeiro_tipo_pagamento_id AND B.identificador = '$idDominio')
                                WHERE (A.identificador IS NULL OR A.identificador =  '$idDominio') $sqlMostrar");

        return $qr;
    }

    /**
     * 
     * @param type $idDominio
     * @param type $idFormPag  int ou array
     * @return type
     */
    public function getById($idDominio, $idFormPag) {

        if (is_array($idFormPag)) {
            $sql = " AND A.idTipo_pagamento IN(" . implode(',', $idFormPag) . ")";
        } else {
            $sql = " AND A.idTipo_pagamento =$idFormPag";
        }



        $qr = $this->connClinicas()->select("SELECT A.*,B.id AS idConfigTpPag, B.possui_taxa,B.percentual_taxa,B.mostrar
                                FROM financeiro_tipo_pagamento AS A
                                LEFT JOIN financeiro_tipo_pag_config AS B 
                                ON (A.idTipo_pagamento = B.financeiro_tipo_pagamento_id AND B.identificador = '$idDominio')
                                WHERE (A.identificador IS NULL OR A.identificador =  '$idDominio') $sql");

        return $qr;
    }

}
