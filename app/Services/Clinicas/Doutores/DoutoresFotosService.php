<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\Clinicas\Doutores;

use App\Services\BaseService;
use App\Repositories\Clinicas\DoutoresRepository;
use App\Repositories\Clinicas\Doutores\DoutoresFotosRepository;
use App\Repositories\Clinicas\ConsultaRepository;
use App\Repositories\Gerenciamento\DominioRepository;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;
use App\Services\Clinicas\Utils\UploadService;
use App\Services\Clinicas\ConsultaService;

/**
 * Description of Activities
 *
 * @author ander
 */
class DoutoresFotosService extends BaseService {

    private $doutoresFotosRep;

    public function __construct(DoutoresFotosRepository $paciFotoRep) {
        $this->doutoresFotosRep = $paciFotoRep;
    }

    private function fieldsResponse($row, $dominioNome = null, $showBase64 = false) {

        if ($showBase64) {
            $fileBase = file_get_contents(env('APP_URL_CLINICAS') . $dominioNome . '/fotos/' . rawurlencode($row->foto));
            $type = pathinfo('../../app/perfis/' . $dominioNome . '/fotos/' . rawurlencode($row->foto), PATHINFO_EXTENSION);
            $base64 = 'data:image/' . $type . ';base64,' . base64_encode($fileBase);
        }
        $retorno = [
            'id' => $row->id,
            'doutorId' => $row->doutores_id,
//            'consultaId' => $row->consultas_id,
            'urlFoto' => env('APP_URL_CLINICAS') . $dominioNome . '/arquivos/doutores/fotos/' . $row->doutores_id . '/' . rawurlencode($row->foto),
            'urlFotoThumb' => env('APP_URL_CLINICAS') . $dominioNome . '/arquivos/doutores/fotos/' . $row->doutores_id . '/' . rawurlencode($row->foto),
            'title' => $row->title,
//            'habilitaVisualizarPaciente' => $row->habilita_visualizar_paciente,
//            'adicionadoPeloPaciente' => $row->adicionado_pelo_paciente,
            'dataCad' => $row->data_cad,
//            'dataCad' =>  $row->data_cad,
        ];

        if ($showBase64) {
            $retorno['base64Foto'] = $base64;
        }


        return $retorno;
    }

