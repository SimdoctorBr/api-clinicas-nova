<?php

namespace App\Services\ZIAD_SMS;
class ZIAD {

    private $url = 'https://api.plataformadesms.com.br/v2';
//    private $chaveApi = "A544D9971D8F90EB5A1491C4E0B31E41"; Chave principal
    private $chaveApi = "6DDD18EEB0119DD43E0790421906CF68"; //Chave Simdoctor

    function setChaveApi($chaveApi) {
        $this->chaveApi = $chaveApi;
    }

    private function connect($url, $method, $data = null) {



        $curl = curl_init();

        $paramsCurl = array(
            CURLOPT_URL => $this->url . $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
                "Key: $this->chaveApi"
            ),
        );

        if (!empty($data)) {
            $paramsCurl[CURLOPT_POSTFIELDS] = json_encode($data);
        }

        curl_setopt_array($curl, $paramsCurl);

        $response = curl_exec($curl);


        curl_close($curl);
        return $response;
    }

    public function getSaldo() {

        $url = "/company/balance";

        $response = $this->connect($url, 'GET');
        return json_decode($response);
    }

    /*
     * 
     */

    public function envioAgendado(ZIAD_Campaing $Campaing, Array $Contacts, $identificador) {
        $url = "/message/advanced";


        $data = array(
            "campaign" => array(
                "title" => $Campaing->getTitle(),
                "message" => $Campaing->getMessage(),
                "sendDate" => $Campaing->getSendDate(),
                "sendTime" => $Campaing->getSendTime()
            )
        );


        foreach ($Contacts as $contact) {

            $contatoAr = array(
                "phone" => $contact->getPhone(),
                "message" => $contact->getMessage()
            );
            if (!empty($contact->getId())) {
                $contatoAr['id'] = $contact->getId();
            }
            $data['contacts'][] = $contatoAr;
        }

        

        $response = $this->connect($url, 'POST', $data);
       
        return json_decode($response);
    }

    public function envioUnico(ZIAD_Contact $Contacts) {
        $url = "/message/single";


        $data = array(
            "phone" => $Contacts->getPhone(),
            "message" => $Contacts->getMessage()
        );
        if (!empty($Contacts->getId())) {
            $data['id'] = $Contacts->getId();
        }

        $response = $this->connect($url, 'POST', $data);
        return json_encode($response);
    }

}
