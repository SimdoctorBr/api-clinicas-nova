<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Repositories\Clinicas;

use App\Repositories\BaseRepository;

/**
 * Description of ConvenioRepository
 *
 * @author ander
 */
class PerfisUsuariosRepository extends BaseRepository {

    public function getAll($idDominio) {

        $qr = $this->connClinicas()->select("SELECT * FROM perfis WHERE identificador = $idDominio ");
        return $qr;
    }

}
