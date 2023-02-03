<?php

namespace App\Repositories\Clinicas;

use Illuminate\Support\Facades\DB;
use App\Repositories\BaseRepository;

class CarteiraVirtualRepository extends BaseRepository {

    public function vinculaCarteiraItemConsulta($idDominio, $idItem, $consultaId) {
        $campos['consulta_id'] = $consultaId;
        $this->updateDB('carteira_virtual_itens', $campos, " identificador = $identificador AND id = $idItem LIMIT 1", null, 'clinicas');
    }

}
