<?php

namespace App\Repositories\Clinicas\Paciente;

use Illuminate\Support\Facades\DB;
use App\Repositories\BaseRepository;
use App\Helpers\Functions;

class PacienteRepository extends BaseRepository {

    protected $camposEncriptados = array('nome_cript', 'sobrenome_cript', 'email_cript', 'cpf_cript', 'rg_cript', 'nome_conjuge_cript', 'cartao_nacional_saude_cript', 'telefone_cript'
        , 'telefone2_cript', 'celular_cript', 'email_cript', 'filiacao_pai', 'filiacao_mae');

    public function getUltimaMatricula($idDominio) {
        $qr = $this->connClinicas()->select("SELECT if(MAX(matricula) IS NULL, 1, MAX(matricula)+1) as sugestaoMatricula FROM pacientes WHERE identificador = $idDominio");
        if (count($qr) > 0) {

            return $qr[0]->sugestaoMatricula;
        } else {
            return 1;
        }
    }

    public function getAll($idDominio, $dadosFiltro = null, $inicioReg = null, $limit = null) {

        $sqlFiltro = array();
        $sqlFiltro2 = '';
        $camposAdicionaisSQL = '';
        $orderBy = "A.id ASC";

        $arrayExcptFields = ['nome_cript', 'sobrenome_cript', 'cpf_cript', 'rg_cript', 'nome_conjuge_cript', 'cartao_nacional_saude_cript', 'telefone_cript',
            'telefone2_cript', 'celular_cript', 'email_cript', 'cpf', 'nome', 'sobrenome', 'email', 'celular', 'telefone', 'telefone2'];
        $qrTabelFields = $this->connClinicas()->select("SHOW COLUMNS FROM pacientes");
        $fieldsTable = array_filter(array_map(function ($item) use ($arrayExcptFields) {
                    if (!in_array($item->Field, $arrayExcptFields)) {
                        return 'A.' . $item->Field;
                    }
                }, $qrTabelFields));
        $fieldsTable = implode(',', $fieldsTable);

//        dd($fieldsTable);

        if (isset($dadosFiltro['nome']) and!empty($dadosFiltro['nome'])) {
            $sqlFiltro[] = "   (CAST( BINARY   AES_DECRYPT(nome_cript, '$this->ENC_CODE')  AS CHAR CHARACTER SET utf8) LIKE '" . $dadosFiltro['nome'] . "%' OR
                            CAST(AES_DECRYPT(nome_cript, '$this->ENC_CODE') AS CHAR(255)) LIKE '" . $dadosFiltro['nome'] . "%' )";
        }


        if (isset($dadosFiltro['sobrenome']) and!empty($dadosFiltro['sobrenome'])) {
            $sqlFiltro[] = "       CAST(AES_DECRYPT(sobrenome_cript, '$this->ENC_CODE') AS CHAR(255)) LIKE '" . $dadosFiltro['sobrenome'] . "%'  ";
        }

