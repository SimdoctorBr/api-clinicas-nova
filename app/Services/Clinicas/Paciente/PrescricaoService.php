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
use App\Repositories\Gerenciamento\DominioRepository;
use App\Repositories\Clinicas\Consulta\ConsultaProntuarioRepository;
use App\Repositories\Clinicas\Paciente\PacientePrescricaoRepository;

/**
 * Description of Activities
 *
 * @author ander
 */
class PrescricaoService extends BaseService {

    public function getAll($idDominio, $pacienteId, $dadosFiltro) {

//PRESCRICOES
        $qrPrescricoes = $PacientePrescricaoRepository->getAll($idDominio, $dadosFiltro);
        $dadosPrescricao = null;

        foreach ($qrPrescricoes as $rowPrescricao) {
            $dadosPrescricao = [
                'id' => $rowPrescricao->id,
                'pacienteId' => $rowPrescricao->pacientes_id,
                'consultaId' => $rowPrescricao->consulta_id,
                'dataCad' => $rowPrescricao->data_cad,
//                'nomeDoutor' => $rowPrescricao->nome,
                'prescricao_especial' => $rowPrescricao->prescricao_especial,
            ];

            $itensPrescricao = null;
            $qrItensPrescricao = $PacientePrescricaoRepository->getItensByIdPrescricao($idDominio, $rowPrescricao->id);
            $i = 0;
            if (count($qrItensPrescricao) > 0) {
                foreach ($qrItensPrescricao as $rowItem) {
                    $posologia = '';
                    if ($rowItem->med_vezes_ao_dia != 0) {
                        $posologia = $rowItem->med_tomar;
                        $posologia .= ' ' . $rowItem->med_quantidade_medida;
                        $posologia .= ' ' . $rowItem->med_medida_nome;
                        $posologia .= ' ' . ('vezes ao dia ' . $rowItem->med_vezes_ao_dia);
                        $posologia .= ' ' . ($rowItem->med_duracao);
                        $posologia .= ' ' . ($rowItem->med_quantidade_duracao);
                        $posologia .= ' ' . ($rowItem->med_tipo_duracao);
                    }
                    $tipo = 'normal';
                    if ($rowItem->especial == 1) {
                        $tipo = 'especial';
                    }
                    $itensPrescricao[$i][$tipo][] = [
                        'id' => $rowItem->idConsultaPrescItem,
                        'medNome' => $rowItem->med_nome,
                        'posologia' => $posologia,
                        'observacao' => $rowItem->observacao,
                    ];
                }
            }
            $dadosPrescricao['itens'] = $itensPrescricao;
              dd($dadosPrescricao);


            $DADOS_RETORNO[substr($rowPrescricao->data_cad, 0, 10)]['prescricoes'][] = $dadosPrescricao;
            unset($dadosPrescricao);
        }
    }

}
