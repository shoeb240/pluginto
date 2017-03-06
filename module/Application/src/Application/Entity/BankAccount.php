<?php
namespace Application\Entity;
use Doctrine\ORM\Mapping as ORM;
/** @ORM\Entity */
class BankAccount {
    /**
    * @ORM\Id
    * @ORM\GeneratedValue(strategy="AUTO")
    * @ORM\Column(type="integer")
    */
    protected $id;

    /** @ORM\Column(type="string") */
    protected $vendor_bank_account_id;
    
    /** @ORM\Column(type="string") */
    protected $user_id;

    /** @ORM\Column(type="string", nullable=true) */
    protected $account_name;

    /** @ORM\Column(type="string", nullable=true) */
    protected $bank_name;
    
    /** @ORM\Column(type="string", nullable=true) */
    protected $account_number;

    /** @ORM\Column(type="string", nullable=true) */
    protected $branch_name;

    public function getId()
    {
        return $this->id;
    }

    // vendor_bank_account_id getter and setter
    public function getVendorBankAccountId()
    {
        return $this->vendor_bank_account_id;
    }

    public function setVendorBankAccountId($value)
    {
        $this->vendor_bank_account_id = $value;
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
    
    // bank_account name getter and setter
    public function getAccountName()
    {
        return $this->account_name;
    }

    public function setAccountName($value)
    {
        $this->account_name = $value;
    }
    
    // vat getter and setter
    public function getBankName()
    {
        return $this->bank_name;
    }

    public function setBankName($value)
    {
        $this->bank_name = $value;
    }

    // account_number getter and setter
    public function getAccountNumber()
    {
        return $this->account_number;
    }

    public function setAccountNumber($value)
    {
        $this->account_number = $value;
    }

    // branch_name getter and setter
    public function getBranchName()
    {
        return $this->branch_name;
    }

    public function setBranchName($value)
    {
        $this->branch_name = $value;
    }

}