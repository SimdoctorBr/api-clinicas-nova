<?php

namespace App\Repositories\Gerenciamento;

use Illuminate\Support\Facades\DB;
use App\Repositories\BaseRepository;

class DominioRepository extends BaseRepository {

    public function getAllByUser($userId, $dominioId = null) {

        $filter = '';
        if (!empty($dominioId)) {
            $filter = " AND B.id = $dominioId";
        }
        $qr = $this->connGerenciamento()->select("SELECT B.id, B.dominio,B.habilita_tuotempo  FROM tuotempo_users_dominios AS A
                                                    INNER JOIN dominios AS B
                                                    ON A.dominios_id = B.id
                                                    WHERE A.users_tuotempo_id = $userId $filter");

        return $qr;
    }

    public function getById($dominioId) {

        $qr = $this->connGerenciamento()->select("SELECT * FROM dominios WHERE id = $dominioId");

        if (count($qr) > 0) {
            return $qr[0];
        } else {
            return false;
        }
    }

    public function getDominiosDocBiz() {
        $qr = $this->connGerenciamento()->select("SELECT * FROM dominios_app_docbiz");
        return $qr;
    }

    public function getDominiosByUserApiInterno($userApiInternoId) {
        $qr = $this->connGerenciamento()->select("SELECT A.*,B.dominio_id FROM api_simdoctor_users AS A
                                                INNER JOIN  api_simdoctor_users_dominios AS B
                                                ON A.id = B.api_simdoctor_users_id
                                                WHERE A.id = $userApiInternoId");
        return $qr;
    }

}
