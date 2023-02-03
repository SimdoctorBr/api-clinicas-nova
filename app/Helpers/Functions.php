<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Helpers;

use DateTime;

/**
 * Description of newPHPClass
 *
 * @author ander
 */
class Functions {

    public static function dateBrToDB($date) {

        return implode('-', array_reverse(explode('/', $date)));
    }

    public static function dateDbToBr($date) {
        return implode('/', array_reverse(explode('-', $date)));
    }

    public static function cpfToNumber($cpf) {
        return str_replace('-', '', str_replace('.', '', $cpf));
    }

    public static function cepToNumber($cpf) {
        return str_replace('-', '', str_replace('.', '', $cpf));
    }

    public static function datetimeToTuotempo($data) {
        $dateF = Functions::dateDbToBr(substr($data, 0, 10));
        $dateF = $dateF . ' ' . substr($data, 11, 8);
        return $dateF;
    }

    public static function dateTuotempoToDB($data) {
        $dateF = Functions::dateBrToDB(substr($data, 0, 10));
        $dateF = $dateF . ' ' . substr($data, 11, 8);
        return $dateF;
    }

    public static function sexoToSigla($sexo) {

        $array = array('Masculino' => 'M', 'Feminino' => 'F');
        if (!empty($sexo)) {
            return $array[$sexo];
        } else {
            return null;
        }
    }

    public static function siglaSexoToNome($sexo) {

        $array = array('M' => 'Masculino', 'F' => 'Feminino');
        if (!empty($sexo)) {
            return $array[$sexo];
        } else {
            return null;
        }
    }

    /**
     * 
     * @param type $valorParcial
     * @param type $valorTotal
     */
    public static function calculaPorcertagemParcial($valorParcial, $valorTotal) {
        $percentual = ($valorParcial * 100) / $valorTotal;
        return $percentual;
    }

    /**
     * 
     * @param type $dataNascimento
     */
    public static function calculaIdade($dataNascimento) {
        $dtIni = new DateTime($dataNascimento);
        $dtAtual = new DateTime(date('Y-m-d H:i:s'));
        $diff = $dtAtual->diff($dtIni);
        return $diff->y;
    }

    public static function utf8Fix($msg, $utf8ToAccents = true) {
        $accents = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç", "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç", "º", "ª");
        $utf8 = array("Ã¡", "Ã ", "Ã¢", "Ã£", "Ã¤", "Ã©", "Ã¨", "Ãª", "Ã«", "Ã­", "Ã¬", "Ã®", "Ã¯", "Ã³", "Ã²", "Ã´", "Ãµ", "Ã¶", "Ãº", "Ã¹", "Ã»", "Ã¼", "Ã§", "Ã", "Ã€", "Ã‚", "Ãƒ", "Ã„", "Ã‰", "Ãˆ", "ÃŠ", "Ã‹", "Ã", "ÃŒ", "ÃŽ", "Ã", "Ã“", "Ã’", "Ã”", "Ã•", "Ã–", "Ãš", "Ã™", "Ã›", "Ãœ", "Ã‡", "Âº", "Âª");
        if ($utf8ToAccents) {
            $fix = str_replace($utf8, $accents, $msg);
        } else {
            $fix = str_replace($accents, $utf8, $msg);
        }

        return $fix;
    }

    public static function accentsToUtf8Convert($msg) {
        $arrayAcentoToUtf8 = array("á" => "Ã¡", "à" => utf8_encode('à'), "â" => "Ã¢", "ã" => "Ã£", "ä" => "Ã¤", "é" => "Ã©", "è" => "Ã¨", "ê" => "Ãª", "ë" => "Ã«", "í" => "Ã­", "ì" => "Ã¬", "î" => "Ã®", "ï" => "Ã¯", "ó" => "Ã³", "ò" => "Ã²", "ô" => "Ã´", "õ" => "Ãµ", "ö" => "Ã¶", "ú" => "Ãº", "ù" => "Ã¹", "û" => "Ã»", "ü" => "Ã¼", "ç" => "Ã§", "Á" => "Ã", "À" => "Ã€", "Â" => "Ã‚", "Ã" => "Ãƒ", "Ä" => "Ã„", "É" => "Ã‰", "È" => "Ãˆ", "Ê" => "ÃŠ", "Ë" => "Ã‹", "Í" => "Ã", "Ì" => "ÃŒ", "Î" => "ÃŽ", "Ï" => "Ã", "Ó" => "Ã“", "Ò" => "Ã’", "Ô" => "Ã”", "Õ" => "Ã•", "Ö" => "Ã–", "Ú" => "Ãš", "Ù" => "Ã™", "Û" => "Ã›", "Ü" => "Ãœ", "Ç" => "Ã‡", "º" => "Âº", "ª" => "Âª");
        return strtr($msg, $arrayAcentoToUtf8);
    }

    public static function utf8ToAccentsConvert($msg) {
        $arrayAcentoToUtf8 = array("Ã¡" => "á", utf8_encode('à') => "à", "Ã¢" => "â", "Ã£" => "ã", "Ã¤" => "ä", "Ã©" => "é", "Ã¨" => "è", "Ãª" => "ê", "Ã«" => "ë", "Ã­" => "í", "Ã¬" => "ì", "Ã®" => "î", "Ã¯" => "ï", "Ã³" => "ó", "Ã²" => "ò", "Ã´" => "ô", "Ãµ" => "õ", "Ã¶" => "ö", "Ãº" => "ú", "Ã¹" => "ù", "Ã»" => "û", "Ã¼" => "ü", "Ã§" => "ç", "Ã" => "Á", "Ã€" => "À", "Ã‚" => "Â", "Ãƒ" => "Ã", "Ã„" => "Ä", "Ã‰" => "É", "Ãˆ" => "È", "ÃŠ" => "Ê", "Ã‹" => "Ë", "Ã" => "Í", "ÃŒ" => "Ì", "ÃŽ" => "Î", "Ã" => "Ï", "Ã“" => "Ó", "Ã’" => "Ò", "Ã”" => "Ô", "Ã•" => "Õ", "Ã–" => "Ö", "Ãš" => "Ú", "Ã™" => "Ù", "Ã›" => "Û", "Ãœ" => "Ü", "Ã‡" => "Ç", "Âº" => "º", "Âª" => "ª");
        return strtr($msg, $arrayAcentoToUtf8);
    }

