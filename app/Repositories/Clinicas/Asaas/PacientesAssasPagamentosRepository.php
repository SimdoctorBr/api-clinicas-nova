<?php

namespace App\Repositories\Clinicas\Asaas;

use Illuminate\Support\Facades\DB;
use App\Repositories\BaseRepository;

class PacientesAssasPagamentosRepository extends BaseRepository {

    public function store($idDominio, $dados) {
        $qr = $this->insertDB('paciente_assas_pagamentos', $dados, null, 'clinicas');
        return $qr;
    }

    public function update($idDominio, $id, $dados) {
        
        $qr = $this->updateDB('paciente_assas_pagamentos', $dados, " identificador = $idDominio AND id = $id", null, 'clinicas');
        return $qr;
    }

}
