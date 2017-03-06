<?php
namespace Application\Entity;
use Doctrine\ORM\Mapping as ORM;
/** 
 * @ORM\Entity 
 * @ORM\Table(name="User",uniqueConstraints={@ORM\UniqueConstraint(name="u_login_vendor", columns={"user_login", "accounting_vendor"})})
 */
class User {
    /**
    * @ORM\Id
    * @ORM\GeneratedValue(strategy="AUTO")
    * @ORM\Column(type="integer")
    */
    protected $id;

    /** @ORM\Column(type="string", nullable=true) */
    protected $property_list;
    
    /** @ORM\Column(type="string") */
    protected $accounting_vendor;
    
    /** @ORM\Column(type="string") */
    protected $user_login;
    
    /** @ORM\Column(type="string") */
    protected $default_entity_list;
    
    // vat getter and setter
    public function getId()
    {
        return $this->id;
    }
    
    // property_list getter and setter
    public function setPropertyList($listArr)
    {
        $propertyList = '';
        if (is_array($listArr)) {
            foreach($listArr as $name => $value) {
                $propertyList .= $name . '=' . $value . '~~';
            }
        } else {
            $propertyList = $listArr;
        }
        
        $this->property_list = $propertyList;
    }

    public function getPropertyList()
    {
        $stArr = explode('~~', $this->property_list);
        $propertyArr = array();
        foreach($stArr as $st) {
            $tmpArr = explode('=', $st);
            if (isset($tmpArr[1])) {
                $propertyArr[trim($tmpArr[0])] = trim($tmpArr[1]);
            }
        }
        return $propertyArr;
    }
    
    public function setProperty($key, $value)
    {
        $newSubSt = $key.'='.$value.'~~';
        if (strpos($this->property_list, $key) !== false){
                $pattern = '@'.$key.'=[^~]+~~@';
                $this->property_list = preg_replace($pattern, $newSubSt, $this->property_list);
        } else {
                $this->property_list = $this->property_list . $newSubSt;
        }
    }

    public function getProperty($key)
    {
        $propertyArr = $this->getPropertyList();
        
        if (isset($propertyArr[$key])) {
            return $propertyArr[$key];
        }
        
        return false;
    }

    // accounting_vendor getter and setter
    public function getAccountingVendor()
    {
        return $this->accounting_vendor;
    }

    public function setAccountingVendor($value)
    {
        $this->accounting_vendor = $value;
    }
    
    // user_login getter and setter
    public function getUserLogin()
    {
        return $this->user_login;
    }

    public function setUserLogin($value)
    {
        $this->user_login = $value;
    }
    
    // default_entity_list getter and setter
    public function setDefaultEntityList($listArr)
    {
        $defaultEntityList = '';
        if (is_array($listArr)) {
            foreach($listArr as $value) {
                $defaultEntityList .= $value . ',';
            }
        } else {
            $defaultEntityList = $listArr;
        }
        
        $this->default_entity_list = $defaultEntityList;
    }

    public function getDefaultEntityList()
    {
        if (!is_array($this->default_entity_list)) {
            $stArr = explode(',', $this->default_entity_list);
            return $stArr;
        } 
        
        return $this->default_entity_list;
    }
    
    public function setDefaultEntity($value)
    {
        $newSubSt = $value . ',';
        if (strpos($this->default_entity_list, $value) === false){
                $this->default_entity_list = $this->default_entity_list . $newSubSt;
        }
    }

}