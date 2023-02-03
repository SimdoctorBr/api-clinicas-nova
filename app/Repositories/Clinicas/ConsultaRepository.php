<?php

namespace App\Repositories\Clinicas;

use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;

class ConsultaRepository extends BaseRepository {

    private function sqlFilterStatusSomenteAgendado() {
        return " AND (SELECT status FROM consultas_status WHERE consulta_id = A.id ORDER BY id DESC LIMIT 1) IS NULL ";
    }

    /**
     * pode ser mais deum separado por vírgula
     * @param type $statusConsultas
     */
    private function sqlFilterStatus($statusConsultas) {
        $filtrosStatus = explode(',', $statusConsultas);

        foreach ($filtrosStatus as $statusConsulta) {
            $sqlFiltroStatus[] = " (SELECT status FROM consultas_status WHERE consulta_id = A.id ORDER BY id DESC LIMIT 1) = '$statusConsulta' ";
        }
        return " AND (" . implode(' OR ', $sqlFiltroStatus) . ")";
    }

    public function getAll($idDominio, $dadosFiltro = null, $page = null, $perPage = null) {

        if (is_array($idDominio)) {
            $sql = 'A.identificador IN(' . implode(',', $idDominio) . ")";
        } else {
            $sql = "A.identificador = $idDominio";
        }

        $sqlFiltro = '';
        if (isset($dadosFiltro['doutoresId'])) {
            $sqlFiltro .= " AND  A.doutores_id = '" . $dadosFiltro['doutoresId'] . "'";
        }
        if (isset($dadosFiltro['pacienteId'])) {
            $sqlFiltro .= " AND  A.pacientes_id = '" . $dadosFiltro['pacienteId'] . "'";
        }
        if (isset($dadosFiltro['consultaId'])) {
            $sqlFiltro .= " AND  A.id = '" . $dadosFiltro['consultaId'] . "'";
        }





        if (isset($dadosFiltro['dataInicio']) and!empty($dadosFiltro['dataInicio'])) {

            $campoDataInicio = null;
            $campoDataFim = null;
            if (isset($dadosFiltro['horaInicio']) and!empty($dadosFiltro['horaInicio'])) {
                $campoDataInicio = " CAST(CONCAT(A.data_consulta,' ',A.hora_consulta) as DATETIME) >= '{$dadosFiltro['dataInicio']} {$dadosFiltro['horaInicio']}'";
            } else {

                if (!empty($dadosFiltro['dataFim'])) {
                    $campoDataInicio = "  A.data_consulta >= '" . $dadosFiltro['dataInicio'] . "'";
                } else {
                    $campoDataInicio = "  A.data_consulta = '" . $dadosFiltro['dataInicio'] . "'";
                }
            }


            if (!empty($dadosFiltro['dataFim'])) {

                if (isset($dadosFiltro['horaFim']) and!empty($dadosFiltro['horaFim'])) {
                    $campoDataFim = " CAST(CONCAT(A.data_consulta,' ',A.hora_consulta) as DATETIME) < '{$dadosFiltro['dataFim']} {$dadosFiltro['horaFim']}'";
                } else {
                    $campoDataFim = "  A.data_consulta <= '" . $dadosFiltro['dataFim'] . "'";
                }

                $sqlFiltro .= " AND  $campoDataInicio AND  $campoDataFim";
            } else {
                $sqlFiltro .= " AND  $campoDataInicio";
            }
        } else
        //Data limite todas as consultas até data informada
        if (isset($dadosFiltro['dataHoraLimite']) and!empty($dadosFiltro['dataHoraLimite'])) {
            $sqlFiltro .= " AND   CAST( CONCAT(A.data_consulta,' ', hora_consulta) AS DATETIME) <= '" . $dadosFiltro['dataHoraLimite'] . "'";
        } else if (isset($dadosFiltro['dataHoraApartirDe']) and!empty($dadosFiltro['dataHoraApartirDe'])) {
            $sqlFiltro .= " AND   CAST( CONCAT(A.data_consulta,' ', hora_consulta) AS DATETIME) >= '" . $dadosFiltro['dataHoraApartirDe'] . "'";
        }

        if (isset($dadosFiltro['hora'])) {
            if (!empty($dadosFiltro['horaFim'])) {
                $sqlFiltro .= " AND  A.hora_consulta >= '" . $dadosFiltro['hora'] . "' AND  A.hora_consulta <= '" . $dadosFiltro['horaFim'] . "'";
            } else {
                $sqlFiltro .= " AND  A.hora_consulta >= '" . $dadosFiltro['hora'] . "'";
            }
        }
//        if (isset($dadosFiltro['horaAtual'])) {
//            if (!empty($dadosFiltro['horaFim'])) {
//                $sqlFiltro .= " AND  A.hora_consulta >= '" . $dadosFiltro['hora'] . "' AND  A.hora_consulta <= '" . $dadosFiltro['horaFim'] . "'";
//            } else {
//                $sqlFiltro .= " AND  A.hora_consulta >= '" . $dadosFiltro['hora'] . "'";
//            }
//        }


        $sqlFiltroStatus = null;

        if (isset($dadosFiltro['statusSomenteAgendado']) and $dadosFiltro['statusSomenteAgendado'] == true) {
            $sqlFiltroStatus = $this->sqlFilterStatusSomenteAgendado();
        } else
        if (isset($dadosFiltro['statusConsulta']) and!empty($dadosFiltro['statusConsulta'])) {
            $sqlFiltroStatus = $this->sqlFilterStatus($dadosFiltro['statusConsulta']);
        }

        $camposAssas= null;
        $joinAssas= null;
        if (isset($dadosFiltro['asaasHabilitado']) and $dadosFiltro['asaasHabilitado'] == 1) {

            $camposAssas = '    ,S.id AS idPacAssasPag,S.link_pagamento AS linkPagAssas,S.link_comprovante AS linkComprovanteAssas,S.valor AS valorAssas, S.status_cobranca AS statusCobrancaAssas,
    S.data_vencimento AS dtVencimentoAssas, S.data_pagamento AS dtPagamentoAssas,S.valor AS valorAssas, T.pl_percentual as plPercentDesconto,
    S.valor_bruto as valorBrutoAssas, T.pl_nome as nomePlBeneficio, T.possui_pendencia as possuiPendencia';
            $joinAssas = 'LEFT JOIN paciente_assas_pagamentos AS S
                            ON (S.consultas_id = A.id AND S.status_cobranca != 0 AND S.status_cobranca != 5)
                            LEFT JOIN pl_beneficios_cons_orc AS T
                            ON (T.tipo =1 AND T.id_tipo = A.id)';
        }




//        dd($sqlFiltroStatus);

        $orderBy = "ORDER BY A.data_consulta ,A.hora_consulta , A.encaixe";
        if (isset($dadosFiltro['orderBy']) and!empty($dadosFiltro['orderBy'])) {
            $orderBy = " ORDER BY {$dadosFiltro['orderBy']} ";
        }

//
//        dd($orderBy);

        if (isset($dadosFiltro['buscaPaciente']) and!empty($dadosFiltro['buscaPaciente'])) {
            $sqlFiltro .= " AND(   CAST( BINARY   AES_DECRYPT(E.nome_cript, '$this->ENC_CODE')  AS CHAR CHARACTER SET utf8) LIKE '%{$dadosFiltro['buscaPaciente']}%' OR
                            CAST(AES_DECRYPT(E.nome_cript, '$this->ENC_CODE') AS CHAR(255)) LIKE '%{$dadosFiltro['buscaPaciente']}%' OR           
                            CAST(AES_DECRYPT(E.sobrenome_cript, '$this->ENC_CODE') AS CHAR(255)) LIKE '%{$dadosFiltro['buscaPaciente']}%' OR
                                  CAST( CONCAT(AES_DECRYPT(E.nome_cript, '$this->ENC_CODE'), ' ', AES_DECRYPT(E.sobrenome_cript, '$this->ENC_CODE')) AS CHAR(255)) like '%{$dadosFiltro['buscaPaciente']}%' OR
                            AES_DECRYPT(E.email_cript, '$this->ENC_CODE') LIKE '%{$dadosFiltro['buscaPaciente']}%' OR
                            AES_DECRYPT(E.cpf_cript, '$this->ENC_CODE') LIKE '%{$dadosFiltro['buscaPaciente']}%' OR
                            E.data_nascimento LIKE '%{$dadosFiltro['buscaPaciente']}%' OR
                            AES_DECRYPT(E.telefone_cript, '$this->ENC_CODE') LIKE '%{$dadosFiltro['buscaPaciente']}%'  )";
        }



