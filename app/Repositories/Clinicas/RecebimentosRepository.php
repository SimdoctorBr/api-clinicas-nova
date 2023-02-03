<?php

namespace App\Repositories\Clinicas;

use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;
use App\Repositories\Clinicas\DefinicaoMarcacaoGlobalRepository;
use App\Repositories\Clinicas\FinanceiroFornecedorRepository;

class RecebimentosRepository extends BaseRepository {

    private function getUltimoCod($idDominio) {

        $qr = $this->connClinicas()->select("SELECT MAX(A.rec_codigo) as total FROM financeiro_recebimento AS A WHERE identificador = $idDominio");
        $row = $qr[0];

        $qrDefGlobal = new DefinicaoMarcacaoGlobalRepository;
        $rowDefGlobal = $qrDefGlobal->getDadosDefinicao($idDominio);

        if (empty($row->total)) {
            return $rowDefGlobal->nr_inicio_recebimento + 1;
        } else {
            return $row->total + 1;
        }
    }

    public function insereRecebimento($idDominio, Array $dados) {

        $dados['ano_codigo'] = date('Y');
        $dados['rec_codigo'] = $this->getUltimoCod($idDominio);
        $dados['recebimento_data_cad'] = date('Y-m-d H:i:s');
        $dados['identificador'] = $idDominio;

        if ($dados['pago'] == 1) {
            $Fornecedor = new FinanceiroFornecedorRepository;
            $saldo_conta = $Fornecedor->getSaldoConta($idDominio, $dados['pagar_com_adm_banco']);
            $saldo_conta = $saldo_conta + $dados['recebimento_valor'];
            $Fornecedor->atualizaSaldo($idDominio, $dados['pagar_com_adm_banco'], $saldo_conta);
        }

        return $this->insertDB('financeiro_recebimento', $dados, null, 'clinicas');
    }

    public function getByConsultaId($idDominio, $idConsulta, $idRecebimento=null) {

        if (is_array($idDominio)) {
            $sql = 'A.identificador IN(' . implode(',', $idDominio) . ")";
        } else {
            $sql = "A.identificador = $idDominio";
        }
        if(!empty($idRecebimento)){
            $sql .= " AND idRecebimento = $idRecebimento";
        }
        
        $qr = $this->connClinicas()->select("SELECT A.*, B.nome as nomeClinica, B.cep, B.cidade, B.estado, B.logradouro, B.complemento, AES_DECRYPT(D.cpf_cript, '$this->ENC_CODE') as cpfPaciente
            FROM  financeiro_recebimento as A
            LEFT JOIN minisite as B
            ON A.identificador = B.identificador
            LEFT JOIN consultas as C
            ON C.id = A.consulta_id
              LEFT JOIN pacientes as D
            ON D.id = C.pacientes_id
            WHERE $sql AND A.status = 1 AND A.consulta_id = '$idConsulta'  ");
        return $qr;
    }

    public function getById($idDominio, $idRecebimento) {

        $qr = $this->connClinicas()->select("SELECT * FROM  financeiro_recebimento WHERE identificador = '$idDominio' AND status = 1 AND idRecebimento  = $idRecebimento ");
        return $qr;
    }

}
