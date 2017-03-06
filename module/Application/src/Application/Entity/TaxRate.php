<?php
namespace Application\Entity;
use Doctrine\ORM\Mapping as ORM;
/** @ORM\Entity */
class TaxRate {
    /**
    * @ORM\Id
    * @ORM\GeneratedValue(strategy="AUTO")
    * @ORM\Column(type="integer")
    */
    protected $id;
    
    /** @ORM\Column(type="string") */
    protected $tax_rate_name;
    
    /** @ORM\Column(type="string") */
    protected $tax_rate_id;
    
    /** @ORM\Column(type="string") */
    protected $user_id;
    
    /** @ORM\Column(type="string") */
    protected $rate_value;
    
    /** @ORM\Column(type="string") */
    protected $tax_agency_id;
    
    /** @ORM\Column(type="string") */
    protected $tax_applicable_on;
    
    // id getter and setter
    public function getId()
    {
        return $this->id;
    }

    public function setId($value)
    {
        $this->id = $value;
    }
    
    // tax_rate_name getter and setter
    public function getTaxRateName()
    {
        return $this->tax_rate_name;
    }

    public function setTaxRateName($value)
    {
        $this->tax_rate_name = $value;
    }
    
    // tax_rate_id getter and setter
    public function getTaxRateId()
    {
        return $this->tax_rate_id;
    }

    public function setTaxRateId($value)
    {
        $this->tax_rate_id = $value;
    }
    
    // user getter and setter
    public function getUserId()
    {
        return $this->user_id;
    }

    public function setUserId($value)
    {
        $this->user_id = $value;
    }
    
    // rate_value getter and setter
    public function getRateValue()
    {
        return $this->rate_value;
    }

    public function setRateValue($value)
    {
        $this->rate_value = $value;
    }
    
    // tax_agency_id getter and setter
    public function getTaxAgencyId()
    {
        return $this->tax_agency_id;
    }

    public function setTaxAgencyId($value)
    {
        $this->tax_agency_id = $value;
    }
    
    // tax_applicable_on getter and setter
    public function getTaxApplicableOn()
    {
        return $this->tax_applicable_on;
    }

    public function setTaxApplicableOn($value)
    {
        $this->tax_applicable_on = $value;
    }
}