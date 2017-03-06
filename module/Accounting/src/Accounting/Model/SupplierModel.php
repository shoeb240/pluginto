<?php
namespace Accounting\Model;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

abstract class SupplierModel implements ServiceLocatorAwareInterface
{
    private $serviceLocator;

    abstract protected function getSupplier($userId);
    
    abstract protected function createSupplier($supplierArray);
    
    abstract protected function updateSupplier(\Application\Entity\Supplier $supplier, $supplierArray);
    
    abstract protected function deleteSupplier(\Application\Entity\Supplier $supplier, $supplierArray);
    
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }

    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

//    public function createSupplier($supplierArray)
//    {
//        // Positive amount is Sale and negetive amount is Purchase
//        $confirmationArray = $this->createSupplier($supplierArray);
//
//        return $confirmationArray;
//    }
//    
//    public function updateSupplier(\Application\Entity\Supplier $supplier, $supplierArray)
//    {
//        // Positive amount is Sale and negetive amount is Purchase
//        $confirmationArray = $this->updateSupplier($supplier, $supplierArray);
//
//        return $confirmationArray;
//    }
//    
//    public function deleteSupplier(\Application\Entity\Supplier $supplier, $supplierArray)
//    {
//        $confirmationArray = $this->deleteSupplier($supplier, $supplierArray);
//
//        return $confirmationArray;
//    }

}
