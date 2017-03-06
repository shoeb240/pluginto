<?php
namespace Accounting\Model;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

abstract class CustomerModel implements ServiceLocatorAwareInterface
{
    private $serviceLocator;

    abstract protected function getCustomer($userId);
    
    abstract protected function createCustomer($customerArray);
    
    abstract protected function updateCustomer(\Application\Entity\Customer $customer, $customerArray);
    
    abstract protected function deleteCustomer(\Application\Entity\Customer $customer, $customerArray);
    
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }

    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

//    public function createCustomer($customerArray)
//    {
//        // Positive amount is Sale and negetive amount is Purchase
////        if ('vendor' == $customerArray['customer_type']) {
////            $confirmationArray = $this->createVendor($customerArray);
////        } else {
//                $confirmationArray = $this->createCustomer($customerArray);
////        }
//
//        return $confirmationArray;
//    }
    
//    public function updateCustomer(\Application\Entity\Customer $customer, $customerArray)
//    {
//        // Positive amount is Sale and negetive amount is Purchase
////        if ('vendor' == $customer->getCustomerType()) {
////            $confirmationArray = $this->updateVendor($customer, $customerArray);
////        } else {
//            $confirmationArray = $this->updateCustomer($customer, $customerArray);
////        }
//
//        return $confirmationArray;
//    }
    
//    public function deleteCustomer(\Application\Entity\Customer $customer, $customerArray)
//    {
////        if ('vendor' == $customer->getCustomerType()) {
////            $confirmationArray = $this->deleteVendor($customer, $customerArray);
////        } else {
//            $confirmationArray = $this->deleteCustomer($customer, $customerArray);
////        }
//
//        return $confirmationArray;
//    }

}
