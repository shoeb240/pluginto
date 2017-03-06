<?php
namespace Application\Entity;
use Doctrine\ORM\Mapping as ORM;
/** @ORM\Entity */
class Company {
    /**
    * @ORM\Id
    * @ORM\GeneratedValue(strategy="AUTO")
    * @ORM\Column(type="integer")
    */
    protected $id;

    /** @ORM\Column(type="string") */
    protected $vendor_company_id;
    
    /** @ORM\Column(type="string") */
    protected $user_id;
    
    /** @ORM\Column(type="string", nullable=true) */
    protected $vat_id;

    /** @ORM\Column(type="string", nullable=true) */
    protected $company_name;
    
    /** @ORM\Column(type="string", nullable=true) */
    protected $display_name;

    /** @ORM\Column(type="string", nullable=true) */
    protected $name;

    /** @ORM\Column(type="string", nullable=true) */
    protected $surname;

    /** @ORM\Column(type="string", nullable=true) */
    protected $address1;
    
    /** @ORM\Column(type="string", nullable=true) */
    protected $address2;

    /** @ORM\Column(type="string", nullable=true) */
    protected $city;

    /** @ORM\Column(type="string", nullable=true) */
    protected $postcode;

    /** @ORM\Column(type="string", nullable=true) */
    protected $country;
    
    /** @ORM\Column(type="string") */
    protected $company_type;
    
    /** @ORM\Column(type="string", nullable=true) */
    protected $sync_token;

    public function getId()
    {
        return $this->id;
    }

    // vendor_company_id getter and setter
    public function getVendorCompanyId()
    {
        return $this->vendor_company_id;
    }

    public function setVendorCompanyId($value)
    {
        $this->vendor_company_id = $value;
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
    
    // vat getter and setter
    public function getVatId()
    {
        return $this->vat_id;
    }

    public function setVatId($value)
    {
        $this->vat_id = $value;
    }

    // company name getter and setter
    public function getCompanyName()
    {
        return $this->company_name;
    }

    public function setCompanyName($value)
    {
        $this->company_name = $value;
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

    // name getter and setter
    public function getName()
    {
        return $this->name;
    }

    public function setName($value)
    {
        $this->name = $value;
    }

    // surname getter and setter
    public function getSurname()
    {
        return $this->surname;
    }

    public function setSurname($value)
    {
        $this->surname = $value;
    }

    // address1 getter and setter
    public function getAddress1()
    {
        return $this->address1;
    }

    public function setAddress1($value)
    {
        $this->address1 = $value;
    }
    
    // address2 getter and setter
    public function getAddress2()
    {
        return $this->address2;
    }

    public function setAddress2($value)
    {
        $this->address2 = $value;
    }

    // city getter and setter
    public function getCity()
    {
        return $this->city;
    }

    public function setCity($value)
    {
        $this->city = $value;
    }

    // postcode getter and setter
    public function getPostcode()
    {
        return $this->postcode;
    }

    public function setPostcode($value)
    {
        $this->postcode = $value;
    }

    // country getter and setter
    public function getCountry()
    {
        return $this->country;
    }

    public function setCountry($value)
    {
        $this->country = $value;
    }
    
    // company_type getter and setter
    public function getCompanyType()
    {
        return $this->company_type;
    }

    public function setCompanyType($value)
    {
        $this->company_type = $value;
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