<?php

namespace App\Services;

use App\Repositories\Gerenciamento\DominioRepository;

class BaseService {

    protected function returnSuccess($data = null, $message = null) {


        return [
            'success' => true,
            'data' => $data,
            'message' => $message
        ];
    }

    protected function returnError($data = null, $message = null) {
        return [
            'success' => false,
            'data' => $data,
            'message' => $message
        ];
    }

    protected function getPaginate($request) {

        $page = ($request->has('page') and ! empty($request->get('page')) and $request->get('page') > 0) ? $request->get('page') : 1;
        $perPage = ($request->has('perPage') and ! empty($request->get('perPage')) and ( $request->get('perPage') > 0 and $request->get('perPage') <= 1000)) ? $request->get('perPage') : 1000;
        return array(
            'page' => $page,
            'perPage' => $perPage,
        );
    }

    protected function verifyDominioApi() {
        $DominioRep = new DominioRepository;
        $qrVerificaDominio = $DominioRep->getAllByUser(auth()->user()->id);
        if (count($qrVerificaDominio) == 0) {
            return $this->returnError(null, 'LOCATION not found');
        }
        $idsDominio = array_map(function($item) {
            return $item->id;
        }, $qrVerificaDominio);
        return $idsDominio;
    }

    function utf8Fix($msg, $utf8ToAccents = true) {
        $accents = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç", "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç", "º", "ª");
        $utf8 = array("Ã¡", "Ã ", "Ã¢", "Ã£", "Ã¤", "Ã©", "Ã¨", "Ãª", "Ã«", "Ã­", "Ã¬", "Ã®", "Ã¯", "Ã³", "Ã²", "Ã´", "Ãµ", "Ã¶", "Ãº", "Ã¹", "Ã»", "Ã¼", "Ã§", "Ã", "Ã€", "Ã‚", "Ãƒ", "Ã„", "Ã‰", "Ãˆ", "ÃŠ", "Ã‹", "Ã", "ÃŒ", "ÃŽ", "Ã", "Ã“", "Ã’", "Ã”", "Ã•", "Ã–", "Ãš", "Ã™", "Ã›", "Ãœ", "Ã‡", "Âº", "Âª");
        if ($utf8ToAccents) {
            $fix = str_replace($utf8, $accents, $msg);
        } else {
            $fix = str_replace($accents, $utf8, $msg);
        }

        return $fix;
    }

    protected function urlOrderByToFields($mapFieldsUrl, $urlOrderBy) {
        $retorno = null;
        if (!empty($urlOrderBy)) {
            $orderBy = null;
            $camposOrdem = explode(',', $urlOrderBy);
            foreach ($camposOrdem as $chave => $campo) {
                $ordem = explode('.', $campo);
                if (isset($mapFieldsUrl[$ordem[0]])) {
                    $orderBy[$chave] = $mapFieldsUrl[$ordem[0]];
                    if (isset($ordem[1]) and ( $ordem[1] == 'desc' OR $ordem[1] == 'asc')) {
                        $orderBy[$chave] .= ' ' . $ordem[1];
                    }
                }
            }

            if (count($orderBy) > 0) {
                $retorno = implode(',', $orderBy);
            }
        }
        return $retorno;
    }

}
