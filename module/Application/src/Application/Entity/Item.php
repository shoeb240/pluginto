<?php
namespace Application\Entity;
use Doctrine\ORM\Mapping as ORM;
/** @ORM\Entity */
class Item {
    /**
    * @ORM\Id
    * @ORM\GeneratedValue(strategy="AUTO")
    * @ORM\Column(type="integer")
    */
    protected $id;

    /** @ORM\Column(type="string") */
    protected $vendor_item_id;
    
    /** @ORM\Column(type="string") */
    protected $user_id;
    
    /** @ORM\Column(type="string", nullable=true) */
    protected $category_id;
    
    /** @ORM\Column(type="string", nullable=true) */
    protected $item_name;
    
    /** @ORM\Column(type="string", nullable=true) */
    protected $item_type;
    
    /** @ORM\Column(type="string", nullable=true) */
    protected $income_account_id;
    
    /** @ORM\Column(type="string", nullable=true) */
    protected $expense_account_id;
    
    /** @ORM\Column(type="string", nullable=true) */
    protected $description;

    /** @ORM\Column(type="string", nullable=true) */
    protected $price;
    
    /** @ORM\Column(type="string", nullable=true) */
    protected $sync_token;

    public function getId()
    {
        return $this->id;
    }

    // vendor_item_id getter and setter
    public function getVendorItemId()
    {
        return $this->vendor_item_id;
    }

    public function setVendorItemId($value)
    {
        $this->vendor_item_id = $value;
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
    public function getCategoryId()
    {
        return $this->category_id;
    }

    public function setCategoryId($value)
    {
        $this->category_id = $value;
    }

    // item name getter and setter
    public function getItemName()
    {
        return $this->item_name;
    }

    public function setItemName($value)
    {
        $this->item_name = $value;
    }
    
    // item_type getter and setter
    public function getItemType()
    {
        return $this->item_type;
    }

    public function setItemType($value)
    {
        $this->item_type = $value;
    }
    
    // income_account_id getter and setter
    public function getIncomeAccountId()
    {
        return $this->income_account_id;
    }

    public function setIncomeAccountId($value)
    {
        $this->income_account_id = $value;
    }
    
    // expense_account_id getter and setter
    public function getExpenseAccountId()
    {
        return $this->expense_account_id;
    }

    public function setExpenseAccountId($value)
    {
        $this->expense_account_id = $value;
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

    // price getter and setter
    public function getPrice()
    {
        return $this->price;
    }

    public function setPrice($value)
    {
        $this->price = $value;
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