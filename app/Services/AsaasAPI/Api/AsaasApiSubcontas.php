<?php

namespace App\Services\AsaasAPI\Api;


class AsaasApiSubcontas extends AssasApi {

    private $baseUrl = 'accounts';
    private $name;
    private $email;
    private $loginEmail;
    private $cpfCnpj;
    private $birthDate;
    private $companyType;
    private $phone;
    private $mobilePhone;
    private $site;
    private $address;
    private $addressNumber;
    private $complement;
    private $province;
    private $postalCode;
    private $Webhooks = [];

    public function setBaseUrl($baseUrl) {
        $this->baseUrl = $baseUrl;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function setEmail($email) {
        $this->email = $email;
    }

    public function setLoginEmail($loginEmail) {
        $this->loginEmail = $loginEmail;
    }

    public function setCpfCnpj($cpfCnpj) {
        $this->cpfCnpj = $cpfCnpj;
    }

    public function setBirthDate($birthDate) {
        $this->birthDate = $birthDate;
    }

    /**
     * 
     * @param type $companyType 1 - MEI, 2 LIMITED, 3 -INDIVIDUAL, 4 -ASSOCIATION
     */
    public function setCompanyType($idType) {

        $arrayType = [1 => 'MEI', 2 => 'LIMITED', 3 => 'INDIVIDUAL', 4 => 'ASSOCIATION'];
        $this->companyType = $arrayType[$idType];
    }

    public function setPhone($phone) {
        $this->phone = $phone;
    }

    public function setMobilePhone($mobilePhone) {
        $this->mobilePhone = $mobilePhone;
    }

    public function setSite($site) {
        $this->site = $site;
    }

    public function setAddress($address) {
        $this->address = $address;
    }

    public function setAddressNumber($addressNumber) {
        $this->addressNumber = $addressNumber;
    }

    public function setComplement($complement) {
        $this->complement = $complement;
    }

    public function setProvince($province) {
        $this->province = $province;
    }

    public function setPostalCode($postalCode) {
        $this->postalCode = $postalCode;
    }

    public function __construct($ambiente, $apiKey) {
        $this->setAmbiente($ambiente);
        $this->setApiKey($apiKey);
    }

    public function setWebhook($url, $email, $enabled, $interrupted = false, $authToken = null, $type = null) {

        $dados = [
            'url' => $url,
            'email' => $email,
            'apiVersion' => 3,
            'enabled' => ($enabled)?'true':'false',
            'interrupted' =>($interrupted)?'true':'false',
            'authToken' => $authToken,
            'type' => $type,
        ];

        if (!empty($authToken)) {
            $dados['authToken'] = $authToken;
        }
        if (!empty($type)) {
            $dados['type'] = $type;
        }
        $this->Webhooks[] = $dados;
    }

    public function insert() {

        $dados = [
            'name' => $this->name,
            'email' => $this->email,
            'loginEmail' => $this->loginEmail,
            'cpfCnpj' => $this->cpfCnpj,
            'birthDate' => $this->birthDate,
            'companyType' => $this->companyType,
            'phone' => $this->phone,
            'mobilePhone' => $this->mobilePhone,
            'site' => $this->site,
            'address' => $this->address,
            'addressNumber' => $this->addressNumber,
            'complement' => $this->complement,
            'province' => $this->province,
            'postalCode' => $this->postalCode,
            'webhooks' => $this->Webhooks,
        ];
        

        $response = $this->connect("$this->baseUrl", 'POST', $dados);
        return $response;
    }

    /**
     * 
     * @param Array $filtros cpfCnpj,email,name,walletId,offset,limit
     * @return type
     */
    public function getAll(Array $filtros) {

        $dadosFiltro = [];
        foreach ($filtros as $chave => $valor) {

            $dadosFiltro[] = $chave . '=' . $valor;
        }
        $response = $this->connect("$this->baseUrl?" . implode('&', $dadosFiltro), 'GET');
        return $response;
    }

    public function getById($id) {
        $response = $this->connect("$this->baseUrl?id=" . $id, 'GET');
        return $response;
    }
}
