<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\TuoTempo\Clinicas;

use App\Services\BaseService;

/**
 * Description of Activities
 *
 * @author ander
 */
class CryptService extends BaseService {

    private $ciphering = "AES-128-CTR";
    private $encryption_iv = 'e63b9c72fd9dc84c';
    private $encryption_key = "simdoctor2021##";

    public function encrypt($string) {

        $encryption = openssl_encrypt($string, $this->ciphering, $this->encryption_key, 0, $this->encryption_iv);

        return $encryption;
    }

    public function decrypt($stringEncrypted) {
        $decryption = openssl_decrypt($stringEncrypted, $this->ciphering, $this->encryption_key, 0, $this->encryption_iv);
        return $decryption;
    }

}
