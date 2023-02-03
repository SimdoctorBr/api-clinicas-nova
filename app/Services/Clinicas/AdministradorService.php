<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\Clinicas;

use App\Services\BaseService;
use DateTime;
use App\Repositories\Clinicas\AdministradorRepository;
use App\Repositories\Gerenciamento\DominioRepository;
use App\Repositories\Clinicas\EmpresaRepository;
use App\Repositories\Clinicas\LogomarcaRepository;
use App\Helpers\Functions;
use Illuminate\Support\Facades\Mail;

/**
 * Description of Activities
 *
 * @author ander
 */
class AdministradorService extends BaseService {

    private $administradorRepository;

    public function __construct() {
        $this->administradorRepository = new AdministradorRepository;
    }

    public function alterarSenha($idDominio, $idAdministrador, $novaSenha, $alterSenha = false, $senhaAtual = null) {







        if ($alterSenha) {
            if (empty($senhaAtual)) {
                return $this->returnError(null, "Informe a senha atual");
            }

            $qrVerificaSenha = $this->administradorRepository->verificaSenha($idDominio, $idAdministrador, md5($senhaAtual));
            if (!$qrVerificaSenha) {
                return $this->returnError(null, "Senha atual inválida!");
            }
        }



        $dadosInsert['senha'] = md5($novaSenha);
        $qr = $this->administradorRepository->update($idDominio, $idAdministrador, $dadosInsert);

        return $this->returnSuccess(null, "Senha alterada com sucesso");
    }

    public function esqueciSenha($idDominio = null, $email) {

        $EmpresaRepository = new EmpresaRepository;
        $LogomarcaRepository = new LogomarcaRepository;

        $details = [
            'title' => 'Mail from ItSolutionStuff.com',
            'body' => 'This is for testing email using smtp'
        ];




        $qrBusca = $this->administradorRepository->buscaPorEmail($idDominio, $email);

        $Links = [];

        if (count($qrBusca) > 0) {
            foreach ($qrBusca as $rowUser) {


                $rowEmpresa = $EmpresaRepository->getById($rowUser->identificador);

                $nome = $rowUser->nome;

                $dados['cod_troca_senha'] = substr(mt_rand(), 0, 6);
                $dados['cod_senha_validade'] = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . " +10 minutes"));

                $this->administradorRepository->update($rowUser->identificador, $rowUser->id, $dados);


                $Links[] = array(
                    'clinica' => $rowEmpresa[0]->nome,
                    'codigo' => $dados['cod_troca_senha'],
                );
            }

            $teste = Mail::send('emails.esqueciSenhaApiMail', ['name' => utf8_decode($nome), 'links' => $Links], function($message) use ($email, $nome) {
                        $message->to($email, $nome)->subject('Alteração de senha');
                        $message->from('naoresponda@simdoctor.com.br', 'Simdoctor');
                    });




            return $this->returnSuccess(null, 'E-mail enviado com sucesso.');
        } else {
            return $this->returnError(null, 'E-mail não encontrado.');
        }
    }

    public function esqueciSenhaVerificaCodigo($idDominio = null, $email, $codigo, $senha) {

        $qr = $this->administradorRepository->buscaPorEmail($idDominio, $email, $codigo);


        if (count($qr) > 0) {


            $dadosUpdate['senha'] = md5($senha);
            $dadosUpdate['cod_troca_senha'] = null;
            $dadosUpdate['cod_senha_validade'] = null;
            $this->administradorRepository->update($qr[0]->identificador, $qr[0]->id, $dadosUpdate);

            return $this->returnSuccess([
                        'id' => $qr[0]->id,
                        'nome' => $qr[0]->nome,
                        'email' => $qr[0]->email,
                            ], 'Senha alterada com successo');
        } else {
            return $this->returnError(null, 'Código inválido.');
        }
    }

}
