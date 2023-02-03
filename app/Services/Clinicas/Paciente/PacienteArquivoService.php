<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\Clinicas\Paciente;

use App\Services\BaseService;
use App\Repositories\Clinicas\Paciente\PacienteArquivoRepository;
use App\Repositories\Gerenciamento\DominioRepository;
use App\Services\Clinicas\Utils\UploadService;
use App\Repositories\Clinicas\ConsultaRepository;
use App\Services\Clinicas\PacienteService;

/**
 * Description of Activities
 *
 * @author ander
 */
class PacienteArquivoService extends BaseService {

    private $pacienteArquivoRep;

    public function __construct(PacienteArquivoRepository $paciFotoRep) {
        $this->pacienteArquivoRep = $paciFotoRep;
    }

    private function fieldsResponse($row, $dominioNome = null) {

//        var_dump($row);
        $retorno = [
            'id' => $row->id,
            'pacienteId' => $row->pacientes_id,
            'consultaId' => $row->consultas_id,
            'urlArquivo' => 'https://app.simdoctor.com.br/' . $dominioNome . '/arquivos/' . rawurlencode($row->arquivo),
            'title' => $row->title,
            'habilitaVisualizarPaciente' => $row->habilita_visualizar_paciente,
            'adicionadoPeloPaciente' => $row->adicionado_pelo_paciente,
            'dataCad' => $row->data_cad,
//            'dataCad' =>  $row->data_cad,
        ];

        return $retorno;
    }

    public function getAll($idDominio, $pacienteId, $request) {

        $DominioRepository = new DominioRepository;
        $rowDominio = $DominioRepository->getById($idDominio);


        $PacienteService = new PacienteService;
        $rowPaciente = $PacienteService->getById($idDominio, $pacienteId);

        if (!$rowPaciente['success']) {
            return $this->returnError(null, $rowPaciente['message']);
        }




        $dadosFiltro = null;
        $dadosPaginacao = $this->getPaginate($request);

        $arrayCamposFiltro = ['dataCad' => 'data_cad', 'id' => 'A.id'];

        $validate = validator([
            'data' => 'date',
            'dataFim' => 'date'
                ], [
            'data.date' => 'Data inválida',
            'dataFim.date' => 'Data inválida',
        ]);

        if ($validate->fails()) {
            return $this->returnError($validate->errors(), $validate->errors()->all());
        }
        if ($request->has('data') and ! empty($request->query("data"))) {
            $dadosFiltro['dataInicio'] = $request->query('data');
            if ($request->has('dataFim') and ! empty($request->query("dataFim"))) {
                $dadosFiltro['dataFim'] = $request->query('dataFim');
            }
        }

        if ($request->has('consultaId') and ! empty($request->query("consultaId"))) {
            $dadosFiltro['consultaId'] = $request->query("consultaId");
        }


        if ($request->has('orderBy') and ! empty($request->query("orderBy"))) {
            $ordem = explode('.', $request->query("orderBy"));

            if (isset($arrayCamposFiltro[$ordem[0]])) {
                $dadosFiltro['campoOrdenacao'] = $arrayCamposFiltro[$ordem[0]];
                if (isset($ordem[1]) and ( $ordem[1] == 'desc' OR $ordem[1] == 'asc')) {
                    $dadosFiltro['tipoOrdenacao'] = $ordem[1];
                }
            }
        }


        $qr = $this->pacienteArquivoRep->getAll($idDominio, $pacienteId, $dadosFiltro, $dadosPaginacao['page'], $dadosPaginacao['perPage']);


        if (count($qr) > 0) {
            $retorno = [];
            foreach ($qr['results'] as $row) {
                $retorno[] = $this->fieldsResponse($row, $rowDominio->dominio);
            }
            $qr['results'] = $retorno;

            return $this->returnSuccess($qr);
        } else {
            
        }
    }

