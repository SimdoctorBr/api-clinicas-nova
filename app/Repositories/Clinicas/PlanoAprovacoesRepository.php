<?php

namespace App\Repositories\Clinicas;

use Illuminate\Support\Facades\DB;
use App\Repositories\BaseRepository;
use App\Helpers\Functions;

class PlanoAprovacoesRepository extends BaseRepository {

    public function insert($idDominio, $campos) {
        $camposEnc = ['nome', 'sobrenome', 'sexo', 'cpf'];
        return $qr = $this->insertDB('plano_aprovacoes', $campos, $camposEnc, 'clinicas');
    }

    public function insertArquivoDocExigido($idDominio, $campos) {
        return $qr = $this->insertDB('plano_aprovacoes_arquivos', $campos, null, 'clinicas');
    }

    public function getAprovacoesDependentesByPacienteId($idDominio, $idPaciente, $tipo = null, $filtro = null) {

        $sqlFiltro = '';
        if (isset($filtro['status'])) {
            $sqlFiltro .= (is_array($filtro['status'])) ? " AND status IN(" . implode(',', $filtro['status']) . ")" : " AND status =" . $filtro['status'];
        }
        if (!empty($tipo)) {
            $sqlFiltro .= " AND tipo =$tipo";
        }




        $qr = $this->connClinicas()->select("SELECT A.*,
            AES_DECRYPT(B.nome_cript, '$this->ENC_CODE') as nomePaciente,
            AES_DECRYPT(B.sobrenome_cript, '$this->ENC_CODE') as sobrenomePaciente,
           AES_DECRYPT(A.nome, '$this->ENC_CODE') as nomeDependente,
            AES_DECRYPT(A.sobrenome, '$this->ENC_CODE') as sobrenomeDependente,
            AES_DECRYPT(A.cpf, '$this->ENC_CODE') as cpfDependente,
            AES_DECRYPT(A.sexo, '$this->ENC_CODE') as sexoDependente,
           C.data_nascimento
             FROM plano_aprovacoes AS A
            LEFT JOIN pacientes AS B
            ON B.id = A.pacientes_id
            LEFT JOIN pacientes AS C
            ON C.id = A.pacientes_dep_id  
            WHERE A.identificador = $idDominio AND A.pacientes_id = $idPaciente $sqlFiltro ORDER BY data_cad DESC 
                ");

        return $qr;
    }

    public function getAprovacoesCodigoNaoConfirmado($idDominio, $pacienteId, $tipo = null) {

        $sql = '';
        if (!empty($tipo)) {
            $sql = "AND tipo  = $tipo";
        }
        $qr = $this->connClinicas()->select("SELECT * FROM  plano_aprovacoes WHERE identificador =$idDominio AND pacientes_id = $pacienteId AND STATUS = 4 $sql;");

        if (count($qr) > 0) {
            return $qr;
        } else {
            return false;
        }
    }

    /**
     * 
     * @param type $identificador
     * @param type $idAprovacao
     * @param type $status  2 - Aprovado, 3 -Reprovado
     */
    public function alterarAprovacao($idDominio, $idAprovacao, $status, $idPacienteDep = null) {
        $campos['status'] = $status;
        $campos['data_ultima_ateracao'] = date('Y-m-d H:i:s');
        $campos['administrador_id_alt'] = ( auth('clinicas')->check()) ? auth('clinicas')->user()->id : null;
        $campos['administrador_nome_alt']= ( auth('clinicas')->check()) ? auth('clinicas')->user()->nome : null;
        if (!empty($idPacienteDep)) {
            $campos['pacientes_dep_id'] = $idPacienteDep;
        }
        return $qr = $this->updateDB('plano_aprovacoes', $campos, " identificador = $idDominio AND id = $idAprovacao LIMIT 1", ['administrador_nome_alt'], 'clinicas');
    }
}
