<?php
namespace Application\Entity;
use Doctrine\ORM\Mapping as ORM;
/** @ORM\Entity */
class TaxCode {
    /**
    * @ORM\Id
    * @ORM\GeneratedValue(strategy="AUTO")
    * @ORM\Column(type="integer")
    */
    protected $id;
    
    /** @ORM\Column(type="string") */
    protected $tax_code;
    
    /** @ORM\Column(type="string") */
    protected $tax_code_id;
    
    /** @ORM\Column(type="string") */
    protected $user_id;
    
    /** @ORM\Column(type="string") */
    protected $tax_rate_list;
    
    // id getter and setter
    public function getId()
    {
        return $this->id;
    }

    public function setId($value)
    {
        $this->id = $value;
    }
    
    // tax_code getter and setter
    public function getTaxCode()
    {
        return $this->tax_code;
    }

    public function setTaxCode($value)
    {
        $this->tax_code = $value;
    }
    
    // tax_code_id getter and setter
    public function getTaxCodeId()
    {
        return $this->tax_code_id;
    }

    public function setTaxCodeId($value)
    {
        $this->tax_code_id = $value;
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
    
    // tax_rate_list getter and setter
    public function getTaxRateList()
    {
        return $this->tax_rate_list;
    }

    public function setTaxRateList($value)
    {
        $this->tax_rate_list = $value;
    }
}