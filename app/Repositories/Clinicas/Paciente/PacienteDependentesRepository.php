<?php

namespace App\Repositories\Clinicas\Paciente;

use Illuminate\Support\Facades\DB;
use App\Repositories\BaseRepository;
use App\Helpers\Functions;

class PacienteDependentesRepository extends BaseRepository {

    public function getByPaciente($idDominio, $idPaciente) {
        $sql = "";
        if (is_array($idDominio)) {
            $sql = 'pd.identificador IN(' . implode(',', $idDominio) . ")";
        } else {
            $sql = "pd.identificador = $idDominio";
        }

        $descobreSeEDependente = "SELECT pd.id, pd.dependente_id, pd.filiacao,
               AES_DECRYPT(dependente.nome_cript, '$this->ENC_CODE') as nomeDependente,
                AES_DECRYPT(dependente.sobrenome_cript, '$this->ENC_CODE') as sobrenomeDependente,
                AES_DECRYPT(dependente.cpf_cript, '$this->ENC_CODE') as cpfDependente ,
                  dependente.data_nascimento,
                   dependente.sexo as sexoDependente,
                  pd.aprovado_pl_beneficio,
                  dependente.data_cadastro,
                  dependente.data_cad_pac,
                  C.id as idAprovacao,
                  C.filiacao AS filiacaoAprov,
                    C.status AS statusAprov,
                    C.doc_exigidos_ids_hist
                  

        FROM pacientes_dependentes pd
	INNER JOIN pacientes
	ON pd.paciente_id = pacientes.id
        INNER JOIN pacientes AS dependente
        ON  (pd.dependente_id = dependente.id     AND dependente.status_paciente = 1)    
         LEFT JOIN plano_aprovacoes AS C
         ON pd.dependente_id = C.pacientes_dep_id
	WHERE $sql AND pd.paciente_id = $idPaciente  
            AND pacientes.status_paciente = 1
	ORDER BY pacientes.id DESC ";

        $qr = $this->connClinicas()->select($descobreSeEDependente);
        return $qr;
    }

    public function store($idDominio, $dados) {

        $qr = $this->insertDB('pacientes_dependentes', $dados, null, 'clinicas');
        return $qr;
    }

    public function alteraAprovacaoDependente($idDominio, $idPacienteDependeteAssoc, $aprovadoPlBen) {

        return $this->updateDB("pacientes_dependentes", ['aprovado_pl_beneficio' => $aprovadoPlBen], " identificador =  $idDominio AND id = $idPacienteDependeteAssoc LIMIT 1", null, 'clinicas');
    }

    public function isDependente($idDominio, $idPaciente) {
        $qr = "SELECT A.*,    AES_DECRYPT(B.nome_cript, '$this->ENC_CODE') as nomeDependente,
                                                      AES_DECRYPT(B.sobrenome_cript, '$this->ENC_CODE') as sobrenomeDependente
                FROM pacientes_dependentes  AS A 
                                            INNER JOIN pacientes AS B
                                            ON A.paciente_id = B.id
					  WHERE A.dependente_id = '$idPaciente' ";

        $qr = $this->connClinicas()->select($qr);
        if (count($qr) > 0) {
            return $qr[0];
        } else {
            return false;
        }
    }
}
