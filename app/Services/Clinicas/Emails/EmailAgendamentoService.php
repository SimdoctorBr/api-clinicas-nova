<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\Clinicas\Emails;

use App\Repositories\Clinicas\DefinicaoMarcacaoConsultaRepository;
use App\Repositories\Clinicas\EmpresaRepository;
use App\Repositories\Gerenciamento\DominioRepository;
use App\Helpers\Functions;
use Illuminate\Support\Facades\Mail;

/**
 * Description of EmailService
 *
 * @author ander
 */
class EmailAgendamentoService {

    private $definicaoMarcacaoConsultaRepository;
    private $empresaRepository;
    private $mostra_titulo_padrao;
    private $dadosEmpresa;
    private $nomeDominio;
    private $linkBase;
    private $mostra_dados_consulta;
    private $mostra_link_confirmacao;
    private $mostra_link_desmarcacao;
    private $mostra_links_sociais;
    private $linkConfirmar;
    private $linkDesmarcar;
    private $linkBloqueiaEmail;
    private $linkFacebook;
    private $iconeFacebook;
    private $dadosLinksSociais = [];
    private $imgLogomarca;
    private $imagem_assinatura;
    private $doutorId;
    private $nomeDoutor;
    private $nomeClinica;
    private $linkVideo;
    private $linkPagseguro;
    private $dataConsulta;
    private $horaConsulta;
    private $precoConsulta;
    private $exibelinkConfirmar;
    private $nomePaciente;
    private $emailPaciente;
    private $enderecoClinica;

    function setDoutorId($doutorId) {
        $this->doutorId = $doutorId;
    }

    function setNomeDoutor($nomeDoutor) {
        $this->nomeDoutor = $nomeDoutor;
    }

    function setLinkVideo($linkVideo) {
        $this->linkVideo = $linkVideo;
    }

    function setLinkPagseguro($linkPagseguro) {
        $this->linkPagseguro = $linkPagseguro;
    }

    function setDataConsulta($dataConsulta) {
        $this->dataConsulta = $dataConsulta;
    }

    function setHoraConsulta($horaConsulta) {
        $this->horaConsulta = $horaConsulta;
    }

    function setPrecoConsulta($precoConsulta) {
        $this->precoConsulta = $precoConsulta;
    }

    function setExibelinkConfirmar($exibelinkConfirmar) {
        $this->exibelinkConfirmar = $exibelinkConfirmar;
    }

    function setNomePaciente($nomePaciente) {
        $this->nomePaciente = $nomePaciente;
    }

    function setEmailPaciente($emailPaciente) {
        $this->emailPaciente = $emailPaciente;
    }

    function setEnderecoClinica($enderecoClinica) {
        $this->enderecoClinica = $enderecoClinica;
    }

    public function __construct($dominioId) {

        $this->definicaoMarcacaoConsultaRepository = new DefinicaoMarcacaoConsultaRepository;
        $this->empresaRepository = new EmpresaRepository;
        $this->dadosEmpresa = $this->empresaRepository->getById($dominioId);
        $this->dadosEmpresa = $this->dadosEmpresa[0];

        $DominioRepository = new DominioRepository;
        $rowDominio = $DominioRepository->getById($dominioId);
        $this->nomeDominio = $rowDominio->dominio;

        if (!empty($this->dadosEmpresa->logradouro)) {
            $dadosEndereco[] = $this->dadosEmpresa->logradouro;
        }
        if (!empty($this->dadosEmpresa->complemento)) {
            $dadosEndereco[] = $this->dadosEmpresa->complemento;
        }
        if (!empty($this->dadosEmpresa->bairro)) {
            $dadosEndereco[] = $this->dadosEmpresa->bairro;
        }
        if (!empty($this->dadosEmpresa->cidade)) {
            $dadosEndereco[] = $this->dadosEmpresa->cidade;
        }
        if (!empty($this->dadosEmpresa->estado)) {
            $dadosEndereco[] = $this->dadosEmpresa->estado;
        }
        if (!empty($this->dadosEmpresa->cep)) {
            $dadosEndereco[] = $this->dadosEmpresa->cep;
        }


        $this->enderecoClinica = (isset($dadosEndereco) and count($dadosEndereco) > 0) ? implode(', ', $dadosEndereco) : '';
    }