        if (isset($dadosFiltro['lastQuery'])) {

            $sqlFiltro .= " AND (FROM_UNIXTIME(A.data_cad_consulta) >= '{$dadosFiltro['lastQuery']}' OR A.data_alter_consulta >='{$dadosFiltro['lastQuery']}')";
        }


        $camposSQL = "A.*,  D.descricao_cid10, D.codigo_cid10, D.tipo AS tipo_cid10, 
            
                                    AES_DECRYPT(E.nome_cript, '$this->ENC_CODE') as  nomePaciente, 
                                         AES_DECRYPT(E.sobrenome_cript, '$this->ENC_CODE') as sobrenomePaciente,
                                         AES_DECRYPT(E.cpf_cript, '$this->ENC_CODE') as cpfPaciente,
                                          AES_DECRYPT(E.cartao_nacional_saude_cript, '$this->ENC_CODE') as cartao_nacional_saude,
                                              AES_DECRYPT(E.telefone_cript, '$this->ENC_CODE') as telefonePaciente,
                                              AES_DECRYPT(E.telefone2_cript, '$this->ENC_CODE') as telefonePaciente2,
                                              AES_DECRYPT(E.celular_cript, '$this->ENC_CODE') as celularPaciente,
                                              E.data_nascimento as dataNascPaciente,
                                              E.sexo as sexoPaciente,
                                    IF(A.pacientes_sem_cadastro_id = '' OR A.pacientes_sem_cadastro_id IS NULL, CONCAT(AES_DECRYPT(E.nome_cript, '$this->ENC_CODE') ,' ', AES_DECRYPT(E.sobrenome_cript, '$this->ENC_CODE') ), F.nome) AS nomePacienteCompleto,
                                     IF(A.administrador_id = '' OR A.administrador_id IS NULL, CAST(CONCAT(AES_DECRYPT(E.nome_cript, '$this->ENC_CODE'),' ',AES_DECRYPT(E.sobrenome_cript, '$this->ENC_CODE') ) as CHAR),G.nome	) AS marcadoPor,
                                      E.envia_email,   E.exibe_sobrenome_impressao, 
                                      AES_DECRYPT(E.email_cript, '$this->ENC_CODE') as  emailPaciente, 
                                     H.numero_carteira, H.validade_carteira, 
                                     /* I.nome as nomePlanoSaude, I.registro_ans, */
                                    AES_DECRYPT(J.nome_cript, '$this->ENC_CODE')  as nomeDoutor, L.nome as nomeConselhoProfissional, J.conselho_uf_id,  AES_DECRYPT(J.conselho_profissional_numero_cript, '$this->ENC_CODE') as conselho_profissional_numero, J.preco_consulta,
                                    J.id as idDoutor, J.cpf as cpfDoutor, J.cnpj as cnpjDoutor,
                                    M.codigo as codigoCbo,M.nome as nomecodigoCbo,
                                    L.codigo as siglaConselhoProfissional,
                                    J.conselho_profissional_id,
                                    J.cbo_s_id,
                                /*     I.tipo_identificacao_xml,
                                    I.codigo_prestador,
                                    I.tipo_dados_contratado,
                                    I.tipo_documento,*/
                                    O.registro_ans,
                                    O.tipo_identificacao_xml,
                                    O.codigo_prestador,
                                    O.tipo_dados_contratado,
                                    O.tipo_documento,
                                     AES_DECRYPT(E.cpf_cript, '$this->ENC_CODE') as cpfPaciente,
                                    
