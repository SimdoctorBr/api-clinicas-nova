<?php

namespace App\Repositories\Clinicas\Consulta;

use Illuminate\Support\Facades\DB;
use App\Repositories\BaseRepository;
use App\Helpers\Functions;

class ConsultaAtendAbertosRepository extends BaseRepository {

    public function getAll($idDominio, Array $dadosFiltro = null, Array $dadosPaginacao = null) {
//
//
//
//        $camposSql = "A.*,B.nome as nomeSecao, C.hora_consulta , C.data_consulta,C.doutores_id,C.prontuario_adicional, AES_DECRYPT(D.nome_cript, '$this->ENC_CODE') as nomeDoutor,C.habilita_visualizar_paciente,
//                                    CAST( AES_DECRYPT(A.json_prontuario_cript, '$this->ENC_CODE') AS CHAR(9999)) as json_prontuario,
//                                
//                                     AES_DECRYPT(E.nome_cript, '$this->ENC_CODE') as nome,
//                                     AES_DECRYPT(E.sobrenome_cript, '$this->ENC_CODE') as sobrenome,
//                                         E.id as idPaciente,
//                                         E.matricula, F.abreviacao as abreviacaoTitulo,C.duracao_atendimento, G.arquivo as arquivoAssinado, A.data_cad as dataCadPront, H.nome as nomeUserCad";
//
//        $from = " FROM consultas_prontuarios as A 
//                                INNER JOIN tipo_anotacao as B
//                                ON B.id = A.tipo_anotacao_id
//                                LEFT JOIN consultas as C
//                                ON A.consulta_id = C.id
//                                LEFT JOIN doutores as D
//                                ON D.id = C.doutores_id 
//                                LEFT JOIN pacientes AS E
//                                ON C.pacientes_id = E.id
//                                LEFT JOIN pronomes_tratamento as F
//                                ON D.pronome_id = F.idPronome
//                                LEFT JOIN consultas_doc_assinaturas as G
//                                ON G.consultas_id = A.consulta_id
//                                     LEFT JOIN administradores as H
//                               ON H.id = A.user_cad                                
//                                WHERE A.identificador = $idDominio
//                                 $sqlFiltro  ORDER BY YEAR(data_consulta) DESC, MONTH(data_consulta) DESC, DAY(data_consulta) DESC, A.consulta_id";
//
//        $qr = $this->paginacao($camposSql, $from, 'clinicas', $dadosPaginacao['page'], $dadosPaginacao['perPage']);
//        return $qr;
    }

    public function store($idDominio, $dadosInsert) {
        if (auth('clinicas')->check()) {
            $dadosInsert ['administrador_id'] = auth('clinicas')->user()->id;
        }
        $dadosInsert['data_cad'] = date('Y-m-d H:i:s');
        return $qr = $this->insertDB('consultas_atend_abertos', $dadosInsert, null, 'clinicas');
    }

    public function update($idDominio, $id, $dadosInsert) {
        return $qr = $this->updateDB('consultas_atend_abertos', $dadosInsert, "identificador = $idDominio AND id = $id LIMIT 1", $dadosCript, 'clinicas');
    }

    public function insertByConsultaId($idDominio, $consultaId) {
        $dadosInsert = null;
        $qr = $this->getByConsultaId($idDominio, $consultaId);

        if (count($qr) == 0) {
            if (auth('clinicas')->check()) {
                $dadosInsert ['administrador_id'] = auth('clinicas')->user()->id;
            }
            $dadosInsert['consultas_id'] = $consultaId;
            $dadosInsert['identificador'] = $idDominio;
            $dadosInsert['data_cad'] = date('Y-m-d H:i:s');
            return $qr = $this->insertDB('consultas_atend_abertos', $dadosInsert, null, 'clinicas');
        }
    }

    public function getByConsultaId($idDominio, $consultaid) {

        return $qr = $this->connClinicas()->select("SELECT * FROM consultas_atend_abertos
             WHERE identificador = $idDominio AND consultas_id = $consultaid ");
    }

    public function deleteByConsultaId($idDominio, $consultaid) {
        $qr = $this->connClinicas()->select("DELETE  FROM consultas_atend_abertos
             WHERE identificador = $idDominio AND consultas_id = $consultaid ");
    }

}
