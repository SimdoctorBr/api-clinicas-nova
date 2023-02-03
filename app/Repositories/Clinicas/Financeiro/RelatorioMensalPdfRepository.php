<?php

namespace App\Repositories\Clinicas\Financeiro;

use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;

class RelatorioMensalPdfRepository extends BaseRepository {

    public function getAll($idDominio, $dadosFiltro = null) {
        $sqFiltro = '';
        if (isset($dadosFiltro['mes']) and ! empty($dadosFiltro['mes'])) {
            $sqFiltro = " AND mes = " . sprintf("%02s", $dadosFiltro['mes']);
        }
        if (isset($dadosFiltro['ano']) and ! empty($dadosFiltro['ano'])) {
            $sqFiltro .= " AND ano = " . $dadosFiltro['ano'];
        }
        if (isset($dadosFiltro['doutorId']) and ! empty($dadosFiltro['doutorId'])) {
            $sqFiltro .= " AND A.doutores_id = " . $dadosFiltro['doutorId'];
        }


        $qrVerifica = $this->connClinicas()->select("SELECT A.*, AES_DECRYPT(B.nome_cript, '$this->ENC_CODE') as nomeDoutor, C.nome as nomeUserCad
                                                FROM financeiro_relatorio_pdf  as A
                                                LEFT JOIN doutores as B
                                                ON A.doutores_id = B.id
                                                LEFT JOIN administradores as C
                                                ON C.id = A.administrador_id_cad

                                                WHERE A.identificador = $idDominio  $sqFiltro ORDER BY codigo DESC");

        return $qrVerifica;
    }

}
