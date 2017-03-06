<?php
namespace Application\Entity;
use Doctrine\ORM\Mapping as ORM;
/** @ORM\Entity */
class Service {
    /**
    * @ORM\Id
    * @ORM\GeneratedValue(strategy="AUTO")
    * @ORM\Column(type="integer")
    */
    protected $id;
    
    /** @ORM\Column(type="string") */
    protected $vendor_service_id;
    
    /** @ORM\Column(type="string") */
    protected $transaction_id;
    
    /** @ORM\Column(type="string") */
    protected $user_id;

    /** @ORM\Column(type="string") */
    protected $customer_id;
    
    /** @ORM\Column(type="string") */
    protected $supplier_id;
    
    /** @ORM\Column(type="string", nullable=true) */
    protected $description;
    
    /** @ORM\Column(type="string", nullable=true) */
    protected $account_id;

    /** @ORM\Column(type="string", nullable=true) */
    protected $bank_account_id;
    
    /** @ORM\Column(type="string", nullable=true) */
    protected $item_id;
    
    /** @ORM\Column(type="string", nullable=true) */
    protected $tax_id;
    
    /** @ORM\Column(type="string", nullable=true) */
    protected $doc_number;
    
    /** @ORM\Column(type="string") */
    protected $service_type;

    /** @ORM\Column(type="string", nullable=true) */
    protected $tax_percentage;
    
    /** @ORM\Column(type="string", nullable=true) */
    protected $tax;
    
    /** @ORM\Column(type="string", nullable=true) */
    protected $discount_percentage;
    
    /** @ORM\Column(type="string", nullable=true) */
    protected $discount;
    
    /** @ORM\Column(type="decimal", nullable=true, precision=11, scale=2) */
    protected $balance;

    /** @ORM\Column(type="decimal", nullable=true, precision=11, scale=2) */
    protected $total_amt;

    /** @ORM\Column(type="string", nullable=true) */
    protected $due_date;
    
    /** @ORM\Column(type="string", nullable=true) */
    protected $payment_type;
    
    /** @ORM\Column(type="string", nullable=true) */
    protected $txn_date;
    
    /** @ORM\Column(type="string", nullable=true) */
    protected $sync_token;

    public function getId()
    {
        return $this->id;
    }
    
    public function setId($value)
    {
        $this->id = $value;
    }

    // vendor_service_id getter and setter
    public function getVendorServiceId()
    {
        return $this->vendor_service_id;
    }

    public function setVendorServiceId($value)
    {
        $this->vendor_service_id = $value;
    }
    
    // transaction_id getter and setter
    public function getTransactionId()
    {
        return $this->transaction_id;
    }

    public function setTransactionId($value)
    {
        $this->transaction_id = $value;
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

    // company getter and setter
    public function getCustomerId()
    {
        return $this->customer_id;
    }

    public function setCustomerId($value)
    {
        $this->customer_id = $value;
    }
    
    // company getter and setter
    public function getSupplierId()
    {
        return $this->supplier_id;
    }

    public function setSupplierId($value)
    {
        $this->supplier_id = $value;
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
    
    // account getter and setter
    public function getAccountId()
    {
        return $this->account_id;
    }

    public function setAccountId($value)
    {
        $this->account_id = $value;
    }
    
    // bank_account_id getter and setter
    public function getBankAccountId()
    {
        return $this->bank_account_id;
    }

    public function setBankAccountId($value)
    {
        $this->bank_account_id = $value;
    }
    
    // item getter and setter
    public function getItemId()
    {
        return $this->item_id;
    }

    public function setItemId($value)
    {
        $this->item_id = $value;
    }
    
    // tax getter and setter
    public function getTaxId()
    {
        return $this->tax_id;
    }

    public function setTaxId($value)
    {
        $this->tax_id = $value;
    }
    
    // company name getter and setter
    public function getDocNumber()
    {
        return $this->doc_number;
    }

    public function setDocNumber($value)
    {
        $this->doc_number = $value;
    }
    
    // service_type getter and setter
    public function getServiceType()
    {
        return $this->service_type;
    }

    public function setServiceType($value)
    {
        $this->service_type = $value;
    }

    // description getter and setter
    public function getTaxPercentage()
    {
        return $this->tax_percentage;
    }

    public function setTaxPercentage($value)
    {
        $this->tax_percentage = $value;
    }
    
    // description getter and setter
    public function getTax()
    {
        return $this->tax;
    }

    public function setTax($value)
    {
        $this->tax = $value;
    }
    
    // description getter and setter
    public function getDiscountPercentage()
    {
        return $this->discount_percentage;
    }

    public function setDiscountPercentage($value)
    {
        $this->discount_percentage = $value;
    }
    
    // description getter and setter
    public function getDiscount()
    {
        return $this->discount;
    }

    public function setDiscount($value)
    {
        $this->discount = $value;
    }
    // name getter and setter
    public function getBalance()
    {
        return $this->balance;
    }

    public function setBalance($value)
    {
        $this->balance = $value;
    }

    // surname getter and setter
    public function getTotalAmt()
    {
        return $this->total_amt;
    }

    public function setTotalAmt($value)
    {
        $this->total_amt = $value;
    }

	// address getter and setter
    public function getDueDate()
    {
        return $this->due_date;
    }

    public function setDueDate($value)
    {
        $this->due_date = $value;
    }
    
    // payment_type getter and setter
    public function getPaymentType()
    {
        return $this->payment_type;
    }

    public function setPaymentType($value)
    {
        $this->payment_type = $value;
    }
    
    // city getter and setter
    public function getTxnDate()
    {
        return $this->txn_date;
    }

    public function setTxnDate($value)
    {
        $this->txn_date = $value;
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