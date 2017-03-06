<?php
namespace Accounting\Model;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

abstract class AccountModel implements ServiceLocatorAwareInterface
{
    private $serviceLocator;

    abstract protected function getAccount($userId);
    
    abstract protected function createAccount($accountArray);
    
    abstract protected function updateAccount(\Application\Entity\Account $account, $accountArray);
    
    abstract protected function deleteAccount(\Application\Entity\Account $account, $accountArray);
    
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }

    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    public function createAccountEntity($accountArray)
    {
        $confirmationArray = $this->createAccount($accountArray);

        return $confirmationArray;
    }
    
    public function updateAccountEntity(\Application\Entity\Account $account, $accountArray)
    {
        $confirmationArray = $this->updateAccount($account, $accountArray);

        return $confirmationArray;
    }
    
    public function deleteAccountEntity(\Application\Entity\Account $account, $accountArray)
    {
        $confirmationArray = $this->deleteAccount($account, $accountArray);

        return $confirmationArray;
    }

}
