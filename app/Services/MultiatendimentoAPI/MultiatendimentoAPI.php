<?php
namespace App\Services\MultiatendimentoAPI;
/*
 * 
  /**
 * Description of MultiatendimentoAPI
 *
 * @author ander
 */

class MultiatendimentoAPI {

    private $url = 'https://api.multidialogo.com.br/api/';
    private $token;

    public function setToken($token) {
        $this->token = $token;
    }

    public function connectApiMulti($url, $method = 'GET', $dadosPost = null, $multipart = false) {

        $arrayMethods = ['post', 'POST', 'PUT', 'put', 'delete', 'DELETE'];

        $curl = curl_init($this->url . $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        if (in_array($method, $arrayMethods)) {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);

            if ($method == 'POST') {
                curl_setopt($curl, CURLOPT_POST, 1);
            }

            if ($multipart) {

                curl_setopt($curl, CURLOPT_POSTFIELDS, ($dadosPost));
            } else {
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($dadosPost));
            }
        }

        $Headers[] = "Authorization: Bearer $this->token";
        if ($multipart) {
            $Headers[] = "Content-Type:multipart/form-data";
        } else {
            $Headers[] = "Content-Type:application/json";
        }



        curl_setopt($curl, CURLOPT_HTTPHEADER, $Headers);

        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response);
    }

    public function sendMsgTxt($number, $msg) {
        $dados['number'] = $number;
        $dados['body'] = $msg;
        return $result = $this->connectApiMulti('messages/send', 'POST', $dados);
    }

    public function sendMsgMedia($number, $pathArquivo, $nameFile = null) {

//        $dataFile = fopen($pathArquivo, 'rb');
//        $size = filesize($pathArquivo);
//        $contents = fread($dataFile, $size);
//        fclose($dataFile);
//        var_dump($contents);
        if (!empty($nameFile)) {
//            $dados['body'] = ;
        }

        $dados['number'] = $number;
//        $dados['medias'] = $pathArquivo;
        $dados['medias'] = curl_file_create($pathArquivo);
        return $result = $this->connectApiMulti('messages/send', 'POST', $dados, true);
    }


}