    public function store($idDominio, $pacienteId, $request) {

        $PacienteService = new PacienteService;

        $PacienteService = new PacienteService;
        $rowPaciente = $PacienteService->getById($idDominio, $pacienteId);

        if (!$rowPaciente['success']) {
            return $this->returnError(null, $rowPaciente['message']);
        }



        $validateFile = validator($request->file(), [
            'arquivo' => 'required|file'
                ], [
            'arquivo.required' => 'Arquivo não enviado',
            'arquivo.size' => 'O tamanho máximo é de 24MB',
        ]);

        $validate = validator($request->input(), [
            'consultaId' => 'numeric',
                ], [
            'consultaId.numeric' => 'O id da consulta dever ser um número',
        ]);



        if ($validateFile->fails()) {
            return $this->returnError($validateFile->errors(), $validateFile->errors()->all());
        } elseif ($validate->fails()) {
            return $this->returnError($validate->errors(), $validate->errors()->all());
        } else if ($request->file('arquivo')->getSize() > 24000000) {
            return $this->returnError(null, ['O tamanho máximo é de 24MB']);
        } else {

            if ($request->has('consultaId') and ! empty($request->input('consultaId'))) {
                $ConsultaRepository = new ConsultaRepository;
                $rowConsulta = $ConsultaRepository->getById($idDominio, $request->input('consultaId'), $pacienteId);
                if (!$rowConsulta) {
                    return $this->returnError(null, ['A consulta informada não existe']);
                }
            }

            $qrVerificaPaciente = $PacienteService->getById($idDominio, $pacienteId);
            if (!$qrVerificaPaciente['success']) {

                return $this->returnError(null, $qrVerificaPaciente['message']);
            }






            $DominioRepository = new DominioRepository();
            $rowDominio = $DominioRepository->getById($idDominio);

            $file = $request->file('arquivo');

            $UploadService = new UploadService;
            $upload = $UploadService->uploadArquivos($rowDominio->dominio, $file, 'arquivo');

            if ($upload) {

                $dadosArquivo['pacientes_id'] = $pacienteId;
                $dadosArquivo['arquivo'] = $upload['fileName'];
                $dadosArquivo['title'] = ($request->has('title') and ! empty($request->input('title'))) ? $request->input('title') : $upload['originalName'];
                $dadosArquivo['consultas_id'] = ($request->has('consultaId') and ! empty($request->input('consultaId'))) ? $request->input('consultaId') : null;
                $dadosArquivo['identificador'] = $idDominio;
//                $dadosArquivo['habilita_visualizar_paciente'] = $area_paciente;
//                $dadosArquivo['adicionado_pelo_paciente'] = $adicionado_pelo_paciente;

                $idInsert = $this->pacienteArquivoRep->store($idDominio, $pacienteId, $dadosArquivo);

                return $this->returnSuccess([
                            'id' => $idInsert,
                            'title' => $dadosArquivo['title'],
                            'consultaId' => $dadosArquivo['consultas_id'],
                            'url' => $upload['url'],
//                            'urlThumb' => $upload['urlThumb'],
                ]);
            } else {
                return $this->returnError(null, ['Ocorreu um erro ao adicionar a foto, por favor tente mais tarde']);
            }
        }
    }

    public function delete($idDominio, $pacienteId, $idArquivo) {

        $PacienteService = new PacienteService;
        $rowPaciente = $PacienteService->getById($idDominio, $pacienteId);

        if (!$rowPaciente['success']) {
            return $this->returnError(null, $rowPaciente['message']);
        }


        $rowArquivo = $this->pacienteArquivoRep->getById($idDominio, $idArquivo);




        if (!$rowArquivo) {
            return $this->returnError(null, ['Arquivo não encontrada']);
        }

        $this->pacienteArquivoRep->delete($idDominio, $idArquivo);
        return $this->returnSuccess(null, 'Excluido com sucesso.');
    }

    public function update($idDominio, $pacienteId, $idArquivo, $title) {

        $PacienteService = new PacienteService;
        $rowPaciente = $PacienteService->getById($idDominio, $pacienteId);

        if (!$rowPaciente['success']) {
            return $this->returnError(null, $rowPaciente['message']);
        }


        $rowArquivo = $this->pacienteArquivoRep->getById($idDominio, $idArquivo);

        if (!$rowArquivo) {
            return $this->returnError(null, ['Arquivo não encontrada']);
        }

        $dadosUpdate['title'] = $title;
        $this->pacienteArquivoRep->update($idDominio, $idArquivo, $dadosUpdate);
        return $this->returnSuccess(null, 'Atualizado com sucesso.');
    }

}