    private function replaceTags($mensagem) {


        $mensagem = str_replace('#dataConsulta', $this->dataConsulta, $mensagem);
        $mensagem = str_replace('#horaConsulta', $this->horaConsulta, $mensagem);
        $mensagem = str_replace('#nomeDoutor', $this->nomeDoutor, $mensagem);
        $mensagem = str_replace('#nomeClinica', $this->dadosEmpresa->nome, $mensagem);
        $mensagem = str_replace('#enderecoClinica', $this->enderecoClinica, $mensagem);
        $mensagem = str_replace('#nomePaciente', $this->nomePaciente, $mensagem);
        return $mensagem;
    }

    private function getConfig($idDominio, $idConsulta, $email, $idDoutor) {

        $hashMD5DaConsulta = md5($idConsulta);

        $link = env('APP_URL_CLINICAS') . $this->nomeDominio . "/confirma_por_email.php?c=" . md5($idConsulta);
        $this->linkBase = env('APP_URL_CLINICAS') . $this->nomeDominio;
//          $this->linkSite = env('APP_URL_CLINICAS') . $this->nomeDominio;

        $rowDefMarcacao = $this->definicaoMarcacaoConsultaRepository->getByDoutoresId($idDominio, $idDoutor);

    
        $this->definicoesMarcacaoDoutor = $rowDefMarcacao;
        $this->mostra_dados_consulta = $rowDefMarcacao->mostra_dados_consulta;
        $this->mostra_titulo_padrao = $rowDefMarcacao->mostra_titulo_padrao;
        $this->mostra_link_confirmacao = $rowDefMarcacao->mostra_link_confirmacao;
        $this->mostra_link_desmarcacao = $rowDefMarcacao->mostra_link_desmarcacao;
        $this->mostra_links_sociais = $rowDefMarcacao->mostra_links_sociais;
        $this->dadosLinksSociais = [
            'facebook' => [
                'link' => $this->dadosEmpresa->facebook,
                'icon' => (!empty($this->dadosEmpresa->icone_facebook)) ?
                $this->linkBase . '/arquivos/icones_sociais/' . $this->dadosEmpresa->icone_facebook : $this->linkBase . '/images/icon_facebook.png'
            ],
            'linkedin' => [
                'link' => $this->dadosEmpresa->linkedin,
                'icon' => (!empty($this->dadosEmpresa->icone_linkedin)) ?
                $this->linkBase . '/arquivos/icones_sociais/' . $this->dadosEmpresa->icone_linkedin : $this->linkBase . '/images/icon_linkedin.png'
            ],
            'twitter' => [
                'link' => $this->dadosEmpresa->twitter,
                'icon' => (!empty($this->dadosEmpresa->icone_twitter)) ?
                $this->linkBase . '/arquivos/icones_sociais/' . $this->dadosEmpresa->icone_twitter : $this->linkBase . '/images/icon_twitter.png'
            ],
        ];



        if ($rowDefMarcacao->mostra_link_confirmacao == 1) {
            $this->linkConfirmar = $this->linkBase . "/confirma_por_email.php?c=" . md5($idConsulta);
        }
        if ($rowDefMarcacao->mostra_link_desmarcacao == 1) {
            $this->linkDesmarcar = $this->linkBase . "/desmarcar.php?consulta=" . $hashMD5DaConsulta;
        }
        $this->linkBloqueiaEmail = $this->linkBase . "/unsubscribe.php?c=" . base64_encode($idConsulta) . '&d=' . base64_encode($idDoutor) . '&t=' . base64_encode('p') . '&e=' . base64_encode($email);



        if (!empty($rowDefMarcacao->imagem_assinatura)) {
            $this->imagem_assinatura = "<img src='" . $this->linkBase . "/img/assinatura/" . rawurlencode($rowDefMarcacao->imagem_assinatura) . "' alt=\"Assinatura\"/>";
        }
    }

