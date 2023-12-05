<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\Clinicas;

use App\Services\BaseService;
use DateTime;
use App\Repositories\Clinicas\PlanoAprovacoesRepository;
use App\Repositories\Gerenciamento\DominioRepository;
use App\Services\Clinicas\Utils\UploadService;
use App\Repositories\Clinicas\Paciente\PacienteRepository;

/**
 * Description of Activities
 *
 * @author ander
 */
class PlanoAprovacoesService extends BaseService {

    private $id;
    private $identificador;
    private $pacientes_id;
    private $status;
    private $doc_exigidos_ids_hist;
    private $tipo;
    private $pacientes_dep_id;
    private $pacientes_dep_id_assoc;
    private $nome;
    private $sobrenome;
    private $cpf;
    private $sexo;
    private $data_nascimento;
    private $nome_foto;
    private $filiacao;
    private $pathDoc;
    private $urlDoc;
    private $planoAprovacoesRepository;

    public function __construct() {
        $this->planoAprovacoesRepository = new PlanoAprovacoesRepository;
        $this->urlDoc = env('APP_URL_CLINICAS') . '/arquivos/arquivo_dep_aprovacao';
    }

    public function getPathDoc($nomeDominio) {

        $this->pathDoc = env('APP_PATH_CLINICAS') . $nomeDominio . '/arquivos/arquivo_dep_aprovacao';
        return $this->pathDoc;
    }

    public function getUrlDoc($nomeDominio) {
        $this->urlDoc = env('APP_URL_CLINICAS') . $nomeDominio . '/arquivos/arquivo_dep_aprovacao';
        return $this->urlDoc;
    }

    public function getPathFoto($nomeDominio) {
        return env('APP_PATH_CLINICAS') . $nomeDominio . "/arquivos/dependentes_aprovacao/";
    }

    public function setNome($nome) {
        $this->nome = $nome;
    }

    public function setSobrenome($sobrenome) {
        $this->sobrenome = $sobrenome;
    }

    public function setCpf($cpf) {
        $this->cpf = $cpf;
    }

    public function setSexo($sexo) {
        $this->sexo = $sexo;
    }

    public function setData_nascimento($data_nascimento) {
        $this->data_nascimento = $data_nascimento;
    }

    public function setNome_foto($nome_foto) {
        $this->nome_foto = $nome_foto;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function setIdentificador($identificador) {
        $this->identificador = $identificador;
    }

    public function setPacientes_id($pacientes_id) {
        $this->pacientes_id = $pacientes_id;
    }

    public function setStatus($status) {
        $this->status = $status;
    }

    public function setDoc_exigidos_ids_hist($doc_exigidos_ids_hist) {
        $this->doc_exigidos_ids_hist = $doc_exigidos_ids_hist;
    }

    public function setTipo($tipo) {
        $this->tipo = $tipo;
    }

    public function setPacientes_dep_id($pacientes_dep_id) {
        $this->pacientes_dep_id = $pacientes_dep_id;
    }

    public function setPacientes_dep_id_assoc($pacientes_dep_id_assoc) {
        $this->pacientes_dep_id_assoc = $pacientes_dep_id_assoc;
    }

    public function setFiliacao($filiacao) {
        $this->filiacao = $filiacao;
    }

    public function insert($idDominio) {
        $campos['identificador'] = $idDominio;
        $campos['pacientes_id'] = $this->pacientes_id;
        $campos['pacientes_dep_id'] = $this->pacientes_dep_id;
        $campos['doc_exigidos_ids_hist'] = $this->doc_exigidos_ids_hist;
        $campos['nome'] = $this->nome;
        $campos['sobrenome'] = $this->sobrenome;
        $campos['cpf'] = $this->cpf;
        $campos['sexo'] = $this->sexo;
        $campos['filiacao'] = $this->filiacao;
        if (!empty($this->data_nascimento)) {
            $campos['data_nascimento'] = $this->data_nascimento;
        }
        if (!empty($this->nome_foto)) {
            $campos['nome_foto'] = $this->nome_foto;
        }
        if (!empty($this->status)) {
            $campos['status'] = $this->status;
        }

        $campos['pacientes_dep_id_assoc'] = $this->pacientes_dep_id_assoc;
        $campos['tipo'] = $this->tipo;
        $campos['data_cad'] = date('Y-m-d H:i:s');

        if ($this->tipo == 3) {
            $PacienteRepository = new PacienteRepository;
            $PacienteRepository->update($idDominio, $this->pacientes_id, ['doc_verificados' => 1, 'doc_verificados_data' => date('Y-m-d H:i:s')]);
        }
        return $this->planoAprovacoesRepository->insert($idDominio, $campos);
    }

    public function insertArquivoDocExigido($idDominio, $idPlAProvacao, $idDocExigido, $nomeArquivo) {
        $campos['identificador'] = $idDominio;
        $campos['plano_aprovacoes_id'] = $idPlAProvacao;
        $campos['doc_exigido_id'] = $idDocExigido;
        $campos['nome_arquivo'] = $nomeArquivo;
        $campos['data_cad'] = date('Y-m-d H:i:s');
        return $this->planoAprovacoesRepository->insertArquivoDocExigido($idDominio, $campos);
    }

    public function salvaFotoPerfilAprovacao($idDominio, $dadosFile) {

        $DominioRepository = new DominioRepository();
        $rowDominio = $DominioRepository->getById($idDominio);
        $baseDir = $this->getPathFoto($rowDominio->dominio);

        $file = $dadosFile;
        $extensao = $file->getClientOriginalExtension();
        $originalName = $file->getClientOriginalName();

        $nameFile = "foto_" . md5(time()) . '.' . $extensao;

        $moveFile = $file->move($baseDir, $nameFile);
        if ($moveFile) {
            $UploadService = new UploadService;
            $UploadService->resizeImage($moveFile->getRealPath(), $baseDir . '/' . $nameFile, 320, 240);
            return $this->returnSuccess([
                        'nomeFoto' => $nameFile,
//                        'urlFoto' => $url . $nameFile
                            ], ['Alterado com sucesso']);
        } else {
            return $this->returnError(null, ['Ocorreu um erro ao adicionar a foto, por favor tente mais tarde']);
        }
    }
}
