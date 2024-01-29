<?php

namespace App\Repositories\Clinicas;

use Illuminate\Support\Facades\DB;
use App\Repositories\BaseRepository;

class AprovacaoAlteracoesRepository extends BaseRepository {

    public function store($idDominio, $dados) {
        $dados['identificador'] = $idDominio;
        return $qr = $this->insertDB('aprovacoes_alteracao', $dados,['json_alteracao','administrador_nome_cad','aprov_administrador_nome']
                ,'clinicas');
    }
}