    public static function calcularDescontoPercentual($valorProc, $tipo, $valorTipoDesconto) {
        $retorno = ($valorProc * ($valorTipoDesconto / 100));
        return $retorno;
    }

    public static function validateDate($date) {

        $date = explode('-', $date);

        if (count($date) != 3) {
            return false;
        }
        return checkdate($date[1], $date[2], $date[0]);
    }

    public static function gerarHorarios($inicio, $termino, $intervalo) {

        $HoraInicio = explode(':', $inicio);
        $HoraTermino = explode(':', $termino);

        $inicio = mktime($HoraInicio[0], $HoraInicio[1], 0);
        $termino = mktime($HoraTermino[0], $HoraTermino[1], 0, date('m'), date('d'), date('Y'));
        $intervalo = $intervalo * 60;

        $cont = 0;
        $horarioAnt = $inicio;
        $HORARIOS[] = date('H:i', $inicio);
        while ($inicio <= $termino) {
            $cont++;
            $horario = $horarioAnt + $intervalo;

            if ($horario > $termino) {

                break;
            }

            $HORARIOS[] = date('H:i', $horario);
            $horarioAnt = $horario;

            if ($cont == 200) {
                break;
                exit;
            }
        }

        return $HORARIOS;
    }

    public static function array_sort($array, $on, $order = SORT_ASC) {
        $new_array = array();
        $sortable_array = array();

        if (count($array) > 0) {
            foreach ($array as $k => $v) {
                if (is_array($v)) {
                    foreach ($v as $k2 => $v2) {
                        if ($k2 == $on) {
                            $sortable_array[$k] = $v2;
                        }
                    }
                } else {
                    $sortable_array[$k] = $v;
                }
            }

            switch ($order) {
                case SORT_ASC:
                    asort($sortable_array);
                    break;
                case SORT_DESC:
                    arsort($sortable_array);
                    break;
            }

            foreach ($sortable_array as $k => $v) {
                $new_array[$k] = $array[$k];
            }
        }

        return $new_array;
    }

    /**
     * 
     * @param type $tipo 1 - SOmente cósigo, 2 Código/Ano
     * @param type $anoCod 
     * @param type $codigo
     * @return string
     */
    public static function codFinanceiroRecDesp($tipo, $anoCod, $codigo) {

        if (empty($anoCod) and empty($codigo)) {
            return '';
        }

        if ($tipo == 1) {
            return $codigo;
        } elseif ($tipo == 2) {
            return $codigo . '/' . $anoCod;
        }
    }

    /**
     * 
     * @param type $tipo 1 - SOmente cósigo, 2 Código/Ano
     * @param type $anoCod 
     * @param type $codigo
     * @return string
     */
    public static function statusConsultas() {

        return [
            'jaSeEncontra',
            'estaSendoAtendido',
            'jaFoiAtendido',
            'faltou',
            'desmarcado',
        ];
    }

    public static function qntProcedimentosDocBizz($procNome, $qnt, $convenioId, $totalRegitros) {

        $retorno['consulta'] = 0;
        $retorno['procedimento'] = 0;
        if (strpos('consulta', strtolower($procNome)) !== false
                and ( $convenioId == 41 OR ( $totalRegitros == 1 and $convenioId != 41))
        ) {
            $retorno['consulta'] = $qnt;
        } else if (strpos('consulta', strtolower($procNome)) === false) {
            $retorno['procedimento'] = $qnt;
        }

        return $retorno;
    }

    public static function correcaoUTF8Decode($texto) {
        if (mb_detect_encoding($texto) == 'UTF-8') {
            $texto = Functions::utf8Fix($texto);
        } else {
            $texto = utf8_decode($texto);
        }
        return $texto;
    }

    public static function limpaTelefone($texto) {

        return str_replace('(', '', str_replace(')', '', str_replace(' ', '', str_replace('-', '', $texto))));
    }

    public static function parcelaMonetaria($valorTotal, $qntParcelas) {
        $somatorioParcela = 0;
        $retorno = [];
        for ($i = 0; $i < $qntParcelas; $i++) {

            $valorParcela = number_format(($valorTotal / $qntParcelas), 2, '.', '');

            if (($qntParcelas - 1) == $i) {
                $retorno[$i] = ($valorTotal - ($somatorioParcela));
            } else {
                $somatorioParcela += $valorParcela;
                $retorno[$i] = $valorParcela;
            }
        }
        return $retorno;
    }

    public static function validateCPF($cpf) {


        // Extrai somente os números
        $cpf = preg_replace('/[^0-9]/is', '', $cpf);

        // Verifica se foi informado todos os digitos corretamente
        if (strlen($cpf) != 11) {
            return false;
        }

        // Verifica se foi informada uma sequência de digitos repetidos. Ex: 111.111.111-11
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }

        // Faz o calculo para validar o CPF
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }
        return true;
    }

}
