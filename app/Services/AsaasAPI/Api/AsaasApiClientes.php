<?php

namespace App\Services\AsaasAPI\Api;

use App\Services\AsaasAPI\Api\AsaasApi;

class AsaasApiClientes extends AsaasApi {

    private $name;
    private $email;
    private $phone;
    private $mobilePhone;
    private $cpfCnpj;
    private $postalCode;
    private $address;
    private $addressNumber;
    private $complement;
    private $province;
    private $externalReference;
    private $notificationDisabled;
    private $additionalEmails;
    private $municipalInscription;
    private $stateInscription;
    private $observations;
    private $groupName;

    
    public function setGroupName($groupName) {
        $this->groupName = $groupName;
    }

        public function setName($name) {
        $this->name = $name;
    }

    public function setEmail($email) {
        $this->email = $email;
    }

    public function setPhone($phone) {
        $this->phone = $phone;
    }

    public function setMobilePhone($mobilePhone) {
        $this->mobilePhone = $mobilePhone;
    }

    public function setCpfCnpj($cpfCnpj) {
        $this->cpfCnpj = $cpfCnpj;
    }

    public function setPostalCode($postalCode) {
        $this->postalCode = $postalCode;
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

    public function setExternalReference($externalReference) {
        $this->externalReference = $externalReference;
    }

    public function setNotificationDisabled($notificationDisabled) {
        $this->notificationDisabled = $notificationDisabled;
    }

    public function setAdditionalEmails($additionalEmails) {
        $this->additionalEmails = $additionalEmails;
    }

    public function setMunicipalInscription($municipalInscription) {
        $this->municipalInscription = $municipalInscription;
    }

    public function setStateInscription($stateInscription) {
        $this->stateInscription = $stateInscription;
    }

    public function setObservations($observations) {
        $this->observations = $observations;
    }

    public function __construct($ambiente, $apiKey) {
        $this->setAmbiente($ambiente);
        $this->setApiKey($apiKey);
    }

    /**
     * Listar pacientes
     * Filtros: name,email, cpfCnpj,groupName,externalReference,offset,limit
     */
    public function list(Array $urlFiltros = []) {

        $queryParams = [];
        if (count($urlFiltros) > 0) {
            foreach ($urlFiltros as $campo => $valor) {
                $queryParams[] = "$campo=" . trim($valor);
            }
        }
        $queryParams = (count($queryParams) > 0) ? '?' . implode('&', $queryParams) : '';
        $response = $this->connect('customers' . $queryParams, 'GET');
        return $response;
    }

    /**
     */
    public function insert() {

        $dadosCliente['name'] = $this->name;
        $dadosCliente['cpfCnpj'] = $this->cpfCnpj;
        $dadosCliente['phone'] = $this->phone;
        $dadosCliente['email'] = $this->email;
        $dadosCliente['mobilePhone'] = $this->mobilePhone;
        $dadosCliente['address'] = $this->address;
        $dadosCliente['addressNumber'] = $this->addressNumber;
        $dadosCliente['complement'] = $this->complement;
        $dadosCliente['province'] = $this->province;
        $dadosCliente['postalCode'] = $this->postalCode;
        $dadosCliente['externalReference'] = $this->externalReference;
        $dadosCliente['notificationDisabled'] = $this->notificationDisabled;
        $dadosCliente['additionalEmails'] = $this->additionalEmails;
        $dadosCliente['municipalInscription'] = $this->municipalInscription;
        $dadosCliente['stateInscription'] = $this->stateInscription;
        $dadosCliente['groupName'] = $this->groupName;
        $response = $this->connect('customers', 'POST', $dadosCliente);

        return $response;
    }

    public function getById($customerId) {

        $response = $this->connect("customers/$customerId");
        return $response;
    }

    /*     * Atualiza todo o cadastro
     * 
     * @return type
     */

    public function update($customerId) {

        $dadosCliente['name'] = $this->name;
        $dadosCliente['cpfCnpj'] = $this->cpfCnpj;
        $dadosCliente['email'] = $this->email;
        $dadosCliente['phone'] = $this->phone;
        $dadosCliente['mobilePhone'] = $this->mobilePhone;
        $dadosCliente['address'] = $this->address;
        $dadosCliente['addressNumber'] = $this->addressNumber;
        $dadosCliente['complement'] = $this->complement;
        $dadosCliente['province'] = $this->province;
        $dadosCliente['postalCode'] = $this->postalCode;
        $dadosCliente['externalReference'] = $this->externalReference;
        $dadosCliente['notificationDisabled'] = $this->notificationDisabled;
        $dadosCliente['additionalEmails'] = $this->additionalEmails;
        $dadosCliente['municipalInscription'] = $this->municipalInscription;
        $dadosCliente['stateInscription'] = $this->stateInscription;
        $dadosCliente['groupName'] = $this->groupName;
        $response = $this->connect("customers/$customerId", 'POST', $dadosCliente);
        return $response;
    }

    /*     * Atualiza somente alguns campos
     * 
     */

    public function updateFields($customerId, Array $dadosUpdate) {
        $response = $this->connect("customers/$customerId", 'POST', $dadosUpdate);
        return $response;
    }

    public function delete($customerId) {
        $response = $this->connect("customers/$customerId", 'DELETE');
        return $response;
    }

    public function restore($customerId) {
        $response = $this->connect("customers/$customerId/restore", 'DELETE');
        return $response;
    }

}
