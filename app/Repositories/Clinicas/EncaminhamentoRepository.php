<?php

namespace App\Repositories\Clinicas;

use Illuminate\Support\Facades\DB;
use App\Repositories\BaseRepository;

class EncaminhamentoRepository extends BaseRepository {

    private function geraCodigo($idDominio) {
        $qr = $this->connClinicas()->select("SELECT MAX(codigo) as max FROM consultas_guia_encaminhamento WHERE identificador = $idDominio ");
        if (count($qr) > 0) {
            return $qr[0]->max + 1;
        } else {
            return 1;
        }
    }

    private function verificaItemProcedimentoId($idDominio, $idEncaminhamento, $procedimentoId) {
        $qr = $this->connClinicas()->select("SELECT id FROM consultas_guia_encaminhamento_itens WHERE identificador = $idDominio 
            AND consultas_guia_encaminhamento_id = $idEncaminhamento  AND procedimento_id = $procedimentoId");
        if (count($qr) > 0) {
            return $qr[0];
        } else {
            return false;
        }
    }

    public function store($idDominio, Array $dados) {


        if (isset($dados['consultas_id']) and ! empty($dados['consultas_id'])) {
            $verifica = $this->getByConsultaId($idDominio, $dados['consultas_id'], true);
            $dados['orcamento_id'] = null;
        } else if (isset($dados['orcamento_id']) and ! empty($dados['orcamento_id'])) {
            $verifica = $this->getByOrcamentoId($idDominio, $dados['orcamento_id'], true);
            $dados['consultas_id'] = null;
        }

        if ($verifica) {
            $qr = $this->updateDB('consultas_guia_encaminhamento', $dados, " identificador = $idDominio AND id = $verifica->id LIMIT 1", null, 'clinicas');
            $qr = $verifica->id;
        } else {
            $dados['codigo'] = $this->geraCodigo($idDominio);
            $dados['data_cad'] = date('Y-m-d H:i:s');
            $dados['identificador'] = $idDominio;
            $qr = $this->insertDB('consultas_guia_encaminhamento', $dados, null, 'clinicas');
        }
        return $qr;
    }

    public function getByConsultaId($idDominio, $consultaId, $verifica = false) {

        $camposSql = $join = '';

        if ($verifica) {
            $camposSql = 'id';
            $join = '';
        } else {
            $camposSql = "A.*,
             AES_DECRYPT(C.nome_cript, '$this->ENC_CODE') as nomePaciente,
                 AES_DECRYPT(C.sobrenome_cript, '$this->ENC_CODE') as sobrenomePaciente,
                     C.exibe_sobrenome_impressao,
                       AES_DECRYPT(C.rg_cript, '$this->ENC_CODE') as rgPaciente,
                       AES_DECRYPT(C.cpf_cript, '$this->ENC_CODE') as cpfPaciente,
                       AES_DECRYPT(C.telefone_cript, '$this->ENC_CODE') as telefonePaciente,
                       AES_DECRYPT(C.celular_cript, '$this->ENC_CODE') as celularPaciente,
                           C.data_nascimento";
            $join = "LEFT JOIN consultas as B
            ON A.consultas_id = B.id
            LEFT JOIN pacientes as C
            ON B.pacientes_id = C.id";
        }


        $qr = $this->connClinicas()->select("SELECT $camposSql
            FROM consultas_guia_encaminhamento  as A 
          $join
            WHERE A.identificador = $idDominio  AND A.consultas_id = $consultaId");
        if (count($qr) > 0) {
            return $qr[0];
        } else {
            return false;
        }
    }

    public function getByOrcamentoId($idDominio, $orcamentoId, $verifica = false) {
        $camposSql = $join = '';
        if ($verifica) {
            $camposSql = 'id';
            $join = '';
        } else {
            $camposSql = "A.*,
             AES_DECRYPT(C.nome_cript, '$this->ENC_CODE') as nomePaciente,
                 AES_DECRYPT(C.sobrenome_cript, '$this->ENC_CODE') as sobrenomePaciente,
                     C.exibe_sobrenome_impressao,
                       AES_DECRYPT(C.rg_cript, '$this->ENC_CODE') as rgPaciente,
                       AES_DECRYPT(C.cpf_cript, '$this->ENC_CODE') as cpfPaciente,
                       AES_DECRYPT(C.telefone_cript, '$this->ENC_CODE') as telefonePaciente,
                       AES_DECRYPT(C.celular_cript, '$this->ENC_CODE') as celularPaciente,
                           C.data_nascimento";

            $join = " LEFT JOIN orcamentos as B
                                    ON A.orcamento_id = B.id
                                    LEFT JOIN pacientes as C
                                    ON B.paciente_id = C.id";
        }


        $qr = $this->connClinicas()->select("SELECT $camposSql
                           FROM
                           consultas_guia_encaminhamento as A 
                              $join
                           WHERE A.identificador = $idDominio  AND A.orcamento_id = $orcamentoId");
        if (count($qr) > 0) {
            return $qr[0];
        } else {
            return false;
        }
        return $qr;
    }

    public function storeEncaminhamentoItem($idDominio, $encaminhamentoId, Array $dados) {


        $verifica = $this->verificaItemProcedimentoId($idDominio, $encaminhamentoId, $dados['procedimento_id']);
        if ($verifica) {
            $qr = $this->updateDB('consultas_guia_encaminhamento_itens', $dados, "identificador = $idDominio AND id = $verifica->id LIMIT 1",null, 'clinicas');
        } else {
            $dados['data_cad'] = date('Y-m-d H:i:s');
            $dados['identificador'] = $idDominio;
            $qr = $this->insertDB('consultas_guia_encaminhamento_itens', $dados, null, 'clinicas');
        }


        return $qr;
    }

}