    public function getAll($idDominio, $doutorId, $request) {




        $DominioRepository = new DominioRepository;
        $rowDominio = $DominioRepository->getById($idDominio);

        $DoutoresRepository = new DoutoresRepository;
        $rowDoutor = $DoutoresRepository->getById($idDominio, $doutorId);
        if (!$rowDoutor) {
            return $this->returnError(null, 'Doutor(a) não encontrado.');
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
        if ($request->has('data') and!empty($request->query("data"))) {
            $dadosFiltro['dataInicio'] = $request->query('data');
            if ($request->has('dataFim') and!empty($request->query("dataFim"))) {
                $dadosFiltro['dataFim'] = $request->query('dataFim');
            }
        }

        if ($request->has('consultaId') and!empty($request->query("consultaId"))) {
            $dadosFiltro['consultaId'] = $request->query("consultaId");
        }

        $showBase64 = false;
        if ($request->has('showFile64') and!empty($request->query("showFile64")) and $request->query("showFile64") == 'true') {
            $showBase64 = true;
        }



        if ($request->has('orderBy') and!empty($request->query("orderBy"))) {
            $ordem = explode('.', $request->query("orderBy"));

            if (isset($arrayCamposFiltro[$ordem[0]])) {
                $dadosFiltro['campoOrdenacao'] = $arrayCamposFiltro[$ordem[0]];
                if (isset($ordem[1]) and ( $ordem[1] == 'desc' OR $ordem[1] == 'asc')) {
                    $dadosFiltro['tipoOrdenacao'] = $ordem[1];
                }
            }
        }


        $qr = $this->doutoresFotosRep->getAll($idDominio, $doutorId, $dadosFiltro, $dadosPaginacao['page'], $dadosPaginacao['perPage']);

        if (count($qr) > 0) {
            $retorno = [];
            foreach ($qr['results'] as $row) {


                $retorno[] = $this->fieldsResponse($row, $rowDominio->dominio, $showBase64);
            }
            $qr['results'] = $retorno;

            return $this->returnSuccess($qr);
        } else {
            
        }
    }

    public function store($idDominio, $doutorId, $request) {


//        
        $validateFile = validator($request->file(), [
            'foto' => 'required|file|image',
                ], [
            'foto.required' => 'Foto não enviada',
            'foto.image' => 'Formatos suportados: jpg,gif,bmp,jpeg,png',
            'foto.size' => 'O tamanho máximo é de 24MB',
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
        } else if ($request->file('foto')->getSize() > 24000000) {
            return $this->returnError(null, ['O tamanho máximo é de 24MB']);
        } else {

//            if ($request->has('consultaId') and!empty($request->input('consultaId'))) {
//                $ConsultaRepository = new ConsultaRepository;
//                $rowConsulta = $ConsultaRepository->getById($idDominio, $request->input('consultaId'), $doutorId);
//                if (!$rowConsulta) {
//                    $this->returnError(null, ['A consulta informada não existe']);
//                }
//            }


            $DominioRepository = new DominioRepository();
            $rowDominio = $DominioRepository->getById($idDominio);
            $url = env('APP_URL_CLINICAS') . $rowDominio->dominio . '/arquivos/doutores/fotos/' . $doutorId . '/';
            $urlThumb = env('APP_URL_CLINICAS') . $rowDominio->dominio . '/arquivos/doutores/fotos/' . $doutorId . '/thumbs/';
            $baseDir = '../../app/perfis/' . $rowDominio->dominio . '/arquivos/doutores/fotos/' . $doutorId;

            $file = $request->file('foto');
            $extensao = $file->getClientOriginalExtension();
            $originalName = $file->getClientOriginalName();

            $nameFile = md5(uniqid(time())) . "." . $extensao;
            $imageThumb = "/thumbs/" . $nameFile;

            $moveFile = $file->move($baseDir, $nameFile);

            if ($moveFile) {
                $UploadService = new UploadService;
                $UploadService->resizeImage($moveFile->getRealPath(), $baseDir . $imageThumb, 800, 600);

                $DadosFotos['doutores_id'] = $doutorId;
                $DadosFotos['foto'] = $nameFile;
                $DadosFotos['title'] = ($request->has('title') and!empty($request->input('title'))) ? $request->input('title') : $originalName;
                $DadosFotos['identificador'] = $idDominio;
//                $DadosFotos['consultas_id'] = ($request->has('consultaId') and!empty($request->input('consultaId'))) ? $request->input('consultaId') : null;
//                $DadosFotos['habilita_visualizar_paciente'] = $area_paciente;
//                $DadosFotos['adicionado_pelo_paciente'] = $adicionado_pelo_paciente;

                $idInsert = $this->doutoresFotosRep->store($idDominio, $doutorId, $DadosFotos);

                return $this->returnSuccess([
                            'id' => $idInsert,
                            'title' => $DadosFotos['title'],
//                            'consultaId' => $DadosFotos['consultas_id'],
                            'url' => $url . rawurldecode($nameFile),
                            'urlThumb' => $urlThumb . rawurldecode($nameFile),
                ]);
            } else {
                return $this->returnError(null, ['Ocorreu um erro ao adicionar a foto, por favor tente mais tarde']);
            }
        }
    }

    public function delete($idDominio, $doutorId, $idFoto) {

        $DominioRepository = new DominioRepository();
        $rowDominio = $DominioRepository->getById($idDominio);

        $rowFoto = $this->doutoresFotosRep->getById($idDominio, $idFoto);

        if (!$rowFoto) {
            return $this->returnError(null, ['Foto não encontrada']);
        }

        $this->doutoresFotosRep->delete($idDominio, $idFoto);


        $url = env('APP_PATH_CLINICAS') . $rowDominio->dominio . '/arquivos/doutores/fotos/' . $doutorId . '/' . $rowFoto->foto;
        $urlThumb = env('APP_PATH_CLINICAS') . $rowDominio->dominio . '/arquivos/doutores/fotos/' . $doutorId . '/thumbs/' . $rowFoto->foto;

        if (file_exists($url)) {
            unlink($url);
        }
        if (file_exists($urlThumb)) {
            unlink($urlThumb);
        }
        return $this->returnSuccess(null, 'Excluido com sucesso.');
    }

    public function update($idDominio, $doutorId, $idFoto, $title) {


        $rowFoto = $this->doutoresFotosRep->getById($idDominio, $idFoto);

        if (!$rowFoto) {
            return $this->returnError(null, ['Foto não encontrada']);
        }

        $dadosUpdate['title'] = $title;
        $this->doutoresFotosRep->update($idDominio, $idFoto, $dadosUpdate);
        return $this->returnSuccess(null, 'Atualizado com sucesso.');
    }

}