    /**
     * 
     * @param type $idDominio
     * @param type $tipoEmail - confirmaConsulta
     * @return type
     */
    public function sendEmailAgendamento($idDominio, $idConsulta, $tipoEmail) {



        $this->getConfig($idDominio, $idConsulta, $this->emailPaciente, $this->doutorId);

       
        $mensagem = null;
        $permiteEnvio = false;
        switch ($tipoEmail) {
            case 'confirmacaoConsulta':
                
                $assunto = utf8_decode($this->replaceTags($this->definicoesMarcacaoDoutor->assunto_email_confirmacao_consulta));
           
                if (empty($assunto)) {
                    $assunto = utf8_decode(sprintf("Sua consulta com %s foi agendada com sucesso!", "Dr(a) {$this->nomeDoutor}"));
                }
                $mensagem = $this->replaceTags(utf8_decode($this->definicoesMarcacaoDoutor->email_confirmacao_consulta));
                $permiteEnvio = ($this->definicoesMarcacaoDoutor->email_confirmacao_consulta_ativado == 1) ? true : false;
                break;
            case 'lembrete1':
                $assunto = utf8_decode($this->replaceTags($this->definicoesMarcacaoDoutor->assunto_email_proximidade_consulta));
                $mensagem = $this->definicoesMarcacaoDoutor->email_proximidade_consulta;
                $permiteEnvio = ($this->definicoesMarcacaoDoutor->email_proximidade_consulta_ativado == 1) ? true : false;
//                $atualiza = $this->select("UPDATE consultas SET email_de_proximidade = '1' WHERE id = '{$idConsulta}' LIMIT 1") or die(mysql_error());
                break;
            case 'lembrete2':
                $assunto = utf8_decode($this->replaceTags($this->definicoesMarcacaoDoutor->assunto_email_proximidade_consulta2));
                $mensagem = $this->definicoesMarcacaoDoutor->email_proximidade_consulta2;
                $permiteEnvio = ($this->definicoesMarcacaoDoutor->email_proximidade_consulta2_ativado == 1) ? true : false;
//                $atualiza = $this->select("UPDATE consultas SET email_de_proximidade = '1' WHERE id = '{$idConsulta}' LIMIT 1") or die(mysql_error());
                break;
            case 'desmarcacao':
                $assunto = utf8_decode($this->replaceTags($this->definicoesMarcacaoDoutor->assunto_email_desmarcar_consulta));
                $mensagem = $this->definicoesMarcacaoDoutor->email_desmarcar_consulta;
                $permiteEnvio = ($this->definicoesMarcacaoDoutor->email_desmarcar_consulta_ativado == 1) ? true : false;
//                $atualiza = $this->select("UPDATE consultas SET email_de_proximidade = '1' WHERE id = '{$idConsulta}' LIMIT 1") or die(mysql_error());
                break;
            case 'avaliacao':
                break;
        }


        if ($permiteEnvio) {
            $dados = [
                'exibelinkConfirmar' => (!empty($this->exibelinkConfirmar) and $this->exibelinkConfirmar == true) ? true : false,
                'linkConfirmacao' => $this->linkConfirmar,
                'linkRemarcar' => $this->linkDesmarcar,
                'mostraTituloPadrao' => $this->mostra_titulo_padrao,
                'mostraLinkDesmarcacao' => $this->mostra_link_desmarcacao,
                'mostraDadosConsulta' => $this->mostra_dados_consulta,
                'mostraLinksSociais' => 1, //$this->mostra_links_sociais,
                'linkDesmarcar' => $this->linkDesmarcar,
                'dadosLinksSociais' => $this->dadosLinksSociais,
                'imagem_assinatura' => $this->imagem_assinatura,
                'mensagem' => $mensagem,
                'nomeDoutor' => $this->nomeDoutor,
                'nomeClinica' => $this->dadosEmpresa->nome,
                'linkPagseguro' => (!empty($this->linkPagseguro)) ? $this->linkPagseguro : null,
                'linkVideo' => (!empty($this->linkVideo)) ? $this->linkVideo : null,
                'precoConsulta' => (!empty($this->precoConsulta)) ? number_format($this->precoConsulta, 2, ',', '.') : null,
                'data' => Functions::dateDbToBr($this->dataConsulta),
                'horario' => substr($this->horaConsulta, 0, 5),
            ];
            $emailPaciente = $this->emailPaciente;
            $nomePaciente = $this->nomePaciente;

            $envio = Mail::send('emails.consultaConfiramacaoMail', $dados, function($message) use ($emailPaciente, $nomePaciente, $assunto) {
                        $message->to($emailPaciente, $nomePaciente)->subject($assunto);
                        $message->from('naoresponda@simdoctor.com.br', 'Simdoctor');
                    });
            return $envio;
        } else {
            return false;
        }
    }

}
