<?php

namespace App\Repositories\Clinicas;

use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;

class IntegracaoGoogleRepository extends BaseRepository {

    public function getByDoutoresId($idDOminio, $doutoresId) {

        $qr = $this->connClinicas()->select("SELECT * FROM google_credenciais WHERE identificador = $idDOminio AND doutores_id = $doutoresId ORDER BY data_alter DESC");
        if (count($qr) > 0) {
            return $qr[0];
        } else {
            return false;
        }
    }

    public function insertEvento($idDominio, $campos) {
        $campos['identificador'] = $idDominio;
        $qr = $this->insertDB('google_email_eventos', $campos, null, 'clinicas');
        return $qr;
    }

    public function excluirEvento($idDominio, $idGoogleEmalEvento) {       
        $qr = $this->connClinicas()->select("DELETE FROM google_email_eventos WHERE id = '$idGoogleEmalEvento' LIMIT 1");
        return $qr;
    }

    /**
     * Pega o evento na tabela google_email_eventos de acordo com o e-mail e tipo de evento 
     * @param type $identificador 1 - Consulta, 2 - Compromisso
     * @param type $tipo 
     * @param type $id
     * @param type $email
     * @return type
     */
    public function getEventoPorTipo($idDominio, $tipo, $id, $email, $calendario_id = null) {
        $sql = '';
        if (!empty($calendario_id)) {
            $sql = " AND calendario_google_id= '$calendario_id'";
        }
//echo "SELECT * FROM google_email_eventos WHERE tipo = '$tipo' AND id_tipo = '$id' AND email = '$email' $sql<br>";
        $qr = $this->connClinicas()->select("SELECT * FROM google_email_eventos WHERE tipo = '$tipo' AND id_tipo = '$id' AND email = '$email' $sql");
        if (count($qr) > 0) {
            return $qr[0];
        } else {
            return false;
        }
    }

}
