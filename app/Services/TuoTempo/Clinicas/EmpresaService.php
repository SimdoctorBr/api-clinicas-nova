<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\TuoTempo\Clinicas;

use App\Repositories\Clinicas\EmpresaRepository;
use App\Repositories\Gerenciamento\DominioRepository;
use App\Services\BaseService;

class EmpresaService extends BaseService {

    private $empresaRepository;
    private $dominioRepository;

    public function __construct(EmpresaRepository $emp, DominioRepository $domRep) {
        $this->empresaRepository = $emp;
        $this->dominioRepository = $domRep;
    }

    public function getAllByUser($request) {


        $dominioId = (!empty($request->get('LOCATION_LID'))) ? $request->get('LOCATION_LID') : null;
        $qrEmpresas = $this->dominioRepository->getAllByUser(auth()->user()->id, $dominioId);


        if (count($qrEmpresas) > 0) {
            $return = null;
            $i = 0;

            foreach ($qrEmpresas as $row) {

                $rowDados = $this->empresaRepository->getById($row->id);

                if (!empty($rowDados)) {
                    $rowDados = $rowDados[0];



                    $return[$i]['LOCATION_LID'] = $row->id;
                    $return[$i]['NAME'] = $rowDados->nome;
                    $return[$i]['ADDRESS'] = $rowDados->logradouro;
                    $return[$i]['ZIP_CODE'] = $rowDados->cep;
                    $return[$i]['CITY'] = utf8_decode($rowDados->cidade);
                    $return[$i]['PROVINCE'] = $rowDados->bairro;
                    $return[$i]['REGION'] = utf8_decode($rowDados->estado);
                    $return[$i]['COUNTRY'] = 'Brasil';
                    $return[$i]['PHONE'] = trim(str_replace('  ', ' ', str_replace(';', ' ', $rowDados->telefones)));
                    $return[$i]['EMAIL'] = $rowDados->email;
                    $return[$i]['WEB_ENABLED'] = ($row->habilita_tuotempo == 1) ? $row->habilita_tuotempo : 0;
                    $i++;
                }
            }

            return $this->returnSuccess($return);
        } else {
            return $this->returnError("LOCATION_LID not found", "LOCATION_LID not found");
        }
    }

    public function getById($request) {


        $userId = auth()->user()->id;

        dd($request);
        $dominioId = (!empty($request->get('LOCATION_LID'))) ? $request->get('LOCATION_LID') : null;


        $qrEmpresas = $this->dominioRepository->getAllByUser($userId, $dominioId);
        if (count($qrEmpresas) > 0) {
            $return = null;
            $i = 0;

            foreach ($qrEmpresas as $row) {
                $rowDados = $this->empresaRepository->getById($row->id);
                $rowDados = $rowDados[0];
                $return[$i]['LOCATION_LID'] = $row->id;
                $return[$i]['NAME'] = $rowDados->nome;
                $return[$i]['ADDRESS'] = $rowDados->logradouro;
                $return[$i]['ZIP_CODE'] = $rowDados->cep;
                $return[$i]['CITY'] = $rowDados->cidade;
                $return[$i]['PROVINCE'] = $rowDados->bairro;
                $return[$i]['REGION'] = utf8_decode($rowDados->estado);
                $return[$i]['COUNTRY'] = 'Brasil';
                $return[$i]['PHONE'] = trim(str_replace('  ', ' ', str_replace(';', ' ', $rowDados->telefones)));
                $return[$i]['EMAIL'] = $rowDados->email;
                $return[$i]['WEB_ENABLED'] = 0;
                $i++;
            }

            return $this->returnSuccess($return);
        } else {
            return $this->returnError('null', 'Empresa não encontrada');
        }
    }

}
