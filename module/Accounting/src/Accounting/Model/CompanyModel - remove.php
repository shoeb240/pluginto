<?php
namespace Accounting\Model;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

abstract class CompanyModel implements ServiceLocatorAwareInterface
{
    private $serviceLocator;

    abstract protected function getCustomer($userId);
    
    abstract protected function createCustomer($companyArray);
    
    abstract protected function updateCustomer(\Application\Entity\Company $company, $companyArray);
    
    abstract protected function deleteCustomer(\Application\Entity\Company $company, $companyArray);
    
    /*abstract protected function getVendor($userId);
    
    abstract protected function createVendor($companyArray);
    
    abstract protected function updateVendor(\Application\Entity\Company $company, $companyArray);
    
    abstract protected function deleteVendor(\Application\Entity\Company $company, $companyArray);*/
    
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }

    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    public function createCompany($companyArray)
    {
        // Positive amount is Sale and negetive amount is Purchase
//        if ('vendor' == $companyArray['company_type']) {
//            $confirmationArray = $this->createVendor($companyArray);
//        } else {
                $confirmationArray = $this->createCustomer($companyArray);
//        }

        return $confirmationArray;
    }
    
    public function updateCompany(\Application\Entity\Company $company, $companyArray)
    {
        // Positive amount is Sale and negetive amount is Purchase
//        if ('vendor' == $company->getCompanyType()) {
//            $confirmationArray = $this->updateVendor($company, $companyArray);
//        } else {
            $confirmationArray = $this->updateCustomer($company, $companyArray);
//        }

        return $confirmationArray;
    }
    
    public function deleteCompany(\Application\Entity\Company $company, $companyArray)
    {
//        if ('vendor' == $company->getCompanyType()) {
//            $confirmationArray = $this->deleteVendor($company, $companyArray);
//        } else {
            $confirmationArray = $this->deleteCustomer($company, $companyArray);
//        }

        return $confirmationArray;
    }

}
