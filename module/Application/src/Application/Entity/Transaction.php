<?php
namespace Application\Entity;
use Doctrine\ORM\Mapping as ORM;
/** @ORM\Entity 
  * @ORM\Table("`Transaction`")
*/
class Transaction {
    /**
    * @ORM\Id
    * @ORM\GeneratedValue(strategy="AUTO")
    * @ORM\Column(type="integer")
    */
    protected $id;
    
    /** @ORM\Column(type="string") */
    protected $user_id;

    /** @ORM\Column(type="string", nullable=true) */
    protected $customer_id;
    
    /** @ORM\Column(type="string", nullable=true) */
    protected $supplier_id;
    
    /** @ORM\Column(type="string", nullable=true) */
    //protected $description;
    
    /** @ORM\Column(type="string", nullable=true) */
    protected $account_id;

    /** @ORM\Column(type="string", nullable=true) */
    protected $bank_account_id;
    
    /** @ORM\Column(type="string", nullable=true) */
    //protected $item_id;
    
    /** @ORM\Column(type="string", nullable=true) */
    protected $tax_id;

    /** @ORM\Column(type="string", nullable=true) */
    protected $tax_percent_total;
    
    /** @ORM\Column(type="string", nullable=true) */
    protected $tax_amount_total;
    
    /** @ORM\Column(type="string", nullable=true) */
    protected $discount_percent_total;
    
    /** @ORM\Column(type="string", nullable=true) */
    protected $discount_amount_total;
    
    /** @ORM\Column(type="decimal", precision=11, scale=2, nullable=true) */
    protected $amount;

    /** @ORM\Column(type="string") */
    protected $currency;

    /** @ORM\Column(type="integer", nullable=true) */
    protected $payment_made;
    
    /** @ORM\Column(type="string", nullable=true) */
    protected $payment_type;

    /** @ORM\Column(type="datetime") */
    protected $date;
    
    public function getId()
    {
        return $this->id;
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

    // customer getter and setter
    public function getCustomerId()
    {
        return $this->customer_id;
    }

    public function setCustomerId($value)
    {
        $this->customer_id = $value;
    }
    
    // customer getter and setter
    public function getSupplierId()
    {
        return $this->supplier_id;
    }

    public function setSupplierId($value)
    {
        $this->supplier_id = $value;
    }
    
    // description getter and setter
    /*public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($value)
    {
        $this->description = $value;
    }*/
    
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
    /*public function getItemId()
    {
        return $this->item_id;
    }

    public function setItemId($value)
    {
        $this->item_id = $value;
    }*/
    
    // tax getter and setter
    public function getTaxId()
    {
        return $this->tax_id;
    }

    public function setTaxId($value)
    {
        $this->tax_id = $value;
    }
    
    // description getter and setter
    public function getTaxPercentTotal()
    {
        return $this->tax_percent_total;
    }

    public function setTaxPercentTotal($value)
    {
        $this->tax_percent_total = $value;
    }
    
    // description getter and setter
    public function getTaxAmountTotal()
    {
        return $this->tax_amount_total;
    }

    public function setTaxAmountTotal($value)
    {
        $this->tax_amount_total = $value;
    }
    
    // description getter and setter
    public function getDiscountPercentTotal()
    {
        return $this->discount_percent_total;
    }

    public function setDiscountPercentTotal($value)
    {
        $this->discount_percent_total = $value;
    }
    
    // description getter and setter
    public function getDiscountAmountTotal()
    {
        return $this->discount_amount_total;
    }

    public function setDiscountAmountTotal($value)
    {
        $this->discount_amount_total = $value;
    }
    
    // amount getter and setter
//    public function getAmount()
//    {
//        $amount = new Amount();
//        $amount->setValue($this->amount);
//        $amount->setCurrency($this->currency);
//        return $amount;
//    }
    public function getAmount()
    {
        return $this->amount;
    }

//    public function setAmount($amount)
//    {
//        $this->amount = $amount->getValue();
//        $this->currency = $amount->getCurrency();
//    }
    public function setAmount($value)
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

    // payment_made getter and setter
    public function getPaymentMade()
    {
        return $this->payment_made;
    }

    public function setPaymentMade($value)
    {
        $this->payment_made = $value;
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
    
    // date getter and setter
    public function getDatetime()
    {
        return $this->date;
    }

    public function setDatetime($value)
    {
        $this->date = $value;
    }
}