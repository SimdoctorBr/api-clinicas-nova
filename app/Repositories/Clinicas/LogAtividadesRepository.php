<?php

namespace App\Repositories\Clinicas;

use Illuminate\Support\Facades\DB;
use App\Repositories\BaseRepository;

class LogAtividadesRepository extends BaseRepository {

    public function store($idDominio, $dados) {
        $this->insertDB('log_administradores', $dados, null, 'clinicas');
    }

}
