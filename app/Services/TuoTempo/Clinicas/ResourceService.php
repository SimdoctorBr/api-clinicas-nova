<?php

namespace App\Services\TuoTempo\Clinicas;

use App\Services\BaseService;
use App\Repositories\Clinicas\DoutorRepository;
use App\Repositories\Gerenciamento\DominioRepository;
use App\Helpers\Functions;

class ResourceService extends BaseService {

    private $doutorRepository;
    private $dominioRepository;

    public function __construct(DoutorRepository $doutRep, DominioRepository $domRep) {
        $this->doutorRepository = $doutRep;
        $this->dominioRepository = $domRep;
    }

    public function getAll($request) {

        $convenioId = (!empty($request->get('INSURANCE_LID'))) ? $request->get('INSURANCE_LID') : null;
        $convArray = explode('-', $convenioId);
        $verificaDominio = $this->verifyDominioApi();

        $idsDominio = ($request->has('LOCATION_LID') and!empty($request->get('LOCATION_LID'))) ? trim(addslashes($request->get('LOCATION_LID'))) : null;


        if (!empty($idsDominio) and!in_array($idsDominio, $verificaDominio)) {
            return $this->returnError('', 'LOCATION_LID not found');
        } else {
            $idsDominio = $verificaDominio;
        }


        if (count($convArray) == 2 and!in_array($idsDominio, $verificaDominio)) {
            $idsDominio = $convArray[0];
            $convenioId = $convArray[1];
        }




        if (!empty($convenioId)) {
            $qrDoutor = $this->doutorRepository->getAllByConvenioId($idsDominio, $convenioId);
        } else {
            $qrDoutor = $this->doutorRepository->getAllById($idsDominio);
        }


        if (count($qrDoutor) > 0) {

            $DOUTOR = array();
            foreach ($qrDoutor as $chave => $row) {

                if (!empty($convenioId)) {
                    $resourceID = 'dout' . $row->doutores_id;
                } else {
                    $resourceID = 'dout' . $row->id;
                }

                $DOUTOR[$chave]['RESOURCE_LID'] = $resourceID;
                $DOUTOR[$chave]['FIRST_NAME'] = $row->nomeDoutor;
                $DOUTOR[$chave]['SECOND_NAME'] = null;
                $DOUTOR[$chave]['MOBILE_PHONE'] = $row->celular1;
                $DOUTOR[$chave]['EMAIL'] = $row->email;
                $DOUTOR[$chave]['ID_NUMBER'] = (!empty(trim($row->cpf))) ? Functions::cpfToNumber(trim($row->cpf)) : null;
                $DOUTOR[$chave]['REGISTRATION_CODE'] = $row->conselho_profissional_numero;
                $DOUTOR[$chave]['REGISTRATION_CODE_REGION'] = $row->siglaUFConselhoProfisional;
                $DOUTOR[$chave]['NOTICE'] = '';
                $DOUTOR[$chave]['LOCATION_LID'] = $row->identificador;
                $DOUTOR[$chave]['WEB_ENABLED'] = $row->mostra_doutor_tuotempo;
            }



            return $this->returnSuccess($DOUTOR);
        } else {
            return $this->returnError(null, 'No resources found');
        }

        dd($qrDoutor);
    }

}
