<?php

namespace App\Services\AsaasAPI\Api;

/**
 * Description of AsaasApi
 *
 * @author ander
 */
class AsaasApiCommons {

    public static function sistemaToTipoPagamentoAsaas($tipo) {

        switch ($tipo) {
            case 'boleto': return 'BOLETO';
                break;
            case 'cartao_credito': return 'CREDIT_CARD';
                break;
            case 'pix': return 'PIX';
                break;
            default :
                return 'UNDEFINED';
                break;
        }
    }

    public static function sistemaToTipoPeriodoApiAsaas($periodo) {

        switch ($periodo) {
            case 'semanal': return 'WEEKLY';
                break;
            case 'quinzenal': return 'BIWEEKLY';
                break;
            case 'mensal': return 'MONTHLY';
                break;
            case 'semestral': return 'SEMIANNUALLY';
                break;
            case 'anual': return 'YEARLY';
                break;
        }
    }

    public static function sistemaToTipoDescontoApiAsaas($tipo) {

        switch ($tipo) {
            case 'fixo': return 'FIXED';
                break;
            case 'percentual': return 'PERCENTAGE';
                break;
        }
    }

    public static function assasTipoPagamentoToSistema($tipo) {

        switch ($tipo) {
            case 'BOLETO': return 'Boleto';
                break;
            case 'CREDIT_CARD': return 'cartao_credito';
                break;
            case 'PIX': return 'Pix';
                break;
            default :
                return 'Indefinido';
                break;
        }
    }

    public static function assasTipoPeriodoApiToSistema($periodo) {

        switch ($periodo) {
            case 'WEEKLY': return 'Semanal';
                break;
            case 'BIWEEKLY': return 'Quinzenal';
                break;
            case 'MONTHLY': return 'Mensal';
                break;
            case 'SEMIANNUALLY': return 'Semestral';
                break;
            case 'YEARLY': return 'Anual';
                break;
        }
    }

    public static function assasTipoDescontoApiToSistema($tipo) {

        switch ($tipo) {
            case 'FIXED': return 'Fixo';
                break;
            case 'PERCENTAGE': return 'Percentual';
                break;
        }
    }

}
