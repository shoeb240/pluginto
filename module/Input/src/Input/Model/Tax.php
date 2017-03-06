<?php
namespace Input\Model;

use Application\Model\AbstractModel;
use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;
use lib\exception\PlugintoException;

class Tax extends AbstractModel
{
    /* TODO: use PHPDoc's DocBlock */

    protected function getHydrator()
    {
        if (!$this->_hydrator) {
            $objectManager = $this->getObjectManager();
            $this->_hydrator = new DoctrineHydrator($objectManager, 'Application\Entity\TaxCode', false);
        }

        return $this->_hydrator;
    }

    // used in InputController getList()
    public function fetchAll()
    {
        if (!$this->getUserId()) {
            throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                      PlugintoException::INVALID_USER_ID_ERROR_CODE);
        }
        
        try {
            $taxes = $this->getObjectManager()->getRepository('\Application\Entity\TaxCode')->findBy(array('user_id' => $this->getUserId()));
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }

        $hydrator = $this->getHydrator();
        $dataArray = array();
        if ($taxes) {
            foreach ($taxes as $tax) {
                $dataArray[] = $hydrator->extract($tax);
            }
        }
        
        return $dataArray;
    }
    
    public function fetchAllObj()
    {
        if (!$this->getUserId()) {
            throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                      PlugintoException::INVALID_USER_ID_ERROR_CODE);
        }
        
        try {
            $tax = $this->getObjectManager()->getRepository('\Application\Entity\TaxCode')->findBy(array('user_id' => $this->getUserId()));
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }

        return $tax;
    }

    public function getEntityById($id)
    {
        try {
            $tax = $this->getObjectManager()->find('\Application\Entity\TaxCode', $id);
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }
        if (!$tax) {
            throw new PlugintoException(PlugintoException::INVALID_TAXCODE_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_TAXCODE_ID_ERROR_CODE);
        }
        if (!$this->getUserId() || $tax->getUserId() != $this->getUserId()) {
            throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_USER_ID_ERROR_CODE);
        }
        
        $hydrator = $this->getHydrator();
        $dataArray = $hydrator->extract($tax);

        return $dataArray;
    }
    
    public function getEntityBySupplierId($id)
    {
        try {
            $tax = $this->getObjectManager()->getRepository('\Application\Entity\TaxCode')->findOneBy(array('vendor_tax_id' => $id));
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }
        if (!$tax) {
            throw new PlugintoException(PlugintoException::INVALID_TAXCODE_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_TAXCODE_ID_ERROR_CODE);
        }
        if (!$this->getUserId() || $tax->getUserId() != $this->getUserId()) {
            throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_USER_ID_ERROR_CODE);
        }
        
        $hydrator = $this->getHydrator();
        $dataArray = $hydrator->extract($tax);

        return $dataArray;
    }

    public function createEntity($data)
    {
        if (!$this->getUserId()) {
            throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_USER_ID_ERROR_CODE);
        }
        $data['user_id'] = $this->getUserId();
        
        $taxModel = $this->getServiceLocator()->get('TaxModelWrapper')->get($this->getUserAccountingVendor());
        $confirmationArray = $taxModel->createTaxEntity($data);
        
        $rateList = '';
        foreach($confirmationArray['tax_rate_list'] as $k => $taxRateList) {
            if (empty($data['tax_rate_id'][$k])) {
                $taxRate = new \Application\Entity\TaxRate();
                if (isset($taxRateList['tax_rate_name'])) {
                    $taxRate->setTaxRateName($taxRateList['tax_rate_name']);
                }
                if (isset($taxRateList['tax_rate_id'])) {
                    $taxRate->setTaxRateId($taxRateList['tax_rate_id']);
                }
                if (isset($data['user_id'])) {
                    $taxRate->setUserId($data['user_id']);
                }
                if (isset($taxRateList['rate_value'])) {
                    $taxRate->setRateValue($taxRateList['rate_value']);
                }
                if (isset($taxRateList['tax_agency_id'])) {
                    $taxRate->setTaxAgencyId($taxRateList['tax_agency_id']);
                }
                if (isset($taxRateList['tax_applicable_on'])) {
                    $taxRate->setTaxApplicableOn($taxRateList['tax_applicable_on']);
                }
                $this->getObjectManager()->persist($taxRate);
                try {
                    $this->getObjectManager()->flush();
                } catch(\Exception $e) {
                    // TODO: implement #36
                    throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG . $e->getMessage(), 
                                                PlugintoException::INTERNAL_SERVER_ERROR_CODE);
                }

                if ($rateList != '') $rateList .= ',';
                $rateList .= $taxRate->getId();
            } else {
                if ($rateList != '') $rateList .= ',';
                $rateList .= $data['tax_rate_id'][$k];
            }
        }
        
        $taxCode = new \Application\Entity\TaxCode();
        if (isset($confirmationArray['tax_code'])) {
            $taxCode->setTaxCode($confirmationArray['tax_code']);
        }
        if (isset($confirmationArray['tax_code_id'])) {
            $taxCode->setTaxCodeId($confirmationArray['tax_code_id']);
        }
        if (isset($data['user_id'])) {
            $taxCode->setUserId($data['user_id']);
        }
        if (isset($rateList)) {
            $taxCode->setTaxRateList($rateList);
        }
        $this->getObjectManager()->persist($taxCode);

        try {
            $this->getObjectManager()->flush();
        } catch(\Exception $e) {
            // TODO: implement #36
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG . $e->getMessage(), 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }            
        
        return $confirmationArray;
    }

    public function updateEntity($id, $data) {
        return null;
    }
    
    public function deleteEntity($id)
    {
        /*try {
            $tax = $this->getObjectManager()->find('\Application\Entity\TaxCode', $id);
            if (!$tax) {
                throw new PlugintoException(PlugintoException::INVALID_TAXCODE_ID_ERROR_MSG, 
                                            PlugintoException::INVALID_TAXCODE_ID_ERROR_CODE);
            }
            
            $hydrator = $this->getHydrator();
            $taxArray = $hydrator->extract($tax);
            
            // Db transaction begins
            $this->getObjectManager()->beginTransaction();
            
            $this->getObjectManager()->remove($tax);
            $this->getObjectManager()->flush();
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }
        
        $taxModel = $this->getServiceLocator()->get('TaxModelWrapper')->get($this->getUserAccountingVendor());
        try {
            $confirmationArray = $taxModel->deleteTaxEntity($tax, $taxArray);
        } catch(\Exception $e) {
            $this->getObjectManager()->rollback();
            throw new PlugintoException($e->getMessage(), $e->getCode());
        }
        
        // Db transaction commits
        $this->getObjectManager()->commit();
        
        return $confirmationArray;*/
    }
}