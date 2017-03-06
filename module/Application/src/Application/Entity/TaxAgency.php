<?php
namespace Application\Entity;
use Doctrine\ORM\Mapping as ORM;
/** @ORM\Entity */
class TaxAgency {
    /**
    * @ORM\Id
    * @ORM\GeneratedValue(strategy="AUTO")
    * @ORM\Column(type="integer")
    */
    protected $id;
    
    /** @ORM\Column(type="string") */
    protected $tax_agency_id;
    
    /** @ORM\Column(type="string") */
    protected $user_id;
    
    /** @ORM\Column(type="string") */
    protected $display_name;
    
    /** @ORM\Column(type="string", nullable=true) */
    protected $sync_token;
    
    // id getter and setter
    public function getId()
    {
        return $this->id;
    }

    public function setId($value)
    {
        $this->id = $value;
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
    
    // user getter and setter
    public function getUserId()
    {
        return $this->user_id;
    }

    public function setUserId($value)
    {
        $this->user_id = $value;
    }
    
    // display_name getter and setter
    public function getDisplayName()
    {
        return $this->display_name;
    }

    public function setDisplayName($value)
    {
        $this->display_name = $value;
    }
    
    // sync_token getter and setter
    public function getSyncToken()
    {
        return $this->sync_token;
    }

    public function setSyncToken($value)
    {
        $this->sync_token = $value;
    }
    
}