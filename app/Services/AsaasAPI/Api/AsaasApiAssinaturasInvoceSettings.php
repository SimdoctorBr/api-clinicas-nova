<?php


namespace App\Services\AsaasAPI\Api;

class AsaasApiAssinaturasInvoceSettings {

    private $municipalServiceId;
    private $municipalServiceCode;
    private $municipalServiceName;
    private $updatePayment;
    private $deductions;
    private $effectiveDatePeriod;
    private $receivedOnly;
    private $daysBeforeDueDate;
    private $observations;
    private $taxes;

    public function setMunicipalServiceId($municipalServiceId) {
        $this->municipalServiceId = $municipalServiceId;
    }

    public function setMunicipalServiceCode($municipalServiceCode) {
        $this->municipalServiceCode = $municipalServiceCode;
    }

    public function setMunicipalServiceName($municipalServiceName) {
        $this->municipalServiceName = $municipalServiceName;
    }

    public function setUpdatePayment($updatePayment) {
        $this->updatePayment = $updatePayment;
    }

    public function setDeductions($deductions) {
        $this->deductions = $deductions;
    }

    public function setEffectiveDatePeriod($effectiveDatePeriod) {
        $this->effectiveDatePeriod = $effectiveDatePeriod;
    }

    public function setReceivedOnly($receivedOnly) {
        $this->receivedOnly = $receivedOnly;
    }

    public function setDaysBeforeDueDate($daysBeforeDueDate) {
        $this->daysBeforeDueDate = $daysBeforeDueDate;
    }

    public function setObservations($observations) {
        $this->observations = $observations;
    }

    public function getMunicipalServiceId() {
        return $this->municipalServiceId;
    }

    public function getMunicipalServiceCode() {
        return $this->municipalServiceCode;
    }

    public function getMunicipalServiceName() {
        return $this->municipalServiceName;
    }

    public function getUpdatePayment() {
        return $this->updatePayment;
    }

    public function getDeductions() {
        return $this->deductions;
    }

    public function getEffectiveDatePeriod() {
        return $this->effectiveDatePeriod;
    }

    public function getReceivedOnly() {
        return $this->receivedOnly;
    }

    public function getDaysBeforeDueDate() {
        return $this->daysBeforeDueDate;
    }

    public function getObservations() {
        return $this->observations;
    }

    public function getTaxes() {
        return $this->taxes;
    }

    public function setTaxas($recolheIss, $iss, $confins, $csll, $inss, $ir, $pis) {

        $this->taxes = [
            'retainIss' => $recolheIss,
            'iss' => $iss,
            'confins' => $confins,
            'csll' => $csll,
            'inss' => $inss,
            'ir' => $ir,
            'pis' => $pis,
        ];
        
    }

}