                                    N.ds_uf_sigla as siglaUfConselho,
                                    O.nome as nomeConvenio, TIMEDIFF(hora_consulta_fim,hora_consulta) as qntHorasAtendimento,
                                    P.nome as nomeTipoSanguineo,
                                    Q.nome as nomeFatorRh,
                                    E.envia_sms,
                                     (SELECT CONCAT(status,'_',(hora), '_', id) FROM consultas_status WHERE consulta_id = A.id ORDER BY id DESC LIMIT 1) AS statusConsulta,
                                     (SELECT idRecebimento FROM financeiro_recebimento  WHERE consulta_id = A.id AND status = 1 LIMIT 1) as  idRecebimento,
                                      R.id AS consAtendAbertoId,R.data_cad AS consAtendAbertoDtCad
                                      $camposAssas
                ";
        $from = " 
            FROM consultas AS A
                                   /* LEFT JOIN dias_horarios AS B ON A.dias_horarios_id = B.id
                                    LEFT JOIN horarios AS C ON C.id = B.horarios_id */
                                    LEFT JOIN consultas_cid10 AS D ON D.consulta_id = A.id
                                    INNER JOIN pacientes AS E ON (A.pacientes_id = E.id)
                                    LEFT JOIN pacientes_sem_cadastro AS F ON F.id = A.pacientes_sem_cadastro_id
                                    LEFT JOIN administradores AS G ON(A.administrador_id = G.id)
                                    LEFT JOIN convenios_pacientes as H 
                                    ON (H.pacientes_id    = E.id AND H.convenios_id = A.convenios_id AND A.doutores_id = H.doutores_id  AND H.`status`=1)
                                   /* LEFT JOIN planos_de_saude as I 
                                    ON (I.id = A.plano_saude_id AND A.doutores_id = I.doutores_id)*/
                                    LEFT JOIN doutores as J
                                    ON J.id = A.doutores_id
                                    LEFT JOIN tiss_conselhos_profisionais as L
                                    ON L.id = J.conselho_profissional_id
                                    LEFT JOIN tiss_cbo_s as M
                                    ON M.id = J.cbo_s_id
                                    LEFT JOIN uf as N
                                    ON N.cd_uf = J.conselho_uf_id
                                    LEFT JOIN convenios as O 
                                    ON (O.id = A.convenios_id)
                                    LEFT JOIN tipo_sanguineo as P 
                                    ON (P.id = E.tipo_sanguineo_id)
                                    LEFT JOIN fator_rh as Q 
                                    ON (Q.id = E.fator_rh_id)
                                          LEFT JOIN consultas_atend_abertos AS R
                                    ON R.consultas_id = A.id
                                    $joinAssas
                                    WHERE  $sql $sqlFiltro  $sqlFiltroStatus"
                . " /*GROUP BY A.id*/"
                . " $orderBy ";

//        if (auth('clinicas')->user()->id = 4055) {
//            var_dump("SELECT $camposSQL $from");
//            exit;
//        }


