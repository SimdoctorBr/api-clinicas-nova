<?php

namespace App\Repositories\Clinicas\Consulta;

use Illuminate\Support\Facades\DB;
use App\Repositories\BaseRepository;
use App\Helpers\Functions;

class ConsultaProntuarioRepository extends BaseRepository {

    public function getAll($idDominio, Array $dadosFiltro = null, Array $dadosPaginacao = null) {

        $sqlFiltro = '';
        $orderBy = 'ORDER BY CAST( BINARY F.nome  AS CHAR CHARACTER SET utf8) ASC,dataF DESC, horarioF DESC ;';

        if (isset($dadosFiltro['pacienteId']) and!empty($dadosFiltro['pacienteId'])) {
            $sqlFiltro .= " AND C.pacientes_id = '{$dadosFiltro['pacienteId']}'";
            $orderBy = 'ORDER BY dataF DESC, horarioF DESC ;';
        }

        if (isset($dadosFiltro['doutorId']) and!empty($dadosFiltro['doutorId'])) {
            $sqlFiltro .= " AND C.doutores_id = {$dadosFiltro['doutorId']}";
        }

        if (isset($dadosFiltro['data']) and!empty($dadosFiltro['data'])) {
            if (isset($dadosFiltro['dataFim']) and!empty($dadosFiltro['dataFim'])) {
                $sqlFiltro .= "AND data_consulta >= '{$dadosFiltro['data']}' AND data_consulta <='{$dadosFiltro['dataFim']}'";
            } else {
                $sqlFiltro .= " AND C.data_consulta = {$dadosFiltro['data']}";
            }
        }
        if (isset($dadosFiltro['search']) and!empty($dadosFiltro['search'])) {
            $sqlFiltro .= "AND (AES_DECRYPT(A.json_prontuario_cript, '$this->ENC_CODE') LIKE '%{$dadosFiltro['search']}%')";
        }
        if (isset($dadosFiltro['secaoId']) and!empty($dadosFiltro['secaoId'])) {
            $sqlFiltro .= ' AND A.tipo_anotacao_id = ' . $dadosFiltro['secaoId'];
        }



        $camposSql = "A.*,B.nome as nomeSecao, C.hora_consulta , C.data_consulta,C.doutores_id,C.prontuario_adicional, AES_DECRYPT(D.nome_cript, '$this->ENC_CODE') as nomeDoutor,C.habilita_visualizar_paciente,
                                    CAST( AES_DECRYPT(A.json_prontuario_cript, '$this->ENC_CODE') AS CHAR(9999)) as json_prontuario,
                                
                                     AES_DECRYPT(E.nome_cript, '$this->ENC_CODE') as nome,
                                     AES_DECRYPT(E.sobrenome_cript, '$this->ENC_CODE') as sobrenome,
                                         E.id as idPaciente,
                                         E.matricula, F.abreviacao as abreviacaoTitulo,C.duracao_atendimento, G.arquivo as arquivoAssinado, A.data_cad as dataCadPront, H.nome as nomeUserCad";

        $from = " FROM consultas_prontuarios as A 
                                INNER JOIN tipo_anotacao as B
                                ON B.id = A.tipo_anotacao_id
                                LEFT JOIN consultas as C
                                ON A.consulta_id = C.id
                                LEFT JOIN doutores as D
                                ON D.id = C.doutores_id 
                                LEFT JOIN pacientes AS E
                                ON C.pacientes_id = E.id
                                LEFT JOIN pronomes_tratamento as F
                                ON D.pronome_id = F.idPronome
                                LEFT JOIN consultas_doc_assinaturas as G
                                ON G.consultas_id = A.consulta_id
                                     LEFT JOIN administradores as H
                               ON H.id = A.user_cad                                
                                WHERE A.identificador = $idDominio
                                 $sqlFiltro  ORDER BY YEAR(data_consulta) DESC, MONTH(data_consulta) DESC, DAY(data_consulta) DESC, A.consulta_id";

        $qr = $this->paginacao($camposSql, $from, 'clinicas', $dadosPaginacao['page'], $dadosPaginacao['perPage']);
        return $qr;
    }

    public function store($idDominio, $dadosInsert) {
        $dadosCript = ['json_prontuario_cript'];

        if (auth('clinicas')->check()) {
            $dadosCript ['user_cad'] = auth('clinicas')->user()->id;
        }
        return $qr = $this->insertDB('consultas_prontuarios', $dadosInsert, $dadosCript, 'clinicas');
    }

    public function update($idDominio, $id, $dadosInsert) {
        $dadosCript = ['json_prontuario_cript'];
        if (auth('clinicas')->check()) {
            $dadosCript ['user_cad'] = auth('clinicas')->user()->id;
        }
        return $qr = $this->updateDB('consultas_prontuarios', $dadosInsert, "identificador = $idDominio AND id = $id LIMIT 1", $dadosCript, 'clinicas');
    }

    public function getById($idDominio, $consultaProntuarioId) {

        $qr = $this->connClinicas()->select("SELECT A.*,AES_DECRYPT(A.json_prontuario_cript, '$this->ENC_CODE') as json_prontuario_cript,
            B.nome as nomeSecao
            FROM consultas_prontuarios  as A 
               LEFT JOIN tipo_anotacao as B
             ON B.id = A.tipo_anotacao_id

WHERE A.identificador = $idDominio AND A.id = $consultaProntuarioId");

        if (count($qr) > 0) {
            return $qr[0];
        } else {
            return false;
        }
    }

    public function getByConsultaId($idDominio, $consultaid, $somenteProntuarioSimples = false) {

        $sql = '';
        if ($somenteProntuarioSimples) {
            $sql = "AND prontuario_simples = 1";
        }

        return $qr = $this->connClinicas()->select("SELECT A.*,AES_DECRYPT(A.json_prontuario_cript, '$this->ENC_CODE') as json_prontuario_cript,
              B.nome as nomeSecao, C.nome  as nomeUserCad
            FROM consultas_prontuarios  as A 
                LEFT JOIN tipo_anotacao as B
             ON B.id = A.tipo_anotacao_id
              LEFT JOIN administradores as C
            ON A.user_cad = C.id
             WHERE A.identificador = $idDominio AND A.consulta_id = $consultaid $sql");
    }

    //Observações
    public function getObservacoes($idDominio, $idConsultaPront, $dadosFiltro = null) {

        $sqlFiltro = '';
        if (isset($dadosFiltro['id']) and!empty($dadosFiltro['id'])) {
            $sqlFiltro .= " AND A.id = {$dadosFiltro['id']}";
        }


        $qr = $this->connClinicas()->select("SELECT A.*,    B.nome  as nomeUserCad
            FROM consultas_prontuarios_observacoes as A
            LEFT JOIN administradores as B
            ON A.administrador_id_cad = B.id
            WHERE A.identificador = $idDominio AND A.consultas_prontuarios_id = {$idConsultaPront} $sqlFiltro  ORDER BY data_cad DESC");

        if (isset($dadosFiltro['id']) and!empty($dadosFiltro['id'])) {
            if (count($qr) > 0) {
                return $qr[0];
            } else {
                return false;
            }
        } else {
            return $qr;
        }
    }

    //Observações
    public function storeObservacoes($idDominio, $idConsultaPront, $dadosInsert) {

        $dadosInsert['identificador'] = $idDominio;
        $dadosInsert['consultas_prontuarios_id'] = $idConsultaPront;
        $dadosInsert['observacao'] = ($dadosInsert['observacao']);
        $dadosInsert['data_cad'] = date('Y-m-d H:i:s');

        if (auth('clinicas')->check()) {
            $dadosInsert['administrador_id_cad'] = auth('clinicas')->user()->id;
            $dadosInsert['administrador_nome_cad'] = utf8_decode(auth('clinicas')->user()->nome);
        }
        $campoCript = ['administrador_nome_cad'];
        $qr = $this->insertDB('consultas_prontuarios_observacoes', $dadosInsert, $campoCript, 'clinicas');
        return $qr;
    }

}
