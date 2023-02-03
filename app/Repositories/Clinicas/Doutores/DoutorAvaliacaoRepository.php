<?php

namespace App\Repositories\Clinicas\Doutores;

use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;

class DoutorAvaliacaoRepository extends BaseRepository {

    private function calculaMediaPontuacao($dominioId, $doutorId) {
        $qr = $this->connClinicas()->select("SELECT AVG(pontuacao) as mediaPontuacao FROM  doutores_avaliacoes WHERE identificador = $dominioId AND doutores_id = $doutorId");
        $this->updateDB('doutores', ['pontuacao' => $qr[0]->mediaPontuacao], "  identificador = $dominioId AND id = $doutorId LIMIT 1", null, 'clinicas');
    }

    public function store($dominioId, $doutorId, $pacienteId, $pontuacao) {

        if (is_array($dominioId)) {
            $sql = 'identificador IN(' . implode(',', $dominioId) . ")";
        } else {
            $sql = "identificador = $dominioId";
        }

        $qrVerificaAvalicao = $this->connClinicas()->select("SELECT id FROM doutores_avaliacoes WHERE $sql AND doutores_id = $doutorId
                AND pacientes_id = $pacienteId
                ");
        if (count($qrVerificaAvalicao) > 0) {
            $row = $qrVerificaAvalicao[0];
            $idAvaliacao = $row->id;
            $campos['pontuacao'] = $pontuacao;
            $this->updateDB('doutores_avaliacoes', $campos, " identificador = $dominioId AND id = $idAvaliacao LIMIT 1", null, 'clinicas');
        } else {

            $campos['identificador'] = $dominioId;
            $campos['doutores_id'] = $doutorId;
            $campos['pacientes_id'] = $pacienteId;
            $campos['pontuacao'] = $pontuacao;
            $idAvaliacao = $this->insertDB('doutores_avaliacoes', $campos, null, 'clinicas');
        }
        $this->calculaMediaPontuacao($dominioId, $doutorId);

        return $idAvaliacao;
    }

}
