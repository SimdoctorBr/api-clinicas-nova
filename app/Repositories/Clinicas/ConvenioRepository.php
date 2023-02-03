<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Repositories\Clinicas;

use App\Repositories\BaseRepository;

/**
 * Description of ConvenioRepository
 *
 * @author ander
 */
class ConvenioRepository extends BaseRepository {

    public function getAll($idDominio) {

        if (is_array($idDominio)) {
            $sql = 'A.identificador IN(' . implode(',', $idDominio) . ")";
        } else {
            $sql = "A.identificador = $idDominio";
        }

        $qr = $this->connClinicas()->select("SELECT *, A.id as convenios_id,
            (CONVERT(CAST(CONVERT(   A.nome USING latin1) AS BINARY) USING UTF8))           as nomeConvenio FROM convenios as A WHERE  A.`status` = 1 AND  ($sql OR A.id = 41)");
        return $qr;
    }

    public function getById($idDominio, $idConvenio) {

        if (is_array($idDominio)) {
            $sql = 'A.identificador IN(' . implode(',', $idDominio) . ")";
        } else {
            $sql = "A.identificador = $idDominio";
        }

        $qr = $this->connClinicas()->select("SELECT *, A.id as convenios_id,   (CONVERT(CAST(CONVERT(   A.nome USING latin1) AS BINARY) USING UTF8)) as nomeConvenio FROM convenios as A WHERE  A.`status` = 1 AND $sql AND A.id =$idConvenio");
        return $qr;
    }

    public function getConveniosPacientes($idDominio, $idPaciente, $doutorId = null) {
        $sql = '';
        if (!empty($doutorId)) {
            $sql = "AND A.doutores_id = $doutorId";
        }


        $qr = $this->connClinicas()->select("SELECT A.*,  (CONVERT(CAST(CONVERT(   B.nome USING latin1) AS BINARY) USING UTF8))AS nomeConvenio,AES_DECRYPT(C.nome_cript, '$this->ENC_CODE') as nomeDoutor
                                    FROM convenios_pacientes AS A
                                    LEFT JOIN convenios AS B ON A.convenios_id = B.id
                                    LEFT JOIN doutores as C
                                    ON C.id = A.doutores_id
                                    WHERE A.identificador = '$idDominio' AND pacientes_id = '$idPaciente' AND A.status = 1
                                       AND 
                                        (
                                        (A.doutores_id IS NOT NULL AND C.status_doutor = 1)
                                        OR 
                                        A.todos = 1
                                        )
                                        $sql
                                        ;");
        return $qr;
    }

    public function vinculaConveniosPacientes($idDominio, $idPaciente, $conveniosId, $numeroCarteira, $validadeCarteira, $doutorId = null, $todos = null) {


        $campos['identificador'] = $idDominio;
        $campos['pacientes_id'] = $idPaciente;
        $campos['numero_carteira'] = $numeroCarteira;
        $campos['validade_carteira'] = $validadeCarteira;
        $campos['convenios_id'] = $conveniosId;
        if ($todos == 1) {
            $campos['todos'] = 1;
        } else {
            $campos['doutores_id'] = $doutorId;
        }
        $campos['status'] = 1;
        return $this->insertDB('convenios_pacientes', $campos, null, 'clinicas');
    }

    public function updateAllConveniosPacientesByConvenioId($idDominio, $pacientes_id, $convenios_id, $numeroCateira, $validadeCarteira) {

        return $this->updateDB('convenios_pacientes', [
                    'numero_carteira' => $numeroCateira,
                    'validade_carteira' => $validadeCarteira,
                        ], " identificador =$idDominio  AND pacientes_id = $pacientes_id AND convenios_id = $convenios_id AND status = 1", null, 'clinicas');
    }

    public function verificaExisteConveniosPacientesTodos($identificador, $pacientes_id, $convenios_id, $status = 1) {
        $qr = $this->connClinicas()->select("SELECT * FROM convenios_pacientes WHERE identificador = $identificador  AND  pacientes_id = $pacientes_id  AND convenios_id = $convenios_id AND status = $status  ");
        return $qr;
    }

    public function verificaExisteConveniosPacientes($identificador, $id_doutor, $pacientes_id, $convenios_id = null) {
        $sql = '';
        if (!empty($convenios_id)) {
            $sql = " AND convenios_id = $convenios_id";
        }

        $qr = $this->connClinicas()->select("SELECT * FROM convenios_pacientes WHERE identificador = $identificador AND doutores_id = $id_doutor AND  pacientes_id = $pacientes_id $sql");
        return $qr;
    }

    /**
     * 
     * @param type $identificador
     * @param array $dadosConveniosPac
     *  dados:
     * $campos['identificador'] 
      $campos['pacientes_id']
      $campos['numero_carteira']
      $campos['validade_carteira']
      $campos['convenios_id']
      $campos['doutores_id']
      $campos['status'] = 1;
     * 
     * @return type
     */
    public function conveniosPacientesInsert($idDominio, Array $dadosConveniosPac) {


        $qrIsExist = $this->verificaExisteConveniosPacientes($idDominio, $dadosConveniosPac['doutores_id'], $dadosConveniosPac['pacientes_id']);
        if (count($qrIsExist) == 0) {


            $campos['identificador'] = $idDominio;
            $campos['pacientes_id'] = $dadosConveniosPac['pacientes_id'];
            $campos['numero_carteira'] = $dadosConveniosPac['numero_carteira'];
            $campos['validade_carteira'] = (!empty($dadosConveniosPac['validade_carteira'])) ? $dadosConveniosPac['validade_carteira'] : null;
            $campos['convenios_id'] = $dadosConveniosPac['convenios_id'];
            $campos['doutores_id'] = $dadosConveniosPac['doutores_id'];

            return $this->insertDB('convenios_pacientes', $campos, null, 'clinicas');
        } else {
            $campos['numero_carteira'] = $dadosConveniosPac['numero_carteira'];
            $campos['status'] = 1;
            $this->updateDB('convenios_pacientes', $campos, " identificador = $idDominio AND id =  {$qrIsExist[0]->id} LIMIT 1", null, 'clinicas');
            return $qrIsExist[0]->id;
        }
    }

    /**
     * Convenios que o doutor aceita
     * 
     * @param type $identificador
     * @param type $idDoutor
     */
    public function getAllConveniosDoutores($idDominio, $idDoutor, $dadosFiltro = null) {

        $sqlSomenteProc = null;
        if (isset($dadosFiltro['somente_com_procedimento']) and $dadosFiltro['somente_com_procedimento'] == 1) {

            $exibeAppNerofor = (isset($dadosFiltro['exibeAppNerofor']) and $dadosFiltro['exibeAppNerofor'] == 1) ? 'AND Cc.exibir_app_docbizz = 1' : '';
            $sqlSomenteProc = " AND (SELECT COUNT(*)
                                FROM procedimentos_doutores_assoc AS Aa
                               INNER JOIN doutores AS Bb
                               ON Aa.doutores_id = Bb.id
                               INNER JOIN procedimentos AS Cc
                               ON Cc.idProcedimento = Aa.procedimentos_id
                               WHERE Aa.identificador = $idDominio AND Aa.doutores_id = $idDoutor
                                AND Aa.proc_convenios_id=A.convenios_id
                                 $exibeAppNerofor
                               AND Aa.status = 1 AND Cc.`status` = 1 ) >0";
        }


        $qrCat = $this->connClinicas()->select("SELECT A.*, (CONVERT(CAST(CONVERT(   B.nome USING latin1) AS BINARY) USING UTF8)) as nomeConvenio, C.nome as nomeTipoCodigo FROM convenios_doutores as A
                                INNER JOIN convenios as B
                                ON A.convenios_id = B.id
                                LEFT JOIN convenios_tipo_codigo_operadora as C
                                ON C.id = A.convenios_tipo_codigo_operadora_id
                                 WHERE A.identificador = '$idDominio' AND doutores_id = '$idDoutor' AND A.status = 1
                                     $sqlSomenteProc
                                     ;");
        return $qrCat;
    }

    public function desvinculaConveniosPacientes($idDominio, $pacientes_id, $conveniosId) {

        return $this->connClinicas()->select("DELETE FROM convenios_pacientes WHERE identificador =$idDominio  AND pacientes_id = $pacientes_id AND convenios_id = $conveniosId AND status = 1");
    }

}
