<?php

namespace App\Repositories\Clinicas;

use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;

class ConselhosProfissionaisRepository extends BaseRepository {

    public function getAll() {

        $qr = $this->connClinicas()->select("SELECT * FROM tiss_conselhos_profisionais");
        return $qr;
    }

    public function getById($id) {

        $qr = $this->connClinicas()->select("SELECT * FROM tiss_conselhos_profisionais WHERE id = $id");
        if (count($qr) > 0) {
            return $qr[0];
        } else {
            return false;
        }
    }
}
