<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\Clinicas\Utils;

use App\Services\BaseService;
use Illuminate\Http\UploadedFile;
use App\Repositories\Clinicas\EmpresaRepository;
use App\Repositories\Clinicas\LogomarcaRepository;
use App\Repositories\Gerenciamento\DominioRepository;
use Illuminate\Support\Facades\Mail;

/**
 * Description of Activities
 *
 * @author ander
 */
class EmailService extends BaseService {

    private function getFormatoDadosEndereco($rowEmpresa) {
        $dadosEndereco = [];

        if (!empty($rowEmpresa->logradouro)) {
            $dadosEndereco[] = $rowEmpresa->logradouro;
        }
        if (!empty($rowEmpresa->complemento)) {
            $dadosEndereco[] = $rowEmpresa->complemento;
        }
        if (!empty($rowEmpresa->bairro)) {
            $dadosEndereco[] = $rowEmpresa->bairro;
        }
        if (!empty($rowEmpresa->cidade)) {
            $dadosEndereco[] = $rowEmpresa->cidade;
        }
        if (!empty($rowEmpresa->estado)) {
            $dadosEndereco[] = $rowEmpresa->estado;
        }
        if (!empty($rowEmpresa->cep)) {
            $dadosEndereco[] = $rowEmpresa->cep;
        }

        return implode(', ', $dadosEndereco);
    }

    public function enviaEmailTemplatePadrao($idDominio, $nomeDestino, $emailDestino, $assunto, $msg) {
        $DominioRepository = new DominioRepository();
        $rowDominio = $DominioRepository->getById($idDominio);

        $EmpresaRepository = new EmpresaRepository();
        $rowEmpresa = $EmpresaRepository->getById($idDominio);
        $rowEmpresa = $rowEmpresa[0];
        $LogomarcaRepository = new LogomarcaRepository();
        $rowLogomarca = $LogomarcaRepository->getLogomarca($idDominio);

        $imgLogo = ($rowLogomarca) ? '<br><br><img style="max-width:200px;" src="' . env('APP_URL_CLINICAS') . $rowDominio->dominio . '/arquivos/logomarca/' . rawurlencode($rowLogomarca->logo_nome) . '" alt="Assinatura"/>' : '';
//
        $assunto2 = $rowEmpresa->nome . ' - ' . $assunto;
        $msg .= '<br><b>' . utf8_decode($rowEmpresa->nome) . '</b><br>';
        $msg .= $imgLogo;

        $msg .= '<br>' . utf8_decode($this->getFormatoDadosEndereco($rowEmpresa));
        if (!empty($rowEmpresa->telefones)) {
            $telefones = str_replace(';', ', ', $rowEmpresa->telefones);
            $msg .= '<br><span style="font-weight:bold;">Telefone:</span> ' . $telefones;
        }



        $dados = [
            'nomePaciente' => $nomeDestino,
            'mensagem' => $msg,
        ];

        try {
            $envio = Mail::send('emails.codigoConfirmacao', $dados, function ($message) use ($emailDestino, $nomeDestino, $assunto) {
                        $message->to($emailDestino, $nomeDestino)->subject($assunto);
                        $message->from('naoresponda@simdoctor.com.br', 'Simdoctor');
                    });
            return true;
        } catch (\Exception $exc) {
//                echo $exc->getTraceAsString();
//                dd($exc);
            return false;
        }
    }
}
