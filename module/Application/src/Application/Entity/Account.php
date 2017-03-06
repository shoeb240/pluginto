<?php
namespace Application\Entity;
use Doctrine\ORM\Mapping as ORM;
/** @ORM\Entity */
class Account {
    /**
    * @ORM\Id
    * @ORM\GeneratedValue(strategy="AUTO")
    * @ORM\Column(type="integer")
    */
    protected $id;

    /** @ORM\Column(type="string") */
    protected $vendor_account_id;

    /** @ORM\Column(type="string") */
    protected $user_id;
    
    /** @ORM\Column(type="string") */
    protected $name;
    
    /** @ORM\Column(type="string", nullable=true) */
    protected $category_id;
    
    /** @ORM\Column(type="string", nullable=true) */
    protected $category_description;
    
    /** @ORM\Column(type="string", nullable=true) */
    protected $account_type;
    
    /** @ORM\Column(type="string", nullable=true) */
    protected $account_sub_type;
    
    /** @ORM\Column(type="string", nullable=true) */
    protected $description;
    
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
    
    // account getter and setter
    public function getVendorAccountId()
    {
        return $this->vendor_account_id;
    }

    public function setVendorAccountId($value)
    {
        $this->vendor_account_id = $value;
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
    
    // user getter and setter
    public function getName()
    {
        return $this->name;
    }

    public function setName($value)
    {
        $this->name = $value;
    }
    
    // category_id getter and setter
    public function getCategoryId()
    {
        return $this->category_id;
    }

    public function setCategoryId($value)
    {
        $this->category_id = $value;
    }
    
    // category_description getter and setter
    public function getCategoryDescription()
    {
        return $this->category_description;
    }

    public function setCategoryDescription($value)
    {
        $this->category_description = $value;
    }
    
    // account_type getter and setter
    public function getAccountType()
    {
        return $this->account_type;
    }

    public function setAccountType($value)
    {
        $this->account_type = $value;
    }
    
    // account_sub_type getter and setter
    public function getAccountSubType()
    {
        return $this->account_sub_type;
    }

    public function setAccountSubType($value)
    {
        $this->account_sub_type = $value;
    }
    
    // description getter and setter
    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($value)
    {
        $this->description = $value;
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