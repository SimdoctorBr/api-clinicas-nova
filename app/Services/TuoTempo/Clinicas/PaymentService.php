<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\TuoTempo\Clinicas;

use App\Services\BaseService;
use App\Repositories\Gerenciamento\DominioRepository;
use App\Helpers\Functions;
use App\Repositories\Clinicas\ConsultaRepository;
use App\Repositories\Clinicas\ConsultaStatusRepository;
use App\Repositories\Clinicas\RecebimentosRepository;
use App\Repositories\Clinicas\FinanceiroFornecedorRepository;
use DateTime;

/**
 * Description of Activities
 *
 * @author ander
 */
class PaymentService extends BaseService {

    private $agendaRepository;
    private $dominioRepository;
    private $definicaoHorarioService;
    private $doutorRepository;
    private $bloqueioAgendaService;
    private $consultaRepository;
    private $pacienteRepository;
    private $convenioRepository;
    private $consultaService;
    private $statusRefreshRepository;

    public function __construct(DominioRepository $domRep, ConsultaRepository $consRep) {
        $this->dominioRepository = $domRep;
        $this->consultaRepository = $consRep;
    }

    public function notificarPagamentoConsulta($request) {

        $RESULTS = array(
            'NOTIFY_RESULT' => null,
            'DOCUMENT_LID' => null,
            'ERROR_MESSAGE' => null,
        );


        if (!$request->has('APP_LID') or empty($request->get('APP_LID'))) {
            $RESULTS['NOTIFY_RESULT'] = 'ERROR';
            $RESULTS['ERROR_MESSAGE'] = 'APP_LID uninformed';
            return $this->returnError($RESULTS, null);
        }
        if (!$request->has('ACTION') or empty($request->get('ACTION'))) {
            $RESULTS['NOTIFY_RESULT'] = 'ERROR';
            $RESULTS['ERROR_MESSAGE'] = 'ACTION uninformed';
            return $this->returnError($RESULTS, null);
        }
        if ($request->get('ACTION') != 'ADD' and $request->get('ACTION') != 'CANCEL') {
            $RESULTS['NOTIFY_RESULT'] = 'ERROR';
            $RESULTS['ERROR_MESSAGE'] = 'Invalid ACTION';
            return $this->returnError($RESULTS, null);
        }

        $qrDominios = $this->dominioRepository->getAllByUser(auth()->user()->id);
        $IDsDominio = array_map(function($item) {
            return $item->id;
        }, $qrDominios);

        $idConsulta = $request->get('APP_LID');
        $valor = $request->get('AMOUNT');
        $idPagamentoExt = $request->get('EXT_ID');
        $dataPagamento = $request->get('PAYED');
        $dataPagamento = Functions::dateBrToDB(substr($dataPagamento, 0, 10)) . ' ' . (substr($dataPagamento, 11, 8));

//        dd($dataPagamento);

        $rowConsulta = $this->consultaRepository->getById($IDsDominio, $idConsulta);

        if (!$rowConsulta) {
            $RESULTS['NOTIFY_RESULT'] = 'ERROR';
            $RESULTS['ERROR_MESSAGE'] = 'APP_LID not found';
            return $this->returnError($RESULTS, null);
        }

        ///verificando valor pago
        $qrConsultasProc = $this->consultaRepository->getConsultasProcedimentos($IDsDominio, $idConsulta);
        $valorTotal = array_sum(array_map(function($item) {
                    return $item->valor_proc;
                }, $qrConsultasProc));

        if ($valorTotal != $valor) {
            $RESULTS['NOTIFY_RESULT'] = 'ERROR';
            $RESULTS['ERROR_MESSAGE'] = 'The amount paid is different from the appointment price';
            return $this->returnError($RESULTS, null);
        }
        $Fornecedor = new FinanceiroFornecedorRepository;
        $rowFornecedorPadrao = $Fornecedor->getFornecedorPadrao($rowConsulta->identificador);



        $RecebimentosRepository = new RecebimentosRepository;
        $qrVErifcaPAg = $RecebimentosRepository->getByConsultaId($rowConsulta->identificador, $idConsulta);

        if (count($qrVErifcaPAg) == 0) {

            $dadosRecebimento['recebido_de'] = 1;
            $dadosRecebimento['pagar_com_adm_banco'] = (!empty($rowFornecedorPadrao->id)) ? $rowFornecedorPadrao->id : ''; //PEgar banco padrao
            $dadosRecebimento['recebimento_competencia'] = substr($dataPagamento, 0, 10);
            $dadosRecebimento['recebimento_data'] = substr($dataPagamento, 0, 10);
            $dadosRecebimento['recebimento_data_vencimento'] = substr($dataPagamento, 0, 10);
            $dadosRecebimento['identificador'] = $rowConsulta->identificador;
            $dadosRecebimento['consulta_id'] = $idConsulta;
            $dadosRecebimento['tipo_pag_id'] = 51; //Tuotempo
            $dadosRecebimento['pago'] = '1';
            $dadosRecebimento['periodo_repeticao_id'] = '1';
            $dadosRecebimento['recebimento_valor'] = $valor;
            $dadosRecebimento['valor_recebido'] = $valor;
            $dadosRecebimento['valor_total_procedimento'] = $valor;
            $dadosRecebimento['total_recebimento'] = $valor;

            $idRecebimento = $RecebimentosRepository->insereRecebimento($rowConsulta->identificador, $dadosRecebimento);

            $RESULTS['NOTIFY_RESULT'] = 'OK';
            $RESULTS['DOCUMENT_LID'] = $idRecebimento;
            return $this->returnSuccess($RESULTS);
        } else {
            $RESULTS['NOTIFY_RESULT'] = 'ERROR';
            $RESULTS['DOCUMENT_LID'] = $qrVErifcaPAg[0]->idRecebimento;
            $RESULTS['ERROR_MESSAGE'] = 'Appointment already paid';
            return $this->returnError($RESULTS);
        }
    }