        if (isset($dadosFiltro['search']) and!empty($dadosFiltro['search'])) {

            $searchTxt = explode(' ', trim($dadosFiltro['search']));
            $searchSobrenome = $searchTxt;
            $searchSobrenome = implode(' ', array_splice($searchSobrenome, 1));

            $orderBy = "  prioridadeBusca,nome,sobrenome ASC";

            $camposAdicionaisSQL = ", 
                IF(CAST( CONCAT(AES_DECRYPT(nome_cript,
                        '$this->ENC_CODE'),
                        ' ', AES_DECRYPT(sobrenome_cript,
                        '$this->ENC_CODE'))
                        AS CHAR(255)) like '" . trim($dadosFiltro['search']) . "%',-1, 
                IF(
                ((CAST( BINARY   AES_DECRYPT(nome_cript, '$this->ENC_CODE')  AS CHAR CHARACTER SET utf8) LIKE '" . $searchTxt[0] . "%' OR
                       CAST(AES_DECRYPT(nome_cript, '$this->ENC_CODE') AS CHAR(255)) LIKE '" . $searchTxt[0] . "%')) AND
                        ( CAST(AES_DECRYPT(sobrenome_cript, '$this->ENC_CODE') AS CHAR(255)) LIKE '%{$searchSobrenome}%')   
                           ,
                       0,IF(
                       (CAST( BINARY   AES_DECRYPT(nome_cript, '$this->ENC_CODE')  AS CHAR CHARACTER SET utf8) LIKE '" . $searchTxt[0] . "%' OR
                       CAST(AES_DECRYPT(nome_cript, '$this->ENC_CODE') AS CHAR(255)) LIKE '" . $searchTxt[0] . "%'),1,2)
		  )) AS prioridadeBusca
		  ";
            if (count($searchTxt) > 1) {
                $sqlFiltroPac = null;
                foreach ($searchTxt as $textS) {

                    $sqlFiltroPac[] = "   (
                        CAST( CONCAT(AES_DECRYPT(nome_cript, '$this->ENC_CODE'), ' ', AES_DECRYPT(sobrenome_cript, '$this->ENC_CODE')) AS CHAR(255)) like '{$textS}%') OR    
                        TRIM(REPLACE(REPLACE( AES_DECRYPT(cpf_cript, '$this->ENC_CODE') , '-', ''), '.',''))  LIKE '%" . $textS . "%'
                        ";
                }
                $sqlFiltro[] = "(" . implode('OR', $sqlFiltroPac) . ")";

//                var_dump($sqlFiltro);
            } else {
                $sqlFiltro[] .= "   (CAST( BINARY   AES_DECRYPT(nome_cript, '$this->ENC_CODE')  AS CHAR CHARACTER SET utf8) LIKE '%{$dadosFiltro['search']}%' OR
                       CAST(AES_DECRYPT(nome_cript, '$this->ENC_CODE') AS CHAR(255)) LIKE '%{$dadosFiltro['search']}%' OR           
                        CAST(AES_DECRYPT(sobrenome_cript, '$this->ENC_CODE') AS CHAR(255)) LIKE '%{$dadosFiltro['search']}%') OR   
                        TRIM(REPLACE(REPLACE( AES_DECRYPT(cpf_cript, '$this->ENC_CODE') , '-', ''), '.',''))  LIKE '%" . $dadosFiltro['search'] . "%' OR   
                        TRIM( AES_DECRYPT(cpf_cript, '$this->ENC_CODE'))  LIKE '%" . $dadosFiltro['search'] . "%'
                        ";
            }
        }

