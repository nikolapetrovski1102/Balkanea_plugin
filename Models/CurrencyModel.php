<?php

namespace Models;

class CurrencyModel
{

    private $currency;
    private $multiplier;
    private $operation;

    function __construct($currency){
        $this->currency = $currency;
        $this->multiplier = self::getMultiplierSwitch();
        $this->operation = self::getOperation();
    }

    public function getCurrency(){
        return $this->currency;
    }

    public function getOperation(){
        return $this->operation;
    }

    public function getMultiplier(){
        return $this->multiplier;
    }

    public function setCurrency($currency){
        $this->currency = $currency;
        $this->multiplier = self::getMultiplierSwitch();
        $this->operation = self::getOperationSwitch();
    }

    private function getOperationSwitch(){
        switch ($this->currency) {
            case 'EUR':
                return '/';
            case 'MKD':
                return '*';
            default:
                return 1;
        }
    }

    public function getMultiplierSwitch(){
        return $this->multiplier;
    }

    private function getMultiplierSwitch(){
        switch ($this->currency) {
            case 'EUR':
                return 61.53;
            case 'MKD':
                return 61.53;
            default:
                return 1;
        }
    }

}

?>