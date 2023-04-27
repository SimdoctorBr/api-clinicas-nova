<?php

namespace App\Repositories\Clinicas\Paciente;

use Illuminate\Support\Facades\DB;
use App\Repositories\BaseRepository;
use App\Helpers\Functions;

class PacienteFavoritosRepository extends BaseRepository {

    public function getAll($idDominio, $idPaciente, Array $dadosFiltro = null, $page = null, $perPage = null) {

        $sqlOrdem = 'ORDER BY id';

        if (is_array($idDominio)) {
            $sql = 'A.identificador IN(' . implode(',', $idDominio) . ")";
        } else {
            $sql = " A.identificador = $idDominio ";
        }



        $camposSql = "A.*,A.id as idFavorito, B.id,B.sobre,B.sexo,B.especialidades_id,B.identificador,B.preco_consulta as precoConsulta,B.website,B.mensagem_antes_marcar,B.pronome_id,B.cor,B.cor_letra,B.conselho_profissional_id,
            B.conselho_uf_id,B.cbo_s_id,B.nome_foto,B.data_cad as dataCad,B.administrador_id_cad,B.possui_repasse,B.tipo_repasse,B.valor_repasse,B.possui_videoconf,B.permite_agenda_website,B.doutor_parceiro,B.nome_responsavel,
            B.status_doutor,B.somente_videoconf, B.plano_aluguel_sala_id,B.tags_tratamentos,B.pontuacao,B.identificador,B.link_video,B.proc_doutor_id_video,
            AES_DECRYPT(B.nome_cript, '$this->ENC_CODE') as nome,
                                AES_DECRYPT(B.email_cript, '$this->ENC_CODE') as email,
                                AES_DECRYPT(B.telefone_cript, '$this->ENC_CODE') as telefone,
                                AES_DECRYPT(B.celular1_cript, '$this->ENC_CODE') as celular1,
                                AES_DECRYPT(B.celular2_cript, '$this->ENC_CODE') as celular2,
                                AES_DECRYPT(B.cpf_cript, '$this->ENC_CODE') as cpf,
                                AES_DECRYPT(B.cnpj_cript, '$this->ENC_CODE') as cnpj,
                                AES_DECRYPT(B.cns_cript, '$this->ENC_CODE') as cns,
                                AES_DECRYPT(B.banco1_cript, '$this->ENC_CODE') as banco1,
                                AES_DECRYPT(B.conta1_cript, '$this->ENC_CODE') as conta1,
                                AES_DECRYPT(B.agencia1_cript, '$this->ENC_CODE') as agencia1,
                                AES_DECRYPT(B.banco2_cript, '$this->ENC_CODE') as banco2,
                                AES_DECRYPT(B.conta2_cript, '$this->ENC_CODE') as conta2,
                                AES_DECRYPT(B.agencia2_cript, '$this->ENC_CODE') as agencia2,
                                     C.abreviacao, C.nome as nomePronome,C.artigo,E.codigo as codigoConselhoProfisssional,
                                                F.ds_uf_nome as nomeUFConselhoProfisional, F.ds_uf_sigla as siglaUFConselhoProfisional, G.codigo as codigoCBO, G.nome as nomeCBO,
                                                   AES_DECRYPT(conselho_profissional_numero_cript, '$this->ENC_CODE') as conselho_profissional_numero,proc_doutor_id_presencial,I.procedimento_nome as procPadraoNome,
    I.idPRocedimento as procPadraoIdProcedimento,I.duracao as procDuracao, J.nome as procPadraoNomeConvenio,J.id as procPadraoIdConvenio,  L.valor AS procPadraoValor
                ";
        $from = "FROM pacientes_doutores_favoritos as A 
                INNER JOIN doutores as B
                ON A.doutores_id = B.id   
                
  LEFT JOIN pronomes_tratamento as C
            ON B.pronome_id = C.idPronome
            LEFT JOIN tiss_conselhos_profisionais as E
            ON E.id = B.conselho_profissional_id
            LEFT JOIN uf as F
            ON F.cd_uf = B.conselho_uf_id
            LEFT JOIN tiss_cbo_s as G
            ON G.id =  B.cbo_s_id
            


  LEFT JOIN procedimentos_doutores_assoc as H
            ON (H.id =  B.proc_doutor_id_presencial AND 	 H.`status`=1)
             LEFT JOIN procedimentos as I
            ON (I.idProcedimento =  H.procedimentos_id)
               LEFT JOIN convenios as J
            ON (J.id =  H.proc_convenios_id)
              LEFT JOIN procedimentos_convenios_assoc as L
            ON (L.convenios_id =  H.proc_convenios_id AND L.procedimentos_id = H.procedimentos_id
				AND L.`status` = 1)
                WHERE $sql AND pacientes_id = $idPaciente";

      
        
        if ($page == null and $perPage == null) {
            $qr = $this->connClinicas()->select("SELECT $camposSql $from");
            return $qr;
        } else {
            $qr = $this->paginacao($camposSql, $from, 'clinicas', $page, $perPage, false);
            return $qr;
        }
    }

    public function verificaAdicionadoByDoutorId($idDominio, $pacienteId, $doutores_id) {

        $qr = $this->connClinicas()->select("SELECT id FROM pacientes_doutores_favoritos WHERE identificador = $idDominio AND pacientes_id =$pacienteId AND  doutores_id= $doutores_id ");
        if (count($qr) > 0) {
            return $qr[0];
        } else {
            return false;
        }
    }
    public function verificaAdicionadoByIdFavorito($idDominio, $pacienteId, $idFavorito) {

        $qr = $this->connClinicas()->select("SELECT id FROM pacientes_doutores_favoritos WHERE identificador = $idDominio AND pacientes_id =$pacienteId AND  id= $idFavorito ");
        if (count($qr) > 0) {
            return $qr[0];
        } else {
            return false;
        }
    }

    public function store($idDominio, $pacienteId, $doutores_id) {

        $campos['identificador'] = $idDominio;
        $campos['pacientes_id'] = $pacienteId;
        $campos['doutores_id'] = $doutores_id;

        $verifica = $this->verificaAdicionadoByDoutorId($idDominio, $pacienteId, $doutores_id);
        if (!$verifica) {
            $qr =  (int) $this->insertDB('pacientes_doutores_favoritos', $campos, null, 'clinicas');
        } else {

            return $verifica->id;
        }

        return $qr;
    }

    public function delete($idDominio, $pacienteId, $idFavorito) {

     return   $qr = $this->connClinicas()->select("DELETE FROM pacientes_doutores_favoritos WHERE identificador = $idDominio AND id = $idFavorito LIMIT 1");
    }

}
