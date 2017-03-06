<?php
namespace Application\Model;

use Doctrine\ORM\EntityManager;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

abstract class BaseModel implements ServiceLocatorAwareInterface
{
    /* TODO: use PHPDoc's DocBlock */
    protected $_serviceLocator;
    protected $_objectManager;
    protected $_hydrator;
    protected $_userId;
    protected $_userAccountingVendor;
    protected $_fromVendor = false;

    // needed for phpunit...
    public function __construct(ServiceLocatorInterface $serviceLocator = null)
    {
        $this->_serviceLocator = $serviceLocator;
    }
    
    public function getServiceLocator()
    {
        return $this->_serviceLocator;
    }

    public function setServiceLocator(ServiceLocatorInterface $serviceLocator) 
    {
	$this->_serviceLocator = $serviceLocator;
    }


    public function setObjectManager(EntityManager $entityManager)
    {
        $this->_objectManager = $entityManager;
    }

    public function getObjectManager()
    {
        if (!$this->_objectManager) {
            $this->_objectManager = $this->getServiceLocator()->get('Doctrine\ORM\EntityManager');
        }

        return $this->_objectManager;
    }
    
    public function setUserId($userId)
    {
        $this->_userId = $userId;
    }
    
    public function getUserId()
    {
        return $this->_userId;
    }
    
    public function setUserAccountingVendor($userAccountingVendor)
    {
        $this->_userAccountingVendor = $userAccountingVendor;
    }
    
    public function getUserAccountingVendor()
    {
        return $this->_userAccountingVendor;
    }
    
    public function setFromVendor($fromVendor)
    {
        $this->_fromVendor = $fromVendor;
    }
    
    public function getFromVendor()
    {
        return $this->_fromVendor;
    }
}