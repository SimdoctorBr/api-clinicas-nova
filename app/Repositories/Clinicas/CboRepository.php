<?php

namespace App\Repositories\Clinicas;

use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;

class CboRepository extends BaseRepository {

    public function getAll($dadosFiltro = null) {
        $sql = '';
        if (isset($dadosFiltro['search']) and $dadosFiltro['search'] != null) {
            $sql = " WHERE codigo like '" . $dadosFiltro['search'] . "%' OR nome like '%" . $dadosFiltro['search'] . "%'";
        }
        $qr = $this->connClinicas()->select("SELECT * FROM tiss_cbo_s $sql");
        return $qr;
    }

    public function getById($id) {

        $qr = $this->connClinicas()->select("SELECT * FROM tiss_cbo_s WHERE id = $id");
        if (count($qr) > 0) {
            return $qr[0];
        } else {
            return false;
        }
    }
    public function getByCodigo($cod) {

        $qr = $this->connClinicas()->select("SELECT * FROM tiss_cbo_s WHERE codigo = $cod");
        if (count($qr) > 0) {
            return $qr[0];
        } else {
            return false;
        }
    }
}
