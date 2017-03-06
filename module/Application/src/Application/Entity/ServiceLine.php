<?php
namespace Application\Entity;
use Doctrine\ORM\Mapping as ORM;
/** @ORM\Entity */
class ServiceLine {
    /**
    * @ORM\Id
    * @ORM\GeneratedValue(strategy="AUTO")
    * @ORM\Column(type="integer")
    */
    protected $id;
    
    /** @ORM\Column(type="string") */
    protected $service_id;

    /** @ORM\Column(type="integer", nullable=true) */
    protected $line_num;
    
    /** @ORM\Column(type="string", nullable=true) */
    protected $account_id;
    
    /** @ORM\Column(type="string", nullable=true) */
    protected $item_id;

    /** @ORM\Column(type="string", nullable=true) */
    protected $description;
    
    /** @ORM\Column(type="string", nullable=true) */
    protected $quantity;
    
    /** @ORM\Column(type="string", nullable=true) */
    protected $unit_price;
    
    /** @ORM\Column(type="string", nullable=true) */
    protected $tax_percentage;
    
    /** @ORM\Column(type="string", nullable=true) */
    protected $tax;
    
    /** @ORM\Column(type="string", nullable=true) */
    protected $discount_percentage;
    
    /** @ORM\Column(type="string", nullable=true) */
    protected $discount;

    /** @ORM\Column(type="decimal", precision=11, scale=2) */
    protected $amount;

    /** @ORM\Column(type="string", nullable=true) */
    protected $detail_type;

    public function getId()
    {
        return $this->id;
    }

    // service_id getter and setter
    public function getServiceId()
    {
        return $this->service_id;
    }

    public function setServiceId($value)
    {
        $this->service_id = $value;
    }

    // line_num getter and setter
    public function getLineNum()
    {
        return $this->line_num;
    }

    public function setLineNum($value)
    {
        $this->line_num = $value;
    }
    
    // account_id getter and setter
    public function getAccountId()
    {
        return $this->account_id;
    }

    public function setAccountId($value)
    {
        $this->account_id = $value;
    }

    // item_id getter and setter
    public function getItemId()
    {
        return $this->item_id;
    }

    public function setItemId($value)
    {
        $this->item_id = $value;
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
    
    // description getter and setter
    public function getQuantity()
    {
        return $this->quantity;
    }

    public function setQuantity($value)
    {
        $this->quantity = $value;
    }
    
    // description getter and setter
    public function getUnitPrice()
    {
        return $this->unit_price;
    }

    public function setUnitPrice($value)
    {
        $this->unit_price = $value;
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

    // amount getter and setter
    public function getAmount()
    {
        return $this->amount;
    }

    public function setAmount($value)
    {
        $this->amount = $value;
    }

    // detail_type getter and setter
    public function getDetailType()
    {
        return $this->detail_type;
    }

    public function setDetailType($value)
    {
        $this->detail_type = $value;
    }

}