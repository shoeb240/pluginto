<?php
namespace Accounting\Model;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

abstract class TaxAgencyModel implements ServiceLocatorAwareInterface
{
    private $serviceLocator;

    abstract protected function getTaxAgency($userId);
    
    abstract protected function createTaxAgency($taxAgencyArray);
    
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }

    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    public function createTaxAgencyEntity($taxAgencyArray)
    {
        $confirmationArray = $this->createTaxAgency($taxAgencyArray);

        return $confirmationArray;
    }
    
}
