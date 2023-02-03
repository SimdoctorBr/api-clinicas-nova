<?php

namespace App\Repositories\Clinicas;

use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;

class NotificacoesSistemaRepository extends BaseRepository {


    public function insert($idDominio, Array $dadosInsert) {
        $dadosInsert['identificador'] = $idDominio;
        $dadosInsert['data_cad'] = date('Y-m-d H:i:s');
        $dadosInsert['administrador_id_cad'] = ( auth('clinicas')->check()) ? auth('clinicas')->user()->id : null;
        return $qr = $this->insertDB('notificacoes_sistema', $dadosInsert, null, 'clinicas');
    }

    public function update($idDominio, $idNotificacao, Array $dadosUpdate) {

        return $qr = $this->updateDB('notificacoes_sistema', $dadosUpdate, " identificador = $idDominio AND id = $idNotificacao LIMIT 1 ", null, 'clinicas');
    }

     public function deleteByConsultaId($idDominio, $consultaId) {
         $qr = $this->connClinicas()->select("DELETE FROM  notificacoes_sistema WHERE identificador = $idDominio AND consulta_id = $consultaId ");
    }
}