    public function buscarPagamento($request) {


        if (!$request->has('APP_LID') or empty($request->get('APP_LID'))) {
            return $this->returnError(null, "APP_LID uninformed");
        }

//        if (!$request->has('DOCUMENT_LID') or empty($request->get('DOCUMENT_LID'))) {
//            return $this->returnError(null, "DOCUMENT_LID uninformed");
//        }

        $qrDominios = $this->dominioRepository->getAllByUser(auth()->user()->id);
        $IDsDominio = $nomeDominios = null;
        foreach ($qrDominios as $rowDom) {
            $IDsDominio[] = $rowDom->id;
            $nomeDominios[$rowDom->id] = $rowDom->dominio;
        }



        $RESULT = array();
        $RecebimentosRepository = new RecebimentosRepository;
        $qrRecebimento = $RecebimentosRepository->getByConsultaId($IDsDominio, $request->get('APP_LID'), $request->get('DOCUMENT_LID'));
        if (count($qrRecebimento) > 0) {
            foreach ($qrRecebimento as $rowRecebimento) {


                $codREcibo = base64_encode(base64_encode($rowRecebimento->idRecebimento . '_' . $request->get('APP_LID')));
                $codDom = md5($rowRecebimento->identificador . '_' . $nomeDominios[$rowRecebimento->identificador]);

                $RESULT['DOCUMENT_LID'] = $rowRecebimento->idRecebimento;
                $RESULT['DOCUMENT_NUMBER'] = $rowRecebimento->idRecebimento;
                $RESULT['DOCUMENT_DATE'] = $rowRecebimento->recebimento_data;
                $RESULT['ADDRESS'] = utf8_decode($rowRecebimento->logradouro . ' ' . $rowRecebimento->complemento);
                $RESULT['ZIPCODE'] = $rowRecebimento->cep;
                $RESULT['CITY'] = utf8_decode($rowRecebimento->cidade);
                $RESULT['PROVINCE'] = utf8_decode($rowRecebimento->estado);
                $RESULT['ID_NUMBER'] = $rowRecebimento->cpfPaciente;
                $RESULT['DOCUMENT_TYPE'] = 'invoice';
                $RESULT['DOCUMENT_AMOUNT'] = $rowRecebimento->recebimento_valor;
                $RESULT['DOCUMENT_TAX_AMOUNT'] = 0;
                $RESULT['DOCUMENT_CONTENT'] = 'https://app.simdoctor.com.br/' . $nomeDominios[$rowRecebimento->identificador] . '/admin/financeiro/recibo_print_pdf.php?t=' . $codREcibo . '&d=' . $codDom;

                return $this->returnSuccess($RESULT);
            }
        } else {
            return $this->returnError(null, 'No payments registred');
        }
    }

}
