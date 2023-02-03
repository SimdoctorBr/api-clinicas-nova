<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\Clinicas;

use App\Services\BaseService;
use DateTime;
use App\Services\Clinicas\CalculosService;
use Illuminate\Support\Facades\Mail;
use App\Helpers\Functions;
use App\Repositories\Gerenciamento\DominioRepository;
use App\Repositories\Clinicas\PlanoBeneficioRepository;
use App\Repositories\Clinicas\PacientesAsaasPagamentosRepository;
use App\Repositories\Clinicas\Paciente\PacienteRepository;

/**
 * Description of Activities
 *
 * @author ander
 */
class PlanoBeneficioService extends BaseService {

    private $pacienteRepository;
    private $arrayTipoDesconto = [1 => 'Percentual', 2 => 'Valor Fixo'];

    private function fieldsResponse($row) {



        $dados['id'] = $row->id;
        $dados['nome'] = $row->nome;
        $dados['valor'] = $row->valor;
        $dados['descricao'] = $row->descricao;
        $dados['desconto'] = [
            'tipo' => (!empty($row->desconto_tipo)) ? $this->arrayTipoDesconto[$row->desconto_tipo] : '',
            'valor' => $row->desconto_valor
        ];

        return $dados;
    }

    public function getAll($idDominio, $dadosFiltro = null) {

        $PlanoBeneficioRepository = new PlanoBeneficioRepository;
        $qr = $PlanoBeneficioRepository->getAll($idDominio, $dadosFiltro);

        if (count($qr) > 0) {
            $retorno = null;
            foreach ($qr as $row) {
                $retorno[] = $this->fieldsResponse($row);
            }
            return $this->returnSuccess($retorno);
        } else {

            return $this->returnSuccess('', 'Sem planos de beneícios cadastros');
        }
    }

    public function delete($idDominio, $idPlano) {
        $PlanoBeneficioRepository = new PlanoBeneficioRepository;
        $qr = $PlanoBeneficioRepository->getById($idDominio, $idPlano);
        if (!$qr) {
            return $this->returnError(null, 'Plano não encontrado');
        }

        $rowPlano = $qr;
        if ($rowPlano->status == 0) {
            return $this->returnError(null, 'O plano já foi excluído.');
        } else {
            $PlanoBeneficioRepository->delete($idDominio, $idPlano);
            return $this->returnSuccess(null, 'Plano excluído com sucesso.');
        }
    }

    public function getById($idDominio, $idPlano) {

        $PlanoBeneficioRepository = new PlanoBeneficioRepository;
        $qr = $PlanoBeneficioRepository->getById($idDominio, $idPlano);
        if (!$qr) {
            return $this->returnError(null, 'Plano não encontrado');
        } else {
            return $this->returnSuccess($this->fieldsResponse($qr));
        }
    }

 

    public function store($idDominio, $dadosInput) {


        $dadosPlano['nome'] = $dadosInput['nome'];
        $dadosPlano['valor'] = $dadosInput['valor'];
        $dadosPlano['financeiro_periodo_repeticao_id'] = $dadosInput['periodo'];
        $dadosPlano['descricao'] = $dadosInput['descricao'];
        $dadosPlano['desconto_tipo'] = $dadosInput['descontoTipo'];
        $dadosPlano['desconto_valor'] = $dadosInput['descontoValor'];
        $dadosPlano['desconto_valor'] = $dadosInput['descontoValor'];
        $dadosPlano['identificador'] = $idDominio;

        $PlanoBeneficioRepository = new PlanoBeneficioRepository;
        $idPlano = $PlanoBeneficioRepository->store($idDominio, $dadosPlano);

        if ($idPlano) {
            $row = $PlanoBeneficioRepository->getById($idDominio, $idPlano);
            return $this->returnSuccess($this->fieldsResponse($row), 'Plano cadastrado com sucesso.');
        } else {
            return $this->returnError(null, 'Erro a o cadastrar o plano');
        }
    }

    public function update($idDominio, $idPlano, $dadosInput) {

        $PlanoBeneficioRepository = new PlanoBeneficioRepository;
        $qr = $PlanoBeneficioRepository->getById($idDominio, $idPlano);
        if (!$qr) {
            return $this->returnError(null, 'Plano não encontrado');
        }

        $DominioRepository = new DominioRepository;
        $rowDominio = $DominioRepository->getById($idDominio);

        $dadosPlano['nome'] = $dadosInput['nome'];
        $dadosPlano['valor'] = $dadosInput['valor'];
        $dadosPlano['financeiro_periodo_repeticao_id'] = $dadosInput['periodo'];
        $dadosPlano['descricao'] = $dadosInput['descricao'];
        $dadosPlano['desconto_tipo'] = $dadosInput['descontoTipo'];
        $dadosPlano['desconto_valor'] = $dadosInput['descontoValor'];
        $dadosPlano['desconto_valor'] = $dadosInput['descontoValor'];

        $PlanoBeneficioRepository = new PlanoBeneficioRepository;
        $rowAnt = $PlanoBeneficioRepository->getById($idDominio, $idPlano);

        $qr = $PlanoBeneficioRepository->update($idDominio, $idPlano, $dadosPlano);
        $row = $PlanoBeneficioRepository->getById($idDominio, $idPlano);

        if ($rowAnt->valor != $dadosInput['valor'] or $rowAnt->financeiro_periodo_repeticao_id != $dadosInput['periodo']
                or $dadosInput['nome'] != $rowAnt->nome) {

            if ($rowDominio->habilita_assas == 1) {
                $PacientesAsaasPagamentosRepository = new PacientesAsaasPagamentosRepository;
                $PacientesAsaasPagamentosRepository->atualizaLoteAssinatura($idDominio, $idPlano);
            }
        }


        return $this->returnSuccess($this->fieldsResponse($row), 'Plano editado com sucesso.');
    }

}