        if ($page == null and $perPage == null) {
            $qr = $this->connClinicas()->select("SELECT $camposSQL $from");
            return $qr;
        } else {
            $qr = $this->paginacao($camposSQL, $from, 'clinicas', $page, $perPage, false);
            return $qr;
        }
    }

    public function getById($idsDominio, $idConsulta, $pacienteId = null) {
        if (is_array($idsDominio)) {
            $sql = 'A.identificador IN(' . implode(',', $idsDominio) . ")";
        } else {
            $sql = "A.identificador = $idsDominio";
        }

        if (!empty($pacienteId)) {
            $sql .= " AND A.pacientes_id = $pacienteId";
        }


        $qrConsulta = $this->connClinicas()->select("SELECT A.*,  D.descricao_cid10, D.codigo_cid10, D.tipo AS tipo_cid10, 
            
                                    AES_DECRYPT(E.nome_cript, '$this->ENC_CODE') as  nomePaciente, 
                                         AES_DECRYPT(E.sobrenome_cript, '$this->ENC_CODE') as sobrenomePaciente,
                                              AES_DECRYPT(E.cartao_nacional_saude_cript, '$this->ENC_CODE') as cartao_nacional_saude,
                                              AES_DECRYPT(E.telefone_cript, '$this->ENC_CODE') as telefonePaciente,
                                              AES_DECRYPT(E.celular_cript, '$this->ENC_CODE') as celularPaciente,
                                             
                                                E.data_nascimento as dataNascPaciente,
                                              E.sexo as sexoPaciente,
                                    IF(A.pacientes_sem_cadastro_id = '' OR A.pacientes_sem_cadastro_id IS NULL, CONCAT(AES_DECRYPT(E.nome_cript, '$this->ENC_CODE') ,' ', AES_DECRYPT(E.sobrenome_cript, '$this->ENC_CODE') ), F.nome) AS nomePacienteCompleto,
                                     IF(A.administrador_id = '' OR A.administrador_id IS NULL, CONCAT(AES_DECRYPT(E.nome_cript, '$this->ENC_CODE'),' ',AES_DECRYPT(E.sobrenome_cript, '$this->ENC_CODE') ),G.nome	) AS marcadoPor,
                                      E.envia_email,   E.exibe_sobrenome_impressao, 
                                      AES_DECRYPT(E.email_cript, '$this->ENC_CODE') as  emailPaciente, 
                                     H.numero_carteira, H.validade_carteira, 
                                     /* I.nome as nomePlanoSaude, I.registro_ans, */
                                    AES_DECRYPT(J.nome_cript, '$this->ENC_CODE')  as nomeDoutor, L.nome as nomeConselhoProfissional, J.conselho_uf_id,  AES_DECRYPT(J.conselho_profissional_numero_cript, '$this->ENC_CODE') as conselho_profissional_numero, J.preco_consulta,
                                    J.id as idDoutor, J.cpf as cpfDoutor, J.cnpj as cnpjDoutor,
                                    M.codigo as codigoCbo,M.nome as nomecodigoCbo,
                                    L.codigo as siglaConselhoProfissional,
                                    J.conselho_profissional_id,
                                    J.cbo_s_id,
                                /*     I.tipo_identificacao_xml,
                                    I.codigo_prestador,
                                    I.tipo_dados_contratado,
                                    I.tipo_documento,*/
                                    O.registro_ans,
                                    O.tipo_identificacao_xml,
                                    O.codigo_prestador,
                                    O.tipo_dados_contratado,
                                    O.tipo_documento,
                                     AES_DECRYPT(E.cpf_cript, '$this->ENC_CODE') as cpfPaciente,
                                    
                                    N.ds_uf_sigla as siglaUfConselho,
                                    O.nome as nomeConvenio, TIMEDIFF(hora_consulta_fim,hora_consulta) as qntHorasAtendimento,
                                    P.nome as nomeTipoSanguineo,
                                    Q.nome as nomeFatorRh,
                                    E.envia_sms,
                                      (SELECT CONCAT(status,'_',(hora), '_', id) FROM consultas_status WHERE consulta_id = A.id ORDER BY id DESC LIMIT 1) AS statusConsulta,
                                       R.id AS consAtendAbertoId,R.data_cad AS consAtendAbertoDtCad,
                                       (SELECT idRecebimento FROM financeiro_recebimento  WHERE consulta_id = A.id AND status = 1 LIMIT 1) as  idRecebimento
                                      
                                    
                                    FROM consultas AS A
                                   /* LEFT JOIN dias_horarios AS B ON A.dias_horarios_id = B.id
                                    LEFT JOIN horarios AS C ON C.id = B.horarios_id */
                                    LEFT JOIN consultas_cid10 AS D ON D.consulta_id = A.id
                                    INNER JOIN pacientes AS E ON (A.pacientes_id = E.id)
                                    LEFT JOIN pacientes_sem_cadastro AS F ON F.id = A.pacientes_sem_cadastro_id
                                    LEFT JOIN administradores AS G ON(A.administrador_id = G.id)
                                    LEFT JOIN convenios_pacientes as H 
                                    ON (H.pacientes_id    = E.id AND H.convenios_id = A.convenios_id)
                                   /* LEFT JOIN planos_de_saude as I 
                                    ON (I.id = A.plano_saude_id AND A.doutores_id = I.doutores_id)*/
                                    LEFT JOIN doutores as J
                                    ON J.id = A.doutores_id
                                    LEFT JOIN tiss_conselhos_profisionais as L
                                    ON L.id = J.conselho_profissional_id
                                    LEFT JOIN tiss_cbo_s as M
                                    ON M.id = J.cbo_s_id
                                    LEFT JOIN uf as N
                                    ON N.cd_uf = J.conselho_uf_id
                                    LEFT JOIN convenios as O 
                                    ON (O.id = A.convenios_id)
                                    LEFT JOIN tipo_sanguineo as P 
                                    ON (P.id = E.tipo_sanguineo_id)
                                    LEFT JOIN fator_rh as Q 
                                    ON (Q.id = E.fator_rh_id)
                                          LEFT JOIN consultas_atend_abertos AS R
                                    ON R.consultas_id = A.id
                                    WHERE $sql AND A.id = $idConsulta
                     
                     ");
        return (count($qrConsulta) > 0) ? $qrConsulta[0] : false;
    }

    public function desmarcarConsulta($idDominio, $idConsulta, $desmarcadoPor, $razaoDesmarcacao = null) {

        $agora = time();
        $qr = $this->connClinicas()->insert("INSERT INTO consultas_status (identificador,consulta_id, status, hora,desmarcado_por,razao_desmarcacao )
            VALUES (?,?,?,?,?,?)", [$idDominio, $idConsulta, 'desmarcado', $agora, $desmarcadoPor, $razaoDesmarcacao]);
        return $qr;
    }

    public function getUltimaConsultaPaciente($idDominio, $idDoutor = null, $idPaciente, $idConsultaExceto = null) {

        $sqlFiltro = null;
        if (!empty($idDoutor)) {
            $sqlFiltro = "  AND A.doutores_id = $idDoutor";
        }

        $agora = time();
        $qr = $this->connClinicas()->select(" SELECT *,
                                                    (
                                                   SELECT status FROM consultas_status AS B WHERE B.consulta_id = A.id  AND B.identificador = A.identificador ORDER BY id DESC LIMIT 1 
                                                   )AS status

                                                    FROM consultas AS A WHERE 
                                                   A.identificador = $idDominio AND A.pacientes_id = $idPaciente
                                                  /* AND A.id != $idConsultaExceto*/
                                                     $sqlFiltro
                                                   AND 
                                                    (
                                                   SELECT status FROM consultas_status AS B WHERE B.consulta_id = A.id  AND B.identificador = A.identificador ORDER BY id DESC LIMIT 1 
                                                   ) = 'jaFoiAtendido'

                                                   ORDER BY A.data_consulta DESC
                                                   LIMIT 1 ");

        if (count($qr) > 0) {
            return $qr[0];
        } else {
            return false;
        }
    }

    public function getConsultasMarcadasHorario($idDominio, $doutores_id, $data, $horario, $verificaConsultaNormal = true) {

        if ($verificaConsultaNormal) {
            $sqlCOnsultaEstendida = "  OR  
                            ( A.hora_consulta_fim != '00:00:00' 
                              AND  (A.hora_consulta_fim > '$horario' AND A.hora_consulta <= '$horario') )";
        } else {
            $sqlCOnsultaEstendida = '';
        }
        $qr = $this->connClinicas()->select("
                    SELECT A.* ,
                       (SELECT status FROM consultas_status WHERE consulta_id = A.id ORDER BY id DESC LIMIT 1) AS statusConsulta
                    FROM consultas as A 
                    INNER JOIN pacientes as B
                    ON B.id = A.pacientes_id
                    WHERE ( A.hora_consulta = '$horario' 
                               $sqlCOnsultaEstendida
                        )  AND
                     A.data_consulta = '$data' AND A.doutores_id = '$doutores_id' AND A.identificador = $idDominio ORDER BY A.id DESC  ");

        return $qr;
    }

    public function insertConsulta($idDominio, Array $dadosInsert) {
        $dadosInsert['identificador'] = $idDominio;
        if (auth('clinicas')->check()) {
            $dadosInsert['administrador_id'] = auth('clinicas')->user()->id;
        }


        if ($this->verificaPrimeiraConsultaPaciente($idDominio, $dadosInsert['pacientes_id']) == 0) {
            $dadosInsert['primeira_consulta'] = 1;
        }
        return $qr = $this->insertDB('consultas', $dadosInsert, null, 'clinicas');
    }

    public function updateConsulta($idDominio, $idConsulta, Array $dadosUpdate) {

        return $qr = $this->updateDB('consultas', $dadosUpdate, " identificador = $idDominio AND id = $idConsulta LIMIT 1 ", null, 'clinicas');
    }

    public function verificaConsultasAnterioresPaciente($idDominio, $idPaciente, $dataConsulta, $idConsulta, $idDoutor = null) {

        $sqlCOnsulta = '';
        if (!empty($idDoutor) and $idDoutor != 'undefined') {
            $sqlCOnsulta .= " AND A.doutores_id = $idDoutor";
        }
        if (!empty($idConsulta) and $idConsulta != 'undefined') {
            $sqlCOnsulta .= " AND A.id != $idConsulta";
        }


        $qr = $this->connClinicas()->select("SELECT  A.*, DATEDIFF ('$dataConsulta', data_consulta) as numero_dias,
                                                        (SELECT status FROM consultas_status as B WHERE B.status ='jaFoiAtendido' AND B.consulta_id = A.id ORDER BY B.id DESC LIMIT 1) as status
                                                        FROM consultas as A 
                                                        WHERE pacientes_id = $idPaciente
                                                        AND data_consulta <= '$dataConsulta'
                                                        AND A.identificador = $idDominio
                                                        AND (A.email_desmarcacao = 0 OR A.email_desmarcacao IS NULL)
                                                        $sqlCOnsulta
                                                        ORDER BY  data_consulta DESC   LIMIT 1");
        return $qr;
    }

    public function verificaPrimeiraConsultaPaciente($idDominio, $idPaciente, $idDoutor = null) {
        $sqlDoutor = '';
        if (!empty($idDoutor)) {
            $sqlDoutor = "  AND A.doutores_id = $idDoutor";
        }
        $qr = $this->connClinicas()->select("SELECT COUNT(*) as total FROM consultas as A WHERE identificador  =$idDominio AND pacientes_id = '$idPaciente' $sqlDoutor");
        return $qr[0]->total;
    }

    public function salvarConsultaProcedimentos($idDominio, $idConsulta, Array $dados) {

        if (isset($dados['idConsultaProc'])) {
//              return $qr = $this->insertDB('consultas_procedimentos', $dados);
        } else {
            $qr = $this->insertDB('consultas_procedimentos', $dados, null, 'clinicas');
            return $qr;
        }
    }

    public function getConsultasProcedimentos($idsDominio, $idConsulta) {
        if (is_array($idsDominio)) {
            $sql = 'A.identificador IN(' . implode(',', $idsDominio) . ")";
        } else {
            $sql = "A.identificador = $idsDominio";
        }
        $qr = $this->connClinicas()->select("SELECT  * FROM consultas_procedimentos AS A WHERE $sql AND A.consultas_id = $idConsulta");
        return $qr;
    }

    public function getDatasMarcadasMensal($idDominio, $doutorId, $mes, $ano) {

        $qr = "SELECT distinct(DATE_FORMAT(data_consulta, '%m/%d/%Y')) as data
                                FROM consultas as A
                                INNER JOIN pacientes as C
                                ON C.id = A.pacientes_id
                                WHERE /* MONTH(data_consulta) = '$mes' AND  YEAR(data_consulta) = '$ano' AND*/ A.identificador = '$idDominio'
                                  AND A.doutores_id = '$doutorId'  AND A.prontuario_adicional IS NULL 
                                ORDER BY A.id DESC";

        $qr = $this->connClinicas()->select($qr);
        return $qr;
    }

    public function delete($idDominio, $consultaId) {

        $qr = "DELETE FROM consultas WHERE identificador = $idDominio AND id = $consultaId LIMIT 1";

        $qr = $this->connClinicas()->select($qr);
        return $qr;
    }

    public function getAllConsultasAgendaPorData($idDominio, $data, $dataFim = null, $dadosFiltro = null, $page = null, $perPage = null) {

        $sqlFiltro = '';
        $sqlData = '';
        $camposConsulta = "consultas.*, DATE_FORMAT(FROM_UNIXTIME(data_agendamento),'%Y-%m-%d %H:%m:%s') as dataCadastroConsulta, pacientes.id as idPaciente,AES_DECRYPT(pacientes.nome_cript, '$this->ENC_CODE')  as nomePaciente,
                        AES_DECRYPT(pacientes.sobrenome_cript, '$this->ENC_CODE')  as sobrenomePaciente,   
                        pacientes.codigo_externo  as codigo_externo,
                        AES_DECRYPT(pacientes.telefone_cript, '$this->ENC_CODE')  as telefone,
                        AES_DECRYPT(pacientes.telefone2_cript, '$this->ENC_CODE')  as telefone2,
                        AES_DECRYPT(pacientes.celular_cript, '$this->ENC_CODE')  as celular,
                        AES_DECRYPT(pacientes.email_cript, '$this->ENC_CODE')  as emailPaciente,
                        conte_a_respeito.opcao as opcaoCOnte,
                        pacientes_sem_cadastro.nome as semCadastroNome,
                        (SELECT idRecebimento FROM financeiro_recebimento  WHERE consulta_id = consultas.id AND status = 1 LIMIT 1) as  idRecebimento,
                        pacientes.alerta,
                        (SELECT id FROM tiss_guia_consulta  WHERE consulta_id = consultas.id  LIMIT 1) as  idTissGuiaConsulta,                                                             
                        financeiro_periodo_repeticao.periodo as periodoRepeticaoNome,
                        convenios.nome as nomeConvenio  ,
                        AES_DECRYPT(doutores.nome_cript, '$this->ENC_CODE') as nomeDoutor,
                        IF( doutores.outra_especialidade != '',doutores.outra_especialidade, especialidades.nome)   AS especialidade,
                        TIMEDIFF(hora_consulta_fim,hora_consulta) as qntHorasAtendimento,
                        doutores.link_appearin,
                        (SELECT CONCAT('{\"status\":\"',STATUS,'\",','\"razao_desmarcacao\":\"',razao_desmarcacao,'\",\"obs_falta\":\"',obs_falta,'\"}') FROM consultas_status WHERE consulta_id =  consultas.id ORDER BY id DESC LIMIT 1 ) AS dadosStatusConsulta,
                        (SELECT status FROM consultas_status WHERE consulta_id =  consultas.id ORDER BY id DESC LIMIT 1 ) AS statusConsulta,
                        E.nome as marcadoPor,
                        E.nome as nomeAdministrador,
                        F.nome as nomeTipoContato,
                        G.nome AS nomeEncaixeAutorizadoPor
                        ";

        if (isset($dadosFiltro['dataAPartirDe']) and!empty($dadosFiltro['dataAPartirDe'])) {
            $sqlData = "AND consultas.data_consulta >= '" . ($dadosFiltro['dataAPartirDe']) . "'";
        } else
        if (
                (isset($dadosFiltro['tipo_data']) and $dadosFiltro['tipo_data'] == 1)
                or!isset($dadosFiltro['tipo_data'])
        ) {
            if (empty($dataFim)) {
                $sqlData = "AND consultas.data_consulta = '" . ($data) . "'";
            } else {
                $sqlData = "AND consultas.data_consulta >='$data' AND consultas.data_consulta <='$dataFim' ";
            }
        } elseif ((isset($dadosFiltro['tipo_data']) and $dadosFiltro['tipo_data'] == 2)) {
            if (empty($dataFim)) {
                $sqlData = "AND DATE_FORMAT(FROM_UNIXTIME(consultas.data_agendamento), '%Y-%m-%d')  = '" . ($data) . "'";
            } else {
                $sqlData = "AND DATE_FORMAT(FROM_UNIXTIME(consultas.data_agendamento), '%Y-%m-%d')  >='$data' AND DATE_FORMAT(FROM_UNIXTIME(consultas.data_agendamento), '%Y-%m-%d')  <='$dataFim' ";
            }
        }
        $sqlData .= (isset($dadosFiltro['doutorId']) and!empty($dadosFiltro['doutorId'])) ? "  AND consultas.doutores_id = $dadosFiltro[doutorId] " : '';

        //filtro status da consulta
        if (isset($dadosFiltro['status_consulta']) and count($dadosFiltro['status_consulta']) > 0) {
            $camposConsulta = "consultas.id,consultas.retorno, consultas.confirmacao, consultas.data_consulta, consultas.hora_consulta,consultas.doutores_id,
                                AES_DECRYPT(pacientes.nome_cript, '$this->ENC_CODE')  as nomePaciente,
                                AES_DECRYPT(pacientes.sobrenome_cript, '$this->ENC_CODE')  as sobrenomePaciente,
                                pacientes.codigo_externo as codigo_externo,
                                AES_DECRYPT(doutores.nome_cript, '$this->ENC_CODE') as nomeDoutor,
                                IF( doutores.outra_especialidade != '',doutores.outra_especialidade, especialidades.nome)   AS especialidade,
                                (SELECT CONCAT('{\"status\":\"',STATUS,'\",','\"razao_desmarcacao\":\"',razao_desmarcacao,'\",\"obs_falta\":\"',obs_falta,'\"}') FROM consultas_status WHERE consulta_id =  consultas.id ORDER BY id DESC LIMIT 1 ) AS dadosStatusConsulta,
                                (SELECT IF(STATUS = 'desmarcado', 
                                IF(desmarcado_por = 1,'desmarcado_paciente','desmarcado_doutor')
                                , STATUS) AS status FROM consultas_status WHERE consulta_id =  consultas.id ORDER BY id DESC LIMIT 1 ) AS statusConsulta,
                                E.nome as marcadoPor,
                                F.nome as nomeTipoContato,consultas.encaixe
                                ";
            $valFiltroStatus = array();
            foreach ($dadosFiltro['status_consulta'] as $statusF) {
                switch ($statusF) {
                    case '': $sqlNaoChegou = "statusConsulta IS NULL";
                        break;
                    default: $valFiltroStatus[] = "'" . $statusF . "'";
                        break;
                }
            }
            if (count($valFiltroStatus) > 0) {
                $sqlFiltroStatus = "WHERE (statusConsulta IN (" . implode(',', $valFiltroStatus) . ") ";
                $sqlFiltroStatus .= (!empty($sqlNaoChegou)) ? ' OR ' . $sqlNaoChegou . ')' : ')';
            } else {
                $sqlFiltroStatus .= (!empty($sqlNaoChegou)) ? "WHERE " . $sqlNaoChegou : '';
            }
        }

        $sqlConfirmacao = '';
        if (isset($dadosFiltro['confirmacao']) and!empty($dadosFiltro['confirmacao'])) {
            switch ($dadosFiltro['confirmacao']) {
                case 'naoConfirmado': $sqlConfirmacao .= "  AND (confirmacao IS  NULL OR confirmacao = 'nao')";
                    break;
                case 'confirmado': $sqlConfirmacao .= " AND  confirmacao IS NOT NULL AND confirmacao != 'nao'";
                    break;
            }
        }
        if (isset($dadosFiltro['status_consulta']) and!empty($dadosFiltro['convenio'])) {
            $sqlData .= ' AND consultas.convenios_id = ' . $dadosFiltro['convenio'];
        }

        if (isset($dadosFiltro['paciente']) and!empty($dadosFiltro['paciente'])) {
            $sqlData .= "AND(  CAST( BINARY   AES_DECRYPT(pacientes.nome_cript, '$this->ENC_CODE')  AS CHAR CHARACTER SET utf8) LIKE '%{$dadosFiltro['paciente']}%' OR
                        CAST(AES_DECRYPT(pacientes.nome_cript, '$this->ENC_CODE') AS CHAR(255)) LIKE '%{$dadosFiltro['paciente']}%' OR           
                        CAST(AES_DECRYPT(pacientes.sobrenome_cript, '$this->ENC_CODE') AS CHAR(255)) LIKE '%{$dadosFiltro['paciente']}%' OR
                        CAST( CONCAT(AES_DECRYPT(pacientes.nome_cript, '$this->ENC_CODE'), ' ', AES_DECRYPT(pacientes.sobrenome_cript, '$this->ENC_CODE')) AS CHAR(255)) like '%{$dadosFiltro['paciente']}%')";
        }
        if (isset($dadosFiltro['usuario']) and!empty($dadosFiltro['usuario'])) {
            $sqlData .= ' AND  consultas.administrador_id  = ' . $dadosFiltro['usuario'];
        }
        if (isset($dadosFiltro['origem']) and!empty($dadosFiltro['origem'])) {
            $sqlData .= ' AND  consultas.tipo_contato_id  = ' . $dadosFiltro['origem'];
        }
        if (isset($dadosFiltro['sexoPaciente']) and!empty($dadosFiltro['sexoPaciente'])) {
            $arraySexo = array('M' => 'Masculino', 'F' => 'Feminino');
            $sqlData .= " AND  pacientes.sexo  = '" . $arraySexo[$dadosFiltro['sexoPaciente']] . "'";
        }

        $joinCategoria = '';
        $joinSubcategoria = '';
        if (isset($dadosFiltro['exibeCategoria']) and $dadosFiltro['exibeCategoria'] == 1) {
            $camposConsulta .= ",H.nome AS nomeCategoriaPaciente";
            $joinCategoria = "LEFT JOIN pacientes_categorias AS H ON H.id = pacientes.categoria_paciente_id";
        }
        if (isset($dadosFiltro['exibeSubcategoria']) and $dadosFiltro['exibeSubcategoria'] == 1) {
            $camposConsulta .= ",I.nome AS nomeSubcategoriaPaciente";
            $joinSubcategoria = "LEFT JOIN pacientes_subcategorias AS I ON I.id = pacientes.subcategoria_paciente_id ";
        }

        if (isset($dadosFiltro['especialidade']) and!empty($dadosFiltro['especialidade'])) {
            $sqlData .= " AND (
                            ( SELECT COUNT(*)FROM doutores_especialidades  
                            LEFT JOIN especialidades
                            ON especialidades.id = doutores_especialidades.especialidade_id
                            WHERE  doutores_especialidades.identificador  = $idDominio
                                  AND doutores_especialidades.doutores_id = consultas.doutores_id
                                      AND (outro = '" . ($dadosFiltro['especialidade']) . "' OR especialidades.nome = '" . ($dadosFiltro['especialidade']) . "')
                           ) >0
                                OR
                                (SELECT COUNT(*)
             /**                   doutores.id, if((doutores.outra_especialidade IS NOT NULL AND doutores.outra_especialidade != ''), doutores.outra_especialidade, especialidades.nome) AS nome**/
                        FROM doutores
                       LEFT JOIN especialidades
                        ON especialidades.id = doutores.especialidades_id
                        WHERE 	doutores.identificador  = $identificador  		AND doutores.id = consultas.doutores_id
                            AND
                        (especialidades.nome ='" . ($dadosFiltro['especialidade']) . "' OR doutores.outra_especialidade = '" . ($dadosFiltro['especialidade']) . "') 						 
									 ) >0
									  
               )";
        }

        if (isset($dadosFiltro['exibeProcedimento']) and $dadosFiltro['exibeProcedimento'] == 1) {

            $camposConsulta .= ",  (SELECT GROUP_CONCAT(nome_proc SEPARATOR '##')  FROM consultas_procedimentos WHERE identificador = consultas.identificador AND consultas_id = consultas.id AND STATUS = 1) AS nomesProcedimentos";
        }

        if (isset($dadosFiltro['procedimento']) and!empty($dadosFiltro['procedimento'])) {

            $sqlFiltro .= " AND        ( SELECT COUNT(*)  FROM consultas_procedimentos WHERE consultas_id = consultas.id
                      AND status = 1 AND CONVERT(CAST(CONVERT(nome_proc USING latin1) AS BINARY) USING utf8mb4) = '{$dadosFiltro['procedimento']}') ";
        }

        $joinTime = '';
//        if (MODULO_AXISMED == 1) {
//
//            $camposConsulta .= ",J.nome AS nomeTime, L.nome as nomeComplexidade,M.id as idPacienteApto";
//            $joinTime = "LEFT JOIN times AS J ON J.id = consultas.times_id 
//                LEFT JOIN grau_complexidade as L ON consultas.grau_complexidade_id = L.id
//                LEFT JOIN axismed_pacs_aptos as M ON M.consultas_id = consultas.id 
//                ";
//            if (isset($dadosFiltro['time']) and!empty($dadosFiltro['time'])) {
//                $sqlData = " AND consultas.times_id = " . $dadosFiltro['time'];
//            }
//        }
//        if ($inicio !== null and $registroPorPagina !== null) {
//            $sqlLimit = "LIMIT $inicio, $registroPorPagina";
//        }
        if (!isset($dadosFiltro['orderBy'])) {
            $ordenacao = 'ORDER BY  data_consulta , hora_consulta ,encaixe,nomeDoutor , nomePaciente ASC';
        } else {
            $ordenacao = 'ORDER BY ' . $dadosFiltro['orderBy'];
        }
        $camposConsulta2 = "*";
        $sqlFiltroStatus = '';
//        if ($tipo_retorno == 3) {
//            $camposConsulta = "consultas.*";
//            $camposConsulta2 = "COUNT(*) as total, data_consulta";
//            $sqlFiltroStatus = $ordenacao = $sqlLimit = '';
//            $sqlFiltroStatus = "GROUP BY data_consulta ORDER BY data_consulta ASC ";
//        }



        $grouByTotalDoutor = null;
        if (isset($filtro['total_por_doutor']) and!empty($filtro['total_por_doutor'])) {
            $camposConsulta2 = "COUNT(*) AS total,nomeDoutor,
                                    SUM( CASE WHEN  retorno = 1  THEN 1 ELSE 0 END
                                   ) AS totalRetornos,doutores_id";
            $ordenacao = null;
            $grouByTotalDoutor = "    GROUP BY nomeDoutor";
        }
//        else {
//            if ($countRegistro) {
//                $camposConsulta2 = "COUNT(*) AS total";
//            }
//        }




        $from = "FROM (
                                        SELECT  $camposConsulta 
                                        FROM	consultas                                                          
                                        INNER JOIN pacientes ON (consultas.pacientes_id = pacientes.id)                                                           
                                        LEFT JOIN conte_a_respeito ON (consultas.conte_a_respeito_id = conte_a_respeito.id)
                                        LEFT JOIN pacientes_sem_cadastro
                                        ON pacientes_sem_cadastro.id = consultas.pacientes_sem_cadastro_id
                                        LEFT JOIN financeiro_periodo_repeticao
                                        ON financeiro_periodo_repeticao.id = consultas.periodo_repeticao_id
                                        LEFT JOIN convenios  
                                        ON (convenios.id = consultas.convenios_id)
                                        INNER JOIN doutores  
                                        ON (doutores.identificador = consultas.identificador AND  doutores.id = consultas.doutores_id)
                                        LEFT JOIN especialidades  
                                        ON (especialidades.id = doutores.especialidades_id)
                                        LEFT JOIN administradores as E
                                        ON E.id = consultas.administrador_id
                                        LEFT JOIN tipo_contato as F
                                        ON F.id = consultas.tipo_contato_id
                                        LEFT JOIN administradores as G
                                        ON G.id = consultas.encaixe_autorizado_por
                                        $joinCategoria
                                        $joinSubcategoria
                                        $joinTime
                                        WHERE  consultas.identificador = '$idDominio' AND consultas.prontuario_adicional IS NULL
                                        $sqlConfirmacao
                                            $sqlFiltro
                                        $sqlData
                                        ) AS query        
                                        $sqlFiltroStatus  
                                            $grouByTotalDoutor
                                                $ordenacao
                                        ";
//        echo '<pre>' . $buscaConsultaNoHorario . '</pre>';
//        dd("SELECT $camposConsulta2 $from");
//
//        $buscaConsultaNoHorario = $this->select($buscaConsultaNoHorario);
        if ($page == null and $perPage == null) {
            $qr = $this->connClinicas()->select("SELECT $camposConsulta2 $from");
            return $qr;
        } else {
            $qr = $this->paginacao($camposConsulta2, $from, 'clinicas', $page, $perPage, false);
            return $qr;
        }
    }

    public function getAlertas($idDominio, $dadosFiltro = null, $page = null, $perPage = null) {

        if (is_array($idDominio)) {
            $sql = 'A.identificador IN(' . implode(',', $idDominio) . ")";
        } else {
            $sql = "A.identificador = $idDominio";
        }
        $orderBy = '';
        $sqlFiltro = ' AND data_consulta = \'' . date('Y-m-d') . '\'';
        $sqlFiltro .= ' AND ( ( (' . $this->sqlFilterStatus('jaFoiAtendido,faltou,desmarcado');
        $sqlFiltro .= ')    AND (SELECT COUNT(*) FROM financeiro_recebimento  WHERE consulta_id = A.id AND status = 1 ) = 0) OR  
              (
              (SELECT COUNT(*) FROM financeiro_recebimento  WHERE consulta_id = A.id AND status = 1 ) > 0)
              AND (SELECT status FROM consultas_status WHERE consulta_id = A.id ORDER BY id DESC LIMIT 1) != \'jaFoiAtendido\'
            )';

        $camposSQL = "A.*,  D.descricao_cid10, D.codigo_cid10, D.tipo AS tipo_cid10, 
            
                                    AES_DECRYPT(E.nome_cript, '$this->ENC_CODE') as  nomePaciente, 
                                         AES_DECRYPT(E.sobrenome_cript, '$this->ENC_CODE') as sobrenomePaciente,
                                         AES_DECRYPT(E.cpf_cript, '$this->ENC_CODE') as cpfPaciente,
                                          AES_DECRYPT(E.cartao_nacional_saude_cript, '$this->ENC_CODE') as cartao_nacional_saude,
                                              AES_DECRYPT(E.telefone_cript, '$this->ENC_CODE') as telefonePaciente,
                                              AES_DECRYPT(E.telefone2_cript, '$this->ENC_CODE') as telefonePaciente2,
                                              AES_DECRYPT(E.celular_cript, '$this->ENC_CODE') as celularPaciente,
                                              E.data_nascimento as dataNascPaciente,
                                              E.sexo as sexoPaciente,
                                    IF(A.pacientes_sem_cadastro_id = '' OR A.pacientes_sem_cadastro_id IS NULL, CONCAT(AES_DECRYPT(E.nome_cript, '$this->ENC_CODE') ,' ', AES_DECRYPT(E.sobrenome_cript, '$this->ENC_CODE') ), F.nome) AS nomePacienteCompleto,
                                     IF(A.administrador_id = '' OR A.administrador_id IS NULL, CAST(CONCAT(AES_DECRYPT(E.nome_cript, '$this->ENC_CODE'),' ',AES_DECRYPT(E.sobrenome_cript, '$this->ENC_CODE') ) as CHAR),G.nome	) AS marcadoPor,
                                      E.envia_email,   E.exibe_sobrenome_impressao, 
                                      AES_DECRYPT(E.email_cript, '$this->ENC_CODE') as  emailPaciente, 
                                     H.numero_carteira, H.validade_carteira, 
                                    AES_DECRYPT(J.nome_cript, '$this->ENC_CODE')  as nomeDoutor, J.conselho_uf_id,  AES_DECRYPT(J.conselho_profissional_numero_cript, '$this->ENC_CODE') as conselho_profissional_numero, J.preco_consulta,
                                    J.id as idDoutor, J.cpf as cpfDoutor, J.cnpj as cnpjDoutor,
                                     AES_DECRYPT(E.cpf_cript, '$this->ENC_CODE') as cpfPaciente,
                                    O.nome as nomeConvenio, TIMEDIFF(hora_consulta_fim,hora_consulta) as qntHorasAtendimento,
                                   
                                     (SELECT CONCAT(status,'_',(hora), '_', id) FROM consultas_status WHERE consulta_id = A.id ORDER BY id DESC LIMIT 1) AS statusConsulta,
                                     (SELECT idRecebimento FROM financeiro_recebimento  WHERE consulta_id = A.id AND status = 1 LIMIT 1) as  idRecebimento
                ";
        $from = " 
            FROM consultas AS A 
            LEFT JOIN consultas_cid10 AS D ON D.consulta_id = A.id
            INNER JOIN pacientes AS E ON (A.pacientes_id = E.id)
            LEFT JOIN pacientes_sem_cadastro AS F ON F.id = A.pacientes_sem_cadastro_id
            LEFT JOIN administradores AS G ON(A.administrador_id = G.id)
            LEFT JOIN convenios_pacientes as H 
            ON (H.pacientes_id    = E.id AND H.convenios_id = A.convenios_id AND A.doutores_id = H.doutores_id  AND H.`status`=1)
            LEFT JOIN doutores as J
            ON J.id = A.doutores_id
            LEFT JOIN convenios as O 
            ON (O.id = A.convenios_id)  
            WHERE  $sql $sqlFiltro   "
                . "/*GROUP BY A.id*/ "
                . "$orderBy ";

//        if (auth('clinicas')->user()->id = 4055) {
//        var_dump("SELECT $camposSQL $from");
//            exit;
//        }


        if ($page == null and $perPage == null) {
            $qr = $this->connClinicas()->select("SELECT $camposSQL $from");
            return $qr;
        } else {
            $qr = $this->paginacao($camposSQL, $from, 'clinicas', $page, $perPage, false);
            return $qr;
        }
    }

}
