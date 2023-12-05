<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\Clinicas\Paciente;

use App\Services\BaseService;
use DateTime;
use App\Helpers\Functions;
use App\Repositories\Clinicas\Paciente\PacienteFavoritosRepository;
use App\Services\Clinicas\Doutores\DoutoresService;
use App\Repositories\Gerenciamento\DominioRepository;
use App\Repositories\Clinicas\Paciente\PacienteDependentesRepository;
use App\Repositories\Clinicas\DefinicaoMarcacaoGlobalRepository;
use App\Repositories\Clinicas\PlanoAprovacoesRepository;

//use App\Repositories\Clinicas\Paciente\PacienteExameRepository;
//use App\Repositories\Clinicas\Paciente\PacienteLaudoRepository;
//use App\Repositories\Clinicas\Paciente\PacienteResultadoExameRepository;

/**
 * Description of Activities
 *
 * @author ander
 */
class PacienteDependenteService extends BaseService {

    private $pacienteDependentesRepository;

    public function __construct() {
        $this->pacienteDependentesRepository = new PacienteDependentesRepository;
    }

    private function getNomeStatusAprovacao($statusAprovacao) {

        switch ($statusAprovacao) {
            case 1: return 'Em análise';
                break;
            case 2: return 'Aprovado';
                break;
            case 3: return 'Reprovado';
                break;
            case 4: return 'Código não confirmado';
                break;
        }
    }

    public function getByPaciente($idDominio, $idPaciente, $dadosQuery = null) {
        $DefinicaoMarcacaoGlobalRepository = new DefinicaoMarcacaoGlobalRepository;
        $rowDef = $DefinicaoMarcacaoGlobalRepository->getDadosDefinicao($idDominio, ['habilita_doc_dependentes', 'hbt_aprov_dependente']);

        $PlanoAprovacao = new PlanoAprovacoesRepository;
        $qrAprovDependente = $PlanoAprovacao->getAprovacoesDependentesByPacienteId($idDominio, $idPaciente,1);
//        dd($qrAprovDependente);
        $somenteAprovados = false;
        if (isset($dadosQuery['somenteAprovados']) and $dadosQuery['somenteAprovados'] == true) {
            $somenteAprovados = true;
        }

        $qr = $this->pacienteDependentesRepository->getByPaciente($idDominio, $idPaciente);
        $retorno = null;

        $ocultaAprovados = [];
        if ($qr) {
            foreach ($qr as $rowDep) {
                $ocultaAprovados[] = $rowDep->dependente_id;

                $retorno[] = [
                    'pacienteId' => $rowDep->dependente_id,
                    'nome' => $rowDep->nomeDependente,
                    'sobrenome' => $rowDep->sobrenomeDependente,
                    'cpf' => Functions::cpfToNumber($rowDep->cpfDependente),
                    'dataNascimento' => Functions::dateBrToDB($rowDep->data_nascimento),
                    'filiacao' => $rowDep->filiacaoAprov,
                    'possuiAprovacao' => ($rowDef->hbt_aprov_dependente == 1) ? true : false,
                    'dadosAprovacao' => [
                        'id' => $rowDep->idAprovacao,
                        'status' => ['id' => 2,
                            'nome' => $this->getNomeStatusAprovacao(2),
                        ],
                    ],
                    'sexo' => $rowDep->sexoDependente,
                    'dataCadastro' => $rowDep->data_cad_pac,
                ];
            }
        }

        if (!$somenteAprovados) {
            if ($rowDef->hbt_aprov_dependente == 1) {
                if ($qrAprovDependente) {
                    foreach ($qrAprovDependente as $rowAp) {
                        if (!in_array($rowAp->pacientes_dep_id, $ocultaAprovados)) {

                            $retorno[] = [
                                'pacienteId' => null,
                                'nome' => $rowAp->nomeDependente,
                                'sobrenome' => $rowAp->sobrenomeDependente,
                                'cpf' => Functions::cpfToNumber($rowAp->cpfDependente),
                                'dataNascimento' => $rowAp->data_nascimento,
                                'filiacao' => $rowAp->filiacao,
                                'possuiAprovacao' => true,
                                'dadosAprovacao' => [
                                    'id' => $rowAp->id,
                                    'status' => ['id' => $rowAp->status,
                                        'nome' => $this->getNomeStatusAprovacao($rowAp->status),
                                    ],
                                ],
                                'sexo' => $rowAp->sexoDependente,
                                'dataCadastro' => $rowAp->data_cad,
                            ];
                        }
                    }
                }
            }
        }


        return $this->returnSuccess($retorno);
    }
}
