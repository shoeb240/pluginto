<?php
namespace Accounting\Model;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

abstract class BankAccountModel implements ServiceLocatorAwareInterface
{
    private $serviceLocator;

    abstract protected function getBankAccount($userId);
    
    abstract protected function createBankAccount($bankAccountArray);
    
    abstract protected function updateBankAccount(\Application\Entity\BankAccount $bankAccount, $bankAccountArray);
    
    abstract protected function deleteBankAccount(\Application\Entity\BankAccount $bankAccount, $bankAccountArray);
    
    /*abstract protected function getVendor($userId);
    
    abstract protected function createVendor($bankAccountArray);
    
    abstract protected function updateVendor(\Application\Entity\BankAccount $bankAccount, $bankAccountArray);
    
    abstract protected function deleteVendor(\Application\Entity\BankAccount $bankAccount, $bankAccountArray);*/
    
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }

    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

//    public function createBankAccount($bankAccountArray)
//    {
//        // Positive amount is Sale and negetive amount is Purchase
////        if ('vendor' == $bankAccountArray['bankAccount_type']) {
////            $confirmationArray = $this->createVendor($bankAccountArray);
////        } else {
//                $confirmationArray = $this->createBankAccount($bankAccountArray);
////        }
//
//        return $confirmationArray;
//    }
    
//    public function updateBankAccount(\Application\Entity\BankAccount $bankAccount, $bankAccountArray)
//    {
//        // Positive amount is Sale and negetive amount is Purchase
////        if ('vendor' == $bankAccount->getBankAccountType()) {
////            $confirmationArray = $this->updateVendor($bankAccount, $bankAccountArray);
////        } else {
//            $confirmationArray = $this->updateBankAccount($bankAccount, $bankAccountArray);
////        }
//
//        return $confirmationArray;
//    }
    
//    public function deleteBankAccount(\Application\Entity\BankAccount $bankAccount, $bankAccountArray)
//    {
////        if ('vendor' == $bankAccount->getBankAccountType()) {
////            $confirmationArray = $this->deleteVendor($bankAccount, $bankAccountArray);
////        } else {
//            $confirmationArray = $this->deleteBankAccount($bankAccount, $bankAccountArray);
////        }
//
//        return $confirmationArray;
//    }

}
