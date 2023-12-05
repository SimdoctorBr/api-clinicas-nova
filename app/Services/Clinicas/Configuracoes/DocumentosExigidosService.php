<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\Clinicas\Configuracoes;

use App\Services\BaseService;
use DateTime;
use App\Helpers\Functions;
use App\Services\CacheService;
use App\Repositories\Clinicas\Configuracoes\DocumentosExigidosRepository;

/**
 * Description of Activities
 *
 * @author ander
 */
class DocumentosExigidosService extends BaseService {

    private $documentosExigidosRepository;
    private $id;
    private $identificador;
    private $nome;
    private $tipo;
    private $obrigatorio;
    private $status;
    private $exibe_cadastro;
    private $formato_arquivo;
    private $exibe_cad_qr_code;

    public function __construct() {
        $this->documentosExigidosRepository = new DocumentosExigidosRepository;
    }

    private function getFomatoArquivo($formatoArquivo) {

        switch ($formatoArquivo) {
            case 1:
                return ['id' => $formatoArquivo,
                    'nome' => 'Pdf e Imagem'];
                break;
            case 2:
                return ['id' => $formatoArquivo,
                    'nome' => 'Somente imagem'];
                break;
            case 3:
                return ['id' => $formatoArquivo,
                    'nome' => 'Somente PDF'];
                break;
        }
    }

    private function getNomeTipo($tipoId) {

        switch ($tipoId) {
            case 1:
                return ['id' => $tipoId,
                    'nome' => 'Dependente'];
                break;
            case 2:
                return ['id' => $tipoId,
                    'nome' => 'Paciente'];
                break;
        }
    }

    public function setDocumentosExigidosRepository($documentosExigidosRepository) {
        $this->documentosExigidosRepository = $documentosExigidosRepository;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function setIdentificador($identificador) {
        $this->identificador = $identificador;
    }

    public function setNome($nome) {
        $this->nome = $nome;
    }

    public function setTipo($tipo) {
        $this->tipo = $tipo;
    }

    public function setObrigatorio($obrigatorio) {
        $this->obrigatorio = $obrigatorio;
    }

    public function setStatus($status) {
        $this->status = $status;
    }

    public function setExibe_cadastro($exibe_cadastro) {
        $this->exibe_cadastro = $exibe_cadastro;
    }

    public function setFormato_arquivo($formato_arquivo) {
        $this->formato_arquivo = $formato_arquivo;
    }

    public function setExibe_cad_qr_code($exibe_cad_qr_code) {
        $this->exibe_cad_qr_code = $exibe_cad_qr_code;
    }

    public function store($idDominio) {

        $campos['nome'] = $this->nome;
        $campos['tipo'] = $this->tipo;
        $campos['obrigatorio'] = $this->obrigatorio;
        $campos['identificador'] = $idDominio;
        $campos['exibe_cad_qr_code'] = $this->exibe_cad_qr_code;
        $campos['formato_arquivo'] = $this->formato_arquivo;

        $qr = $this->documentosExigidosRepository->store($idDominio, $campos);
        return $qr;
    }

    public function update($idDominio) {
        $campos['nome'] = $this->nome;
        $campos['tipo'] = $this->tipo;
        $campos['obrigatorio'] = $this->obrigatorio;
        $campos['exibe_cad_qr_code'] = $this->exibe_cad_qr_code;
        $campos['formato_arquivo'] = $this->formato_arquivo;
        $qr = $this->documentosExigidosRepository->update("documentos_exigidos", $campos, " identificador = $idDominio and id = $this->id limit 1");
    }

    public function getAllExibeCadastro($idDominio, $tipoDoc) {
        $qr = $this->documentosExigidosRepository->getAllExibeCadastro($idDominio, $tipoDoc);
        $retorno = [];
        if ($qr) {
            $retorno = null;
            foreach ($qr as $row) {
                $retorno[] = [
                    'id' => $row->id,
                    'perfilId' => $row->identificador,
                    'nome' => utf8_decode($row->nome),
                    'tipo' => $this->getNomeTipo($row->tipo),
                    'obrigatorio' => $row->obrigatorio,
                    'exibeCadastro' => $row->exibe_cadastro,
                    'exibeCadQrCode' => $row->exibe_cad_qr_code,
                    'formatoArquivo' => $this->getFomatoArquivo($row->formato_arquivo),
                ];
            }
        }
        return $this->returnSuccess($retorno);
    }
}
