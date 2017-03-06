<?php
namespace Accounting\Model;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

abstract class TaxModel implements ServiceLocatorAwareInterface
{
    private $serviceLocator;
    
    abstract protected function getTax($userId);
    
    abstract protected function createTax($taxArray);
    
    //abstract protected function deleteTax(\Application\Entity\TaxCode $tax, $taxArray);
    
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }

    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    public function createTaxEntity($taxArray)
    {
        if (isset($taxArray['tax_rate_id'])) {
            for($i = 0; $i < count($taxArray['tax_rate_id']); $i++) {
                if (!empty($taxArray['tax_rate_id'][$i])) {
                    $taxRateModel = $this->getServiceLocator()->get("TaxRateModel");
                    $taxRateModel->setUserId($taxArray['user_id']);
                    $taxRateArray = $taxRateModel->getEntityById($taxArray['tax_rate_id'][$i]);
                    $taxArray['tax_rate_id'][$i] = $taxRateArray['tax_rate_id'];
                }
            }
        }
        
        if (isset($taxArray['tax_agency_id'])) {
            for($i = 0; $i < count($taxArray['tax_agency_id']); $i++) {
                if (!empty($taxArray['tax_agency_id'][$i])) {
                    $taxAgencyModel = $this->getServiceLocator()->get("TaxAgencyModel");
                    $taxAgencyModel->setUserId($taxArray['user_id']);
                    $taxAgencyArray = $taxAgencyModel->getEntityById($taxArray['tax_agency_id'][$i]);
                    $taxArray['tax_agency_id'][$i] = $taxAgencyArray['tax_agency_id'];
                }
            }
        }
        
        $confirmationArray = $this->createTax($taxArray);

        return $confirmationArray;
    }
    
    public function updateTaxEntity(\Application\Entity\TaxCode $tax, $taxArray)
    {
        $confirmationArray = $this->updateTax($tax, $taxArray);

        return $confirmationArray;
    }
    
    /*public function deleteTaxEntity(\Application\Entity\TaxCode $tax, $taxArray)
    {
        $confirmationArray = $this->deleteTax($tax, $taxArray);

        return $confirmationArray;
    }*/

}
