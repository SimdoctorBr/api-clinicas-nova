<?php

namespace App\Repositories\Clinicas;

use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;
use App\Helpers\Functions;

class DoutoresRepository extends BaseRepository {

    public function sqlFilterTipoAtendimento($tiposAtendimento, $alias = 'A') {

        if (count(explode(',', $tiposAtendimento)) < 2) {
            if ($tiposAtendimento == 'presencial') {
                return " AND ($alias.possui_videoconf =0 OR ($alias.possui_videoconf = 1 AND  $alias.somente_videoconf=0))";
            } else if ($tiposAtendimento == 'video') {
                return " AND  $alias.possui_videoconf =1 ";
            }
        }
        return '';
    }

    public function sqlFilterSexo($filtroSexo, $alias = '') {

        $alias = (!empty($alias)) ? $alias . '.' : '';
        if (is_array($filtroSexo)) {
            $filtroSexo = array_map(function ($item) {
                return '"' . $item . '"';
            }, $filtroSexo);

            return " AND  $alias" . "sexo IN(" . implode(',', $filtroSexo) . ")";
        } else {
            return " AND   $alias" . "sexo  = '{$filtroSexo}'";
        }
        return '';
    }

    public function sqlFilterEspecialidade($idDominio, $especialidade, $alias = "A") {
        if (is_array($idDominio)) {
            $sqlDominioEsp = 'doutores_especialidades.identificador IN(' . implode(',', $idDominio) . ")";
        } else {
            $sqlDominioEsp = "doutores_especialidades.identificador = $idDominio";
        }
        return " AND ( (
                            (SELECT COUNT(*) FROM doutores_especialidades 
                            INNER JOIN especialidades
                            ON especialidades.id = doutores_especialidades.especialidade_id
                            WHERE  $sqlDominioEsp AND especialidades.nome = '" . utf8_encode($especialidade) . "'
                            AND doutores_especialidades.doutores_id = $alias.id
                            ) >0)
                            OR A.outra_especialidade = '" . utf8_encode($especialidade) . "'
                             OR    M.nome = '" . utf8_encode($especialidade) . "'
                        )
                            ";
    }

    public function sqlFilterIdioma($idDominio, Array $idiomas, $alias = "A") {
        $idiomaId = implode(',', array_filter($idiomas));

        return " AND (SELECT COUNT(*) FROM doutores_idiomas  WHERE
                                                identificador = $alias.identificador AND doutores_id =$alias.id AND idiomas_id IN($idiomaId) AND STATUS = 1
                            ) >0
                            ";
    }

    public function sqlFilterGruposAtendimento($idDominio, Array $gruposAtendimentoId, $alias = "A") {
        $idsGrupoAtend = implode(',', array_filter($gruposAtendimentoId));

        return " AND (SELECT COUNT(*)
                                    FROM doutores_grupo_atendimento
                                    WHERE identificador = $alias.identificador AND doutores_id = $alias.id  AND grupo_atendimento_id IN({$idsGrupoAtend}) AND status = 1
                            ) >0
                            ";
    }

    public function sqlFilterNomeFormacao(Array $formacoes, $alias = "A") {
        $nomesFormacao = array_filter($formacoes);
        $nomesFormacao = array_map(function ($item) {
            return "'" . $item . "'";
        }, $nomesFormacao);
        $nomesFormacao = implode(',', $nomesFormacao);

    
        return" AND (SELECT COUNT(*) FROM doutores_formacoes WHERE
                                               identificador = $alias.identificador AND nome_formacao IN($nomesFormacao)  AND doutores_id = $alias.id
                            ) >0
                            ";
    }

    public function sqlFilterTagsTratamento( $tagsTratamento, $alias = "A") {
        
      
        if (!is_array($tagsTratamento)) {
            $tagsTratamento = explode(',', $tagsTratamento);
        }
        $sqlTag = null;
         
        foreach ($tagsTratamento as $tagF) {
            $tagF = Functions::removeAccents($tagF);
            $sqlTag[] = "JSON_SEARCH(tags_tratamentos,'all','%".trim($tagF)."%') IS NOT NULL ";
        }
        return " AND (" . implode('OR ', $sqlTag) . ")";
    }

    /**
     * 
     * @param type $idDominio
     * @param type $valorConsulta
     * @param type $valorConsultaMax
     * @param type $tipoAtendimento
     * @param type $alias
     * @param type $aliasProcedimentosConveniosAssoc
     */
    public function sqlFilterValorConsulta($idDominio, $valorConsulta, $valorConsultaMax = null, $tipoAtendimento = null, $alias = "A", $aliasProcedimentosConveniosAssoc = "L") {
        if (isset($tipoAtendimento) and $tipoAtendimento == 'video') {

            $sqlFilter = '';
            if (isset($valorConsultaMax) and!empty($valorConsultaMax)) {
                $sqlFilter = " AND Bb.`valor`>='{$valorConsulta}' AND Bb.`valor`<='{$valorConsultaMax}'";
            } else {
                $sqlFilter = " AND Bb.`valor`>='{$valorConsulta}'";
            }

            return "
 AND  ( SELECT COUNT(*) FROM   procedimentos_doutores_assoc AS Aa
            LEFT JOIN procedimentos_convenios_assoc as Bb
            ON (Bb.convenios_id = Aa.proc_convenios_id AND Bb.procedimentos_id = Aa.procedimentos_id
				AND Bb.`status` = 1)         
         WHERE Aa.identificador = A.identificador AND Aa.id = $alias.proc_doutor_id_video AND Aa.status=1
         $sqlFilter
              ) >0";
        } else {

            if (isset($valorConsultaMax) and!empty($valorConsultaMax)) {
                return " AND  ($aliasProcedimentosConveniosAssoc.valor >= {$valorConsulta} AND
             $aliasProcedimentosConveniosAssoc.valor <= {$valorConsultaMax})";
            } else {
                return " AND  $aliasProcedimentosConveniosAssoc.valor >= '{$valorConsulta}'";
            }
        }
    }

    public function getAll($idDominio, $dadosFiltro = null, $page = null, $perPage = null) {


//        $idDominio = 3580;
        if (is_array($idDominio)) {
            $sql = 'A.identificador IN(' . implode(',', $idDominio) . ")";
        } else {
            $sql = "A.identificador = $idDominio";
        }



      


        $sqlFiltro = '';
        $sqlFiltroCampos = '';
//        if (isset($dadosFiltro['doutoresId'])) {
//            $sqlFiltro .= " AND  A.doutores_id = '" . $dadosFiltro['doutoresId'] . "'";
//        }
//        dd($dadosFiltro); 
        if (isset($dadosFiltro['nome']) and!empty($dadosFiltro['nome'])) {
            $sqlFiltro .= " AND  (CAST(AES_DECRYPT(nome_cript, '$this->ENC_CODE') AS CHAR(255)) like '%{$dadosFiltro['nome']}%' OR 
                CONVERT( BINARY   AES_DECRYPT(nome_cript, '$this->ENC_CODE')  USING latin1) LIKE '%{$dadosFiltro['nome']}%')";
        }
        if (isset($dadosFiltro['valorConsulta']) and!empty($dadosFiltro['valorConsulta'])) {

            $tipoAtendimentoF = (isset($dadosFiltro['tipoAtendimento'])) ? $dadosFiltro['tipoAtendimento'] : null;
            $valorConsultaMax = (isset($dadosFiltro['valorConsultaMax'])) ? $dadosFiltro['valorConsultaMax'] : null;
            $sqlFiltro .= $this->sqlFilterValorConsulta($idDominio, $dadosFiltro['valorConsulta'], $valorConsultaMax, $tipoAtendimentoF);
            unset($tipoAtendimentoF);

//            if (isset($dadosFiltro['tipoAtendimento']) and $dadosFiltro['tipoAtendimento'] == 'video') {
//
//                if (isset($dadosFiltro['valorConsultaMax']) and!empty($dadosFiltro['valorConsultaMax'])) {
//                    $sqlFiltro .= " AND  (CAST( REPLACE(A.preco_consulta,',','.') AS DECIMAL(11,2)) >= '{$dadosFiltro['valorConsulta']}' AND
//              CAST( REPLACE(A.preco_consulta,',','.') AS DECIMAL(11,2)) <= '{$dadosFiltro['valorConsultaMax']}')";
//                } else {
//                    $sqlFiltro .= " AND  (CAST( REPLACE(A.preco_consulta,',','.') AS DECIMAL(11,2)) >= '{$dadosFiltro['valorConsulta']}')";
//                }
//            } else {
//
//                if (isset($dadosFiltro['valorConsultaMax']) and!empty($dadosFiltro['valorConsultaMax'])) {
//                    $sqlFiltro .= " AND  (L.valor >= {$dadosFiltro['valorConsulta']} AND
//             L.valor <= {$dadosFiltro['valorConsultaMax']})";
//                } else {
//                    $sqlFiltro .= " AND  L.valor >= '{$dadosFiltro['valorConsulta']}')";
//                }
//            }
        }

        if (isset($dadosFiltro['doutorId']) and!empty($dadosFiltro['doutorId'])) {
            $sqlFiltro .= "AND  A.id = " . $dadosFiltro['doutorId'];
        }
        if (isset($dadosFiltro['sexo']) and!empty($dadosFiltro['sexo'])) {
            $sqlFiltro .= $this->sqlFilterSexo($dadosFiltro['sexo']);
        }


        if (isset($dadosFiltro['tipoAtendimento']) and!empty($dadosFiltro['tipoAtendimento'])) {
            $sqlFiltro .= $this->sqlFilterTipoAtendimento($dadosFiltro['tipoAtendimento']);
        }


        if (isset($dadosFiltro['especialidade']) and!empty($dadosFiltro['especialidade'])) {

            $sqlFiltro .= $this->sqlFilterEspecialidade($idDominio, $dadosFiltro['especialidade']);
        }

        if (isset($dadosFiltro['grupoAtendimentoId']) and count(array_filter($dadosFiltro['grupoAtendimentoId'])) > 0) {

            $sqlFiltro .= $this->sqlFilterGruposAtendimento($idDominio, $dadosFiltro['grupoAtendimentoId']);
        }

        if (isset($dadosFiltro['idiomaId']) and count(array_filter($dadosFiltro['idiomaId'])) > 0) {
            $sqlFiltro .= $this->sqlFilterIdioma($idDominio, $dadosFiltro['idiomaId']);
        }

        if (isset($dadosFiltro['nomeFormacao']) and count(array_filter($dadosFiltro['nomeFormacao'])) > 0) {
            $sqlFiltro .= $this->sqlFilterNomeFormacao($dadosFiltro['nomeFormacao']);
        }

        if (isset($dadosFiltro['tags']) and!empty($dadosFiltro['tags'])) {     
            
            $sqlFiltro .= $this->sqlFilterTagsTratamento($dadosFiltro['tags']);
        }

        if (isset($dadosFiltro['favoritoPacienteId']) and!empty($dadosFiltro['favoritoPacienteId'])) {
            $sqlFiltroCampos .= ", (SELECT id FROM pacientes_doutores_favoritos WHERE
                                               identificador = A.identificador AND doutores_id = A.id AND pacientes_id = " . $dadosFiltro['favoritoPacienteId'] . " 
                            ) as favoritoPaciente
                            ";
        }


        if (isset($dadosFiltro['totalConsultasAtendidas']) and $dadosFiltro['totalConsultasAtendidas'] == true) {

            $sqlFiltroCampos .= ",
                (SELECT COUNT(*)
     FROM consultas 
    WHERE consultas.doutores_id = A.id AND consultas.identificador = A.identificador
    AND (SELECT STATUS FROM consultas_status WHERE consulta_id =consultas.id ORDER BY id DESC LIMIT 1) = 'jaFoiAtendido') as totalConsultasAtendidas";
        }

       

        $orderBy = "A.nome ASC";
        if (isset($dadosFiltro['orderBy']) and!empty($dadosFiltro['orderBy'])) {
            $orderBy = "  {$dadosFiltro['orderBy']} ";
        }

        if (isset($dadosFiltro['agruparIdDoutor']) and $dadosFiltro['agruparIdDoutor'] == true) {
            $camposSQL = 'GROUP_CONCAT(A.id) as idsDoutores,GROUP_CONCAT(A.sexo) AS sexos';
        } else {
            $camposSQL = "A.id,A.sobre,A.sexo,A.especialidades_id,A.outra_especialidade  ,A.identificador,A.preco_consulta as precoConsulta,A.website,A.mensagem_antes_marcar,A.pronome_id,A.cor,A.cor_letra,A.conselho_profissional_id,
            A.conselho_uf_id,A.cbo_s_id,A.nome_foto,A.data_cad as dataCad,A.administrador_id_cad,A.possui_repasse,A.tipo_repasse,A.valor_repasse,A.possui_videoconf,A.permite_agenda_website,A.doutor_parceiro,A.nome_responsavel,
            A.status_doutor,A.somente_videoconf, A.plano_aluguel_sala_id,A.tags_tratamentos,A.pontuacao,A.link_video,A.dt_ini_atividade_prof,
            AES_DECRYPT(nome_cript, '$this->ENC_CODE') as nome,A.proc_doutor_id_video,
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
                                    A.duracao_videocons,A.dt_ini_atividade_prof,
                                B.abreviacao, B.nome as nomePronome,B.artigo, D.nome as nomeConselhoProfissional, D.codigo as codigoConselhoProfisssional,
                                                E.ds_uf_nome as nomeUFConselhoProfisional, E.ds_uf_sigla as siglaUFConselhoProfisional, F.codigo as codigoCBO, F.nome as nomeCBO,
                                                   AES_DECRYPT(conselho_profissional_numero_cript, '$this->ENC_CODE') as conselho_profissional_numero,G.nome as nomePlanoSala, proc_doutor_id_presencial,I.procedimento_nome as procPadraoNome,
    I.idPRocedimento as procPadraoIdProcedimento,I.duracao as procDuracao, J.nome as procPadraoNomeConvenio,J.id as procPadraoIdConvenio,  L.valor AS procPadraoValor, M.nome as nomeEspecialidade  $sqlFiltroCampos";
        }
        $from = " 
            FROM doutores AS A 
             LEFT JOIN pronomes_tratamento as B
            ON A.pronome_id = B.idPronome
            LEFT JOIN tiss_conselhos_profisionais as D
            ON D.id = A.conselho_profissional_id
            LEFT JOIN uf as E
            ON E.cd_uf = A.conselho_uf_id
            LEFT JOIN tiss_cbo_s as F
            ON F.id =  A.cbo_s_id
            LEFT JOIN plano_aluguel_salas as G
            ON (G.id =  A.plano_aluguel_sala_id AND G.status = 1)
              LEFT JOIN procedimentos_doutores_assoc as H
            ON (H.id =  A.proc_doutor_id_presencial AND 	 H.`status`=1)
             LEFT JOIN procedimentos as I
            ON (I.idProcedimento =  H.procedimentos_id)
               LEFT JOIN convenios as J
            ON (J.id =  H.proc_convenios_id)
              LEFT JOIN procedimentos_convenios_assoc as L
            ON (L.convenios_id =  H.proc_convenios_id AND L.procedimentos_id = H.procedimentos_id
				AND L.`status` = 1)
             LEFT JOIN especialidades as M
            ON M.id = A.especialidades_id
            WHERE  $sql AND A.status_doutor = 1 $sqlFiltro     ";
// var_dump($sql);
//        if (auth('clinicas')->user()->id = 4055) {
//        var_dump("SELECT $camposSQL $from");    
//            exit;
//        }


        if ($page == null and $perPage == null) {
            $qr = $this->connClinicas()->select("SELECT $camposSQL $from ORDER BY $orderBy");
            return $qr;
        } else {
            $qr = $this->paginacao($camposSQL, $from, 'clinicas', $page, $perPage, false, $orderBy);
            return $qr;
        }
    }

    public function getById($idDominio, $idDoutor) {

        if (is_array($idDominio)) {
            $sql = 'A.identificador IN(' . implode(',', $idDominio) . ")";
        } else {
            $sql = "A.identificador = $idDominio";
        }

        $qr = $this->connClinicas()->select("SELECT A.id,A.sobre,A.sexo,A.especialidades_id,A.outra_especialidade,A.identificador,A.preco_consulta as precoConsulta,A.website,A.mensagem_antes_marcar,A.pronome_id,A.cor,A.cor_letra,A.conselho_profissional_id,
            A.conselho_uf_id,A.cbo_s_id,A.nome_foto,A.data_cad as dataCad,A.administrador_id_cad,A.possui_repasse,A.tipo_repasse,A.valor_repasse,A.possui_videoconf,A.permite_agenda_website,A.doutor_parceiro,A.nome_responsavel,
            A.status_doutor,A.somente_videoconf, A.plano_aluguel_sala_id,A.tags_tratamentos,A.pontuacao,A.link_video,A.dt_ini_atividade_prof,
            AES_DECRYPT(nome_cript, '$this->ENC_CODE') as nome,
                    A.duracao_videocons,A.dt_ini_atividade_prof,
                                AES_DECRYPT(email_cript, '$this->ENC_CODE') as email,
                                AES_DECRYPT(telefone_cript, '$this->ENC_CODE') as telefone,
                                AES_DECRYPT(celular1_cript, '$this->ENC_CODE') as celular1,
                                AES_DECRYPT(celular2_cript, '$this->ENC_CODE') as celular2,
                                AES_DECRYPT(cpf_cript, '$this->ENC_CODE') as cpf,
                                AES_DECRYPT(cnpj_cript, '$this->ENC_CODE') as cnpj, 
                                AES_DECRYPT(cns_cript, '$this->ENC_CODE') as cns, B.abreviacao, B.nome as nomePronome,B.artigo, D.nome as nomeConselhoProfissional, D.codigo as codigoConselhoProfisssional,
                                E.ds_uf_nome as nomeUFConselhoProfisional, E.ds_uf_sigla as siglaUFConselhoProfisional, F.codigo as codigoCBO, F.nome as nomeCBO,
                                   AES_DECRYPT(conselho_profissional_numero_cript, '$this->ENC_CODE') as conselho_profissional_numero, I.procedimento_nome as procPadraoNome,
    I.idPRocedimento as procPadraoIdProcedimento,I.duracao as procDuracao, J.nome as procPadraoNomeConvenio,J.id as procPadraoIdConvenio,  L.valor AS procPadraoValor,proc_doutor_id_presencial, M.nome as nomeEspecialidade,A.proc_doutor_id_video
                                    
                                    FROM doutores as A 
                                LEFT JOIN pronomes_tratamento as B
                                ON A.pronome_id = B.idPronome
                                LEFT JOIN tiss_conselhos_profisionais as D
                                ON D.id = A.conselho_profissional_id
                                LEFT JOIN uf as E
                                ON E.cd_uf = A.conselho_uf_id
                                LEFT JOIN tiss_cbo_s as F
                                ON F.id =  A.cbo_s_id        
                                  LEFT JOIN procedimentos_doutores_assoc as H
            ON (H.id =  A.proc_doutor_id_presencial AND 	 H.`status`=1)
             LEFT JOIN procedimentos as I
            ON (I.idProcedimento =  H.procedimentos_id)
               LEFT JOIN convenios as J
            ON (J.id =  H.proc_convenios_id)
              LEFT JOIN procedimentos_convenios_assoc as L
            ON (L.convenios_id =  H.proc_convenios_id AND L.procedimentos_id = H.procedimentos_id
				AND L.`status` = 1)                                
          LEFT JOIN especialidades as M
            ON M.id = A.especialidades_id

                                    WHERE $sql AND A.id = $idDoutor");
        if (count($qr) > 0) {
            return $qr[0];
        } else {
            return false;
        }
    }

    /**
     * 
     * @param type $idDominio
     * @return boolean
     */
    public function getValorConsultaMinMax($idDominio) {

        if (is_array($idDominio)) {
            $sql = 'A.identificador IN(' . implode(',', $idDominio) . ")";
        } else {
            $sql = "A.identificador = $idDominio";
        }

        $retorno = null;
        $qrPresencial = $this->connClinicas()->select("SELECT MIN(CAST( L.valor AS DECIMAL(10,2))) AS valorMinimoPresencial, MAX(CAST( L.valor AS DECIMAL(10,2)))  AS valorMaximoPresencial
            FROM doutores AS A 
              LEFT JOIN procedimentos_doutores_assoc as H
            ON (H.id =  A.proc_doutor_id_presencial AND 	 H.`status`=1)
             LEFT JOIN procedimentos as I
            ON (I.idProcedimento =  H.procedimentos_id)
               LEFT JOIN convenios as J
            ON (J.id =  H.proc_convenios_id)
              LEFT JOIN procedimentos_convenios_assoc as L
            ON (L.convenios_id =  H.proc_convenios_id AND L.procedimentos_id = H.procedimentos_id
				AND L.`status` = 1)
            WHERE  $sql AND A.status_doutor = 1   ");

        $retorno['valorMinimoPresencial'] = $qrPresencial[0]->valorMinimoPresencial;
        $retorno['valorMaximoPresencial'] = $qrPresencial[0]->valorMaximoPresencial;

        $qrVideo = $this->connClinicas()->select("SELECT MIN(CAST( L.valor AS DECIMAL(10,2))) AS valorMinimoVideo, MAX(CAST( L.valor AS DECIMAL(10,2)))  AS valorMaximoVideo
            FROM doutores AS A 
              LEFT JOIN procedimentos_doutores_assoc as H
            ON (H.id =  A.proc_doutor_id_video AND H.`status`=1)
             LEFT JOIN procedimentos as I
            ON (I.idProcedimento =  H.procedimentos_id)
               LEFT JOIN convenios as J
            ON (J.id =  H.proc_convenios_id)
              LEFT JOIN procedimentos_convenios_assoc as L
            ON (L.convenios_id =  H.proc_convenios_id AND L.procedimentos_id = H.procedimentos_id
				AND L.`status` = 1)
            WHERE  $sql AND A.status_doutor = 1   AND possui_videoconf=1 ");

//        $qrVideo = $this->connClinicas()->select("SELECT 
//                MIN(CAST( preco_consulta AS DECIMAL(10,2))) AS valorMinimoVideo,MAX(CAST( preco_consulta AS DECIMAL(10,2))) AS valorMaximoVideo
//            FROM doutores AS A 
//            WHERE  $sql AND A.status_doutor = 1  AND possui_videoconf=1  ");
////        
        $retorno['valorMinimoVideo'] = $qrVideo[0]->valorMinimoVideo;
        $retorno['valorMaximoVideo'] = $qrVideo[0]->valorMaximoVideo;

        return $retorno;
    }

}
