<?php
namespace Application\Entity;

class Amount {
    protected $amount;

    protected $currency;

    // amount getter and setter
    public function getValue()
    {
        return $this->amount;
    }

    public function setValue($value)
    {
        $this->amount = $value;
    }

    // currency getter and setter
    public function getCurrency()
    {
        return $this->currency;
    }

    public function setCurrency($value)
    {
        $this->currency = $value;
    }

}