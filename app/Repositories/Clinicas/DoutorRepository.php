<?php

namespace App\Repositories\Clinicas;

use Illuminate\Support\Facades\DB;
use App\Repositories\BaseRepository;

class DoutorRepository extends BaseRepository {

    public function getAllById($idDominio, $idDoutor = null) {


        if (is_array($idDominio)) {
            $sql = 'A.identificador IN(' . implode(',', $idDominio) . ")";
        } else {
            $sql = "A.identificador = $idDominio";
        }

        if (!empty($idDoutor)) {
            $sql .= " AND A.id = $idDoutor";
        }

        $qr = $this->connClinicas()->select("SELECT A.*, AES_DECRYPT(A.nome_cript, '$this->ENC_CODE') as nomeDoutor,
                                AES_DECRYPT(A.email_cript, '$this->ENC_CODE') as email,
                                AES_DECRYPT(A.telefone_cript, '$this->ENC_CODE') as telefone,
                                AES_DECRYPT(A.celular1_cript, '$this->ENC_CODE') as celular1,
                                AES_DECRYPT(A.celular2_cript, '$this->ENC_CODE') as celular2,
                                AES_DECRYPT(A.cpf_cript, '$this->ENC_CODE') as cpf,
                                AES_DECRYPT(A.cnpj_cript, '$this->ENC_CODE') as cnpj,
                                AES_DECRYPT(A.cns_cript, '$this->ENC_CODE') as cns,
                                B.abreviacao, B.nome as nomePronome,B.artigo, C.nome as nomeEspecialidade, D.nome as nomeConselhoProfissional, D.codigo as codigoConselhoProfisssional,
                                E.ds_uf_nome as nomeUFConselhoProfisional, E.ds_uf_sigla as siglaUFConselhoProfisional, F.codigo as codigoCBO, F.nome as nomeCBO,
                                   AES_DECRYPT(conselho_profissional_numero_cript, '$this->ENC_CODE') as conselho_profissional_numero,
                                    G.exibe_parceiro_agenda,G.mostra_doutor_tuotempo,
                                    A.identificador
                                    FROM doutores as A 
                                      LEFT JOIN pronomes_tratamento as B
                                                    ON A.pronome_id = B.idPronome
                                                    LEFT JOIN especialidades as C
                                                    ON A.especialidades_id = C.id
                                                    LEFT JOIN tiss_conselhos_profisionais as D
                                                    ON D.id = A.conselho_profissional_id
                                                    LEFT JOIN uf as E
                                                    ON E.cd_uf = A.conselho_uf_id
                                                    LEFT JOIN tiss_cbo_s as F
                                                    ON F.id =  A.cbo_s_id                                                    
                                    LEFT JOIN definicoes_marcacao_consulta as G
                                    ON A.id = G.doutores_id
                                    WHERE $sql AND A.status_doutor =1 AND (doutor_parceiro IS NULL OR (G.exibe_parceiro_agenda = 1 AND A.doutor_parceiro =1)) ");
        return $qr;
    }

    public function getAllByConvenioId($idDominio, $insuranceId) {


        if (is_array($idDominio)) {
            $sql = 'A.identificador IN(' . implode(',', $idDominio) . ")";
        } else {
            $sql = "A.identificador = $idDominio";
        }
        $qr = $this->connClinicas()->select("SELECT A.*, B.nome, AES_DECRYPT(C.nome_cript, '$this->ENC_CODE') as nomeDoutor,
                                AES_DECRYPT(C.email_cript, '$this->ENC_CODE') as email,
                                AES_DECRYPT(C.telefone_cript, '$this->ENC_CODE') as telefone,
                                AES_DECRYPT(C.celular1_cript, '$this->ENC_CODE') as celular1,
                                AES_DECRYPT(C.celular2_cript, '$this->ENC_CODE') as celular2,
                                AES_DECRYPT(C.cpf_cript, '$this->ENC_CODE') as cpf,
                                AES_DECRYPT(C.cnpj_cript, '$this->ENC_CODE') as cnpj,
                                AES_DECRYPT(C.cns_cript, '$this->ENC_CODE') as cns,
                                    D.nome as nomeEspecialidade,E.nome as nomeConselhoProfissional, E.codigo as codigoConselhoProfisssional,
                                     F.ds_uf_nome as nomeUFConselhoProfisional, F.ds_uf_sigla as siglaUFConselhoProfisional,G.codigo as codigoCBO, G.nome as nomeCBO,
                                   AES_DECRYPT(conselho_profissional_numero_cript, '$this->ENC_CODE') as conselho_profissional_numero,
                                         H.exibe_parceiro_agenda,H.mostra_doutor_tuotempo,
                                    A.identificador
                               FROM convenios_doutores AS A
                                LEFT JOIN convenios AS B
                                ON A.convenios_id = B.id
                                INNER JOIN doutores AS C
                                ON A.doutores_id = C.id
                                LEFT JOIN especialidades as D
                                ON C.especialidades_id = D.id
                                LEFT JOIN tiss_conselhos_profisionais as E
                                ON E.id = C.conselho_profissional_id
                                LEFT JOIN uf as F
                                 ON F.cd_uf = C.conselho_uf_id
                                 LEFT JOIN tiss_cbo_s as G
                                ON G.id =  C.cbo_s_id    
                                 LEFT JOIN definicoes_marcacao_consulta as H
                                 ON C.id = H.doutores_id
                                WHERE $sql AND A.`status` = 1 AND A.convenios_id = '$insuranceId'
                                 AND C.status_doutor =1 AND (C.doutor_parceiro IS NULL OR (H.exibe_parceiro_agenda = 1 AND C.doutor_parceiro =1))");
        return $qr;
    }

    public function getById($idDominio, $doutorId) {


        $qr = $this->connClinicas()->select("     SELECT A.*, 
                                     AES_DECRYPT(nome_cript, '$this->ENC_CODE') as nome,
                                AES_DECRYPT(email_cript, '$this->ENC_CODE') as email,
                                AES_DECRYPT(telefone_cript, '$this->ENC_CODE') as telefone,
                                AES_DECRYPT(celular1_cript, '$this->ENC_CODE') as celular1,
                                AES_DECRYPT(celular2_cript, '$this->ENC_CODE') as celular2,
                                AES_DECRYPT(cpf_cript, '$this->ENC_CODE') as cpf,
                                AES_DECRYPT(cnpj_cript, '$this->ENC_CODE') as cnpj,
                                AES_DECRYPT(cns_cript, '$this->ENC_CODE') as cns,
                                AES_DECRYPT(banco1_cript, '$this->ENC_CODE') as banco1,
                                AES_DECRYPT(conta1_cript, '$this->ENC_CODE') as conta1,
                                AES_DECRYPT(agencia1_cript, '$this->ENC_CODE') as agencia1,
                                AES_DECRYPT(banco2_cript, '$this->ENC_CODE') as banco2,
                                AES_DECRYPT(conta2_cript, '$this->ENC_CODE') as conta2,
                                AES_DECRYPT(agencia2_cript, '$this->ENC_CODE') as agencia2,
                                B.abreviacao, B.nome as nomePronome,B.artigo, C.nome as nomeEspecialidade, D.nome as nomeConselhoProfissional, D.codigo as codigoConselhoProfisssional,
                                                E.ds_uf_nome as nomeUFConselhoProfisional, E.ds_uf_sigla as siglaUFConselhoProfisional, F.codigo as codigoCBO, F.nome as nomeCBO,
                                                   AES_DECRYPT(conselho_profissional_numero_cript, '$this->ENC_CODE') as conselho_profissional_numero
                                                      
                                                 FROM doutores as A
                                                    LEFT JOIN pronomes_tratamento as B
                                                    ON A.pronome_id = B.idPronome
                                                    LEFT JOIN especialidades as C
                                                    ON A.especialidades_id = C.id
                                                    LEFT JOIN tiss_conselhos_profisionais as D
                                                    ON D.id = A.conselho_profissional_id
                                                    LEFT JOIN uf as E
                                                    ON E.cd_uf = A.conselho_uf_id
                                                    LEFT JOIN tiss_cbo_s as F
                                                    ON F.id =  A.cbo_s_id
                                                    
                                                    WHERE A.id = '$doutorId'  AND A.identificador = $idDominio ");

        if (count($qr) > 0) {
            return $qr[0];
        } else {
            return false;
        }
    }

}