//        dd($dadosFiltro)];
        if (isset($dadosFiltro['id']) and!empty($dadosFiltro['id'])) {
            $sqlFiltro2 = "AND A.id =  '" . $dadosFiltro['id'] . "'";
        }
        if (isset($dadosFiltro['sexo']) and!empty($dadosFiltro['sexo'])) {
            $sqlFiltro2 .= "AND A.sexo =  '" . $dadosFiltro['sexo'] . "'";
        }
        if (isset($dadosFiltro['cpf']) and!empty($dadosFiltro['cpf'])) {
            $sqlFiltro2 .= " AND REPLACE(REPLACE(
                            CAST( BINARY AES_DECRYPT(cpf_cript, '$this->ENC_CODE')  AS CHAR CHARACTER SET UTF8),
                            '-',''), '.','') ='" . $dadosFiltro['cpf'] . "'";
        }
        if (isset($dadosFiltro['celular']) and!empty($dadosFiltro['celular'])) {
            $sqlFiltro2 .= " AND        REPLACE(  REPLACE(	  REPLACE(	  REPLACE(REPLACE(
                            CAST( BINARY AES_DECRYPT(celular_cript, '$this->ENC_CODE')  AS CHAR CHARACTER SET UTF8),
                            '-',''), '.','') 
                            , '(','')
                            , ')','')
                            , ' ','')  = '" . $dadosFiltro['celular'] . "'";
        }
        if (isset($dadosFiltro['email']) and!empty($dadosFiltro['email'])) {
            $sqlFiltro2 .= " AND     CAST( BINARY AES_DECRYPT(email_cript, '$this->ENC_CODE')  AS CHAR CHARACTER SET UTF8)= '" . $dadosFiltro['email'] . "'";
        }

        if (isset($dadosFiltro['data_nascimento']) and!empty($dadosFiltro['data_nascimento'])) {
            $sqlFiltro2 .= " AND    STR_TO_DATE(A.data_nascimento,'%d/%m/%Y')= '" . $dadosFiltro['data_nascimento'] . "'";
        }


        if (isset($dadosFiltro['data_nascimento_min']) and!empty($dadosFiltro['data_nascimento_min'])) {

            $filtroDataNasc = " AND     A.data_nascimento= '" . Functions::dateDbToBr($dadosFiltro['data_nascimento_min']) . "'";
            if (isset($dadosFiltro['data_nascimento_max']) and!empty($dadosFiltro['data_nascimento_max'])) {
                $filtroDataNasc = " AND     (STR_TO_DATE(A.data_nascimento,'%d/%m/%Y')>= '" . $dadosFiltro['data_nascimento_min'] . "'  AND STR_TO_DATE(A.data_nascimento,'%d/%m/%Y')<= '" . $dadosFiltro['data_nascimento_max'] . "')";
            }
            $sqlFiltro2 .= $filtroDataNasc;
        }

        if (isset($dadosFiltro['ultima_alteracao']) and!empty($dadosFiltro['ultima_alteracao'])) {
            $sqlFiltro2 .= " AND    ( A.data_cad_pac >= '" . $dadosFiltro['ultima_alteracao'] . "' OR  A.data_alter_pac >= '" . $dadosFiltro['ultima_alteracao'] . "')";
        }

        $sqlFiltro = (count($sqlFiltro) > 0) ? "AND (" . implode(' OR ', $sqlFiltro) . " )" : "";

        if (is_array($idDominio)) {
            $sql = 'A.identificador IN(' . implode(',', $idDominio) . ")";
        } else {
            $sql = "A.identificador = $idDominio";
        }




        $camposSQl = "$fieldsTable,AES_DECRYPT(nome_cript, '$this->ENC_CODE') as nome,
        AES_DECRYPT(sobrenome_cript, '$this->ENC_CODE') as sobrenome,
        AES_DECRYPT(cartao_nacional_saude_cript, '$this->ENC_CODE') as cartao_nacional_saude,
        AES_DECRYPT(telefone_cript, '$this->ENC_CODE') as telefone,
        AES_DECRYPT(telefone2_cript, '$this->ENC_CODE') as telefone2,
        AES_DECRYPT(celular_cript, '$this->ENC_CODE') as celular,
        AES_DECRYPT(email_cript, '$this->ENC_CODE') as email,
        AES_DECRYPT(cpf_cript, '$this->ENC_CODE') as cpf,
        AES_DECRYPT(rg_cript, '$this->ENC_CODE') as rg,
        AES_DECRYPT(nome_conjuge_cript, '$this->ENC_CODE') as nome_conjuge" . $camposAdicionaisSQL
                . " , B.ds_uf_sigla as ufSigla, C.customer_id as idCustomerAsaas";

        $from = "FROM pacientes as A 
               LEFT JOIN uf as B
            ON A.pac_uf_id = B.cd_uf
            LEFT JOIN pacientes_assas_clientes as C
            ON C.pacientes_id = A.id
            WHERE $sql AND A.status_paciente = 1
                $sqlFiltro2
               $sqlFiltro
                 ";

        if (!empty($inicioReg) and!empty($limit)) {
//            var_dump("SELECT $camposSQl $from ORDER BY $orderBy");
            $qr = $this->paginacao($camposSQl, $from, 'clinicas', $inicioReg, $limit, false, $orderBy);
        } else {
            $qr = $this->connClinicas()->select("SELECT $camposSQl $from   ORDER BY $orderBy ");
        }


        return $qr;
    }

    public function insert($idDominio, $dados) {

        $dados['identificador'] = $idDominio;
        $qr = $this->insertDB('pacientes', $dados, $this->camposEncriptados, 'clinicas');
        $this->insertTotalPacientesPerfil($idDominio);
        return $qr;
    }

    public function update($idDominio, $idPaciente, $dados) {
        if (is_array($idDominio)) {
            $sqlFiltro = '  identificador IN(' . implode(',', $idDominio) . ")";
        } else {
            $sqlFiltro = " identificador = $idDominio";
        }
        $dados['data_alter_pac'] = date('Y-m-d H:i:s');

        $qr = $this->updateDB('pacientes', $dados, " $sqlFiltro AND id = $idPaciente LIMIT 1", $this->camposEncriptados, 'clinicas');
        return $qr;
    }

    public function getById($idDominio, $pacienteId, $verify = false) {
        if (is_array($idDominio)) {
            $sqlFiltro = '  identificador IN(' . implode(',', $idDominio) . ")";
        } else {
            $sqlFiltro = " identificador = '$idDominio'";
        }


        if ($verify) {
            $camposSql = "id";
        } else {
            $camposSql = "*,AES_DECRYPT(nome_cript, '$this->ENC_CODE') as nome,
        AES_DECRYPT(sobrenome_cript, '$this->ENC_CODE') as sobrenome,
        AES_DECRYPT(cartao_nacional_saude_cript, '$this->ENC_CODE') as cartao_nacional_saude,
        AES_DECRYPT(telefone_cript, '$this->ENC_CODE') as telefone,
        AES_DECRYPT(telefone2_cript, '$this->ENC_CODE') as telefone2,
        AES_DECRYPT(celular_cript, '$this->ENC_CODE') as celular,
        AES_DECRYPT(email_cript, '$this->ENC_CODE') as email,
        AES_DECRYPT(cpf_cript, '$this->ENC_CODE') as cpf,
        AES_DECRYPT(rg_cript, '$this->ENC_CODE') as rg,
        AES_DECRYPT(nome_conjuge_cript, '$this->ENC_CODE') as nome_conjuge";
        }
        $qr = $this->connClinicas()->select("SELECT $camposSql
            FROM pacientes WHERE $sqlFiltro AND id  = $pacienteId ");
        return $qr;
    }

    public function loginGoogle($idDominio, $codigo) {
        if (is_array($idDominio)) {
            $sqlFiltro = '  identificador IN(' . implode(',', $idDominio) . ")";
        } else {
            $sqlFiltro = " identificador = $idDominio";
        }
        $qr = $this->connClinicas()->select("SELECT id,identificador,AES_DECRYPT(nome_cript, '$this->ENC_CODE') as nome,
                                                    AES_DECRYPT(sobrenome_cript, '$this->ENC_CODE') as sobrenome,
                                                    AES_DECRYPT(email_cript, '$this->ENC_CODE') as email,id_google,foto_google,link_google
                                                    FROM pacientes WHERE $sqlFiltro AND id_google = $codigo");

        if (count($qr) > 0) {
            return $qr[0];
        } else {
            return false;
        }
    }

    public function loginFacebook($idDominio, $codigo) {
        if (is_array($idDominio)) {
            $sqlFiltro = '  identificador IN(' . implode(',', $idDominio) . ")";
        } else {
            $sqlFiltro = " identificador = $idDominio";
        }
        $qr = $this->connClinicas()->select("SELECT id,identificador,AES_DECRYPT(nome_cript, '$this->ENC_CODE') as nome,
                                                    AES_DECRYPT(sobrenome_cript, '$this->ENC_CODE') as sobrenome,
                                                    AES_DECRYPT(email_cript, '$this->ENC_CODE') as email,id_facebook,foto_facebook,link_facebook
                                                    FROM pacientes WHERE $sqlFiltro AND id_facebook = $codigo");

        if (count($qr) > 0) {
            return $qr[0];
        } else {
            return false;
        }
    }

    public function verificaPacienteExisteTuotempo($idDominio, $nome, $sobrenome, $telefone, $celular, $cpf, $dataNascimento) {


        $qr = $this->connClinicas()->select("SELECT *,AES_DECRYPT(nome_cript,'$this->ENC_CODE')as nome
                FROM pacientes AS A WHERE identificador =$idDominio
                AND
                A.status_paciente = 1
                AND
                (  
                (CAST( BINARY AES_DECRYPT(nome_cript,'$this->ENC_CODE')AS CHAR CHARACTER SET UTF8)) = '$nome' 
                    AND (CAST( BINARY AES_DECRYPT(sobrenome_cript, '$this->ENC_CODE') AS CHAR CHARACTER SET UTF8)) = '$sobrenome'
                )
                AND
                (
                (data_nascimento = '$dataNascimento' AND CAST( BINARY AES_DECRYPT(telefone_cript,'$this->ENC_CODE') AS CHAR CHARACTER SET utf8) ='$telefone') OR

                ( AES_DECRYPT(celular_cript,'$this->ENC_CODE') = '$celular' AND data_nascimento = '$dataNascimento') OR
                ( AES_DECRYPT(cpf_cript,'$this->ENC_CODE')= '$cpf' AND AES_DECRYPT(celular_cript,'$this->ENC_CODE') = '$celular'

                )
                )");
        return $qr;
    }

    public function login($idDominio, $email, $senha, $tokenBio = null) {

        if (is_array($idDominio)) {
            $sql = 'identificador IN(' . implode(',', $idDominio) . ")";
        } else {
            $sql = "identificador = $idDominio";
        }
        if (!empty($tokenBio)) {
            $sql .= " AND auth_token_biometria = '$tokenBio'";
        } else {
            $sql .= " AND AES_DECRYPT(email_cript,'$this->ENC_CODE') = '$email' AND senha = '$senha'";
        }

        $qr = $this->connClinicas()->select("SELECT *,
            AES_DECRYPT(nome_cript, '$this->ENC_CODE') as nome,
        AES_DECRYPT(sobrenome_cript, '$this->ENC_CODE') as sobrenome,
        AES_DECRYPT(cartao_nacional_saude_cript, '$this->ENC_CODE') as cartao_nacional_saude,
        AES_DECRYPT(telefone_cript, '$this->ENC_CODE') as telefone,
        AES_DECRYPT(telefone2_cript, '$this->ENC_CODE') as telefone2,
        AES_DECRYPT(celular_cript, '$this->ENC_CODE') as celular,
        AES_DECRYPT(email_cript, '$this->ENC_CODE') as email,
        AES_DECRYPT(cpf_cript, '$this->ENC_CODE') as cpf,
        AES_DECRYPT(rg_cript, '$this->ENC_CODE') as rg,
        AES_DECRYPT(nome_conjuge_cript, '$this->ENC_CODE') as nome_conjuge
            FROM pacientes WHERE $sql ");
        if (count($qr) > 0) {
            return $qr;
        } else {
            return false;
        }
    }

    public function verificaSenha($idDominio, $idPaciente, $senha) {


        if (is_array($idDominio)) {
            $sqlFiltro = '  identificador IN(' . implode(',', $idDominio) . ")";
        } else {
            $sqlFiltro = " identificador = $idDominio";
        }

        $qr = $this->connClinicas()->select("SELECT * FROM pacientes WHERE $sqlFiltro AND id = $idPaciente AND senha = '$senha' ");
        if (count($qr) > 0) {
            return $qr[0];
        } else {
            return false;
        }
    }

    public function buscaPorEmail($idDominio = null, $email, $codigo = null) {
        $sqlFiltro = '';

        if (!empty($idDominio)) {
            if (is_array($idDominio)) {
                $sqlFiltro = 'AND  identificador IN(' . implode(',', $idDominio) . ")";
            } else {
                $sqlFiltro = "AND identificador = $idDominio";
            }
        }
        if (!empty($codigo)) {
            $sqlFiltro .= " AND cod_troca_senha = $codigo  AND  cod_senha_validade >= '" . date('Y-m-d H:i:s') . "' ";
        }


        $qr = $this->connClinicas()->select("SELECT *,AES_DECRYPT(nome_cript, '$this->ENC_CODE') as nome,
        AES_DECRYPT(sobrenome_cript, '$this->ENC_CODE') as sobrenome,
        AES_DECRYPT(email_cript, '$this->ENC_CODE') as email
            FROM pacientes WHERE   AES_DECRYPT(email_cript, '$this->ENC_CODE')  = '$email' $sqlFiltro");
        if (count($qr) > 0) {
            return $qr[0];
        } else {
            return false;
        }
    }

    /**
     * Voncular o paciente do Assas 
     * @param type $identificador
     * @param type $idPaciente
     * @param type $customerId
     */
    public function vincularPacienteAssas($idDominio, $idPaciente, $customerId) {

        try {
            $campos['identificador'] = $idDominio;
            $campos['pacientes_id'] = $idPaciente;
            $campos['customer_id'] = $customerId;
            $qr = $this->insertDB('pacientes_assas_clientes', $campos, null, 'clinicas');
            return true;
        } catch (Exception $ex) {
            return $ex;
        }
    }

    public function getTotalPacientes($idDominio) {
        $qr = $this->connClinicas()->select("SELECT COUNT(*) as total FROM pacientes WHERE status_paciente = 1 AND identificador = '$idDominio'  ORDER BY nome ASC");
        $row = $qr[0];
        return $row->total;
    }

    public function insertTotalPacientesPerfil($idDominio) {
        $totalPac = $this->getTotalPacientes($idDominio);
     
        $campos['total_pacientes_cad'] = $totalPac;
        $qr = $this->updateDB('dominios', $campos, " id = $idDominio LIMIT 1", null, 'gerenciamento');
    }

//    public function getPacienteFotos($idPaciente, $idConsulta = null) {
//        
//        if (!empty($idConsulta) and is_numeric($idConsulta)) {
//            $sqlFiltro .= " AND consultas_id = '$idConsulta' ";
//        }
//
//        if (isset($dadosFiltro['data_filtro']) and!empty($dadosFiltro['data_filtro'])) {
//            if (isset($dadosFiltro['data_periodo'])) {
//                $sqlFiltro .= " AND DATE_FORMAT(data_cad, '%Y-%m-%d') >='" . ($dadosFiltro['data_filtro']) . "' AND DATE_FORMAT(data_cad, '%Y-%m-%d')<='" . ($dadosFiltro['data_filtro_ate']) . "'";
//            } else {
//                $sqlFiltro .= " AND DATE_FORMAT(data_cad, '%Y-%m-%d') ='" . ($dadosFiltro['data_filtro']) . "'";
//            }
//        }
//
//
//        $qr = $this->connClinicas()->select("SELECT * FROM pacientes_fotos  WHERE pacientes_id = '$idPaciente' $sqlFiltro ORDER BY data_cad DESC $sqlLimit");
//        return $qr;
//    }
}
