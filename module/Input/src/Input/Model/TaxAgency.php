<?php
namespace Input\Model;

use Application\Model\AbstractModel;
use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;
use lib\exception\PlugintoException;

class TaxAgency extends AbstractModel
{
    /* TODO: use PHPDoc's DocBlock */

    protected function getHydrator()
    {
        if (!$this->_hydrator) {
            $objectManager = $this->getObjectManager();
            $this->_hydrator = new DoctrineHydrator($objectManager, 'Application\Entity\TaxAgency', false);
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
            $taxAgencies = $this->getObjectManager()->getRepository('\Application\Entity\TaxAgency')->findBy(array('user_id' => $this->getUserId()));
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }

        $hydrator = $this->getHydrator();
        $dataArray = array();
        if ($taxAgencies) {
            foreach ($taxAgencies as $taxAgency) {
                $dataArray[] = $hydrator->extract($taxAgency);
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
            $taxAgencies = $this->getObjectManager()->getRepository('\Application\Entity\TaxAgency')->findBy(array('user_id' => $this->getUserId()));
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }

        return $taxAgencies;
    }
    
    public function getEntityById($id)
    {
        try {
            $taxAgency = $this->getObjectManager()->find('\Application\Entity\TaxAgency', $id);
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }
        if (!$taxAgency) {
            throw new PlugintoException(PlugintoException::INVALID_AGENCY_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_AGENCY_ID_ERROR_CODE);
        }
        if (!$this->getUserId() || $taxAgency->getUserId() != $this->getUserId()) {
            throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_USER_ID_ERROR_CODE);
        }
        
        $hydrator = $this->getHydrator();
        $dataArray = $hydrator->extract($taxAgency);

        return $dataArray;
    }
    
    public function getEntityBySupplierId($id)
    {
        try {
            $taxAgency = $this->getObjectManager()->getRepository('\Application\Entity\TaxAgency')->findOneBy(array('tax_agency_id' => $id));
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }
        if (!$taxAgency) {
            throw new PlugintoException(PlugintoException::INVALID_AGENCY_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_AGENCY_ID_ERROR_CODE);
        }
        if (!$this->getUserId() || $taxAgency->getUserId() != $this->getUserId()) {
            throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_USER_ID_ERROR_CODE);
        }
        
        $hydrator = $this->getHydrator();
        $dataArray = $hydrator->extract($taxAgency);

        return $dataArray;
    }

    public function createEntity($data)
    {
        if (!$this->getUserId()) {
            throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_USER_ID_ERROR_CODE);
        }
        $data['user_id'] = $this->getUserId();
        
        $taxAgencyModel = $this->getServiceLocator()->get('TaxAgencyModelWrapper')->get($this->getUserAccountingVendor());
        $confirmationArray = $taxAgencyModel->createTaxAgency($data);
        
        $taxAgency = new \Application\Entity\TaxAgency();

        if (isset($confirmationArray['tax_agency_id'])) {
            $taxAgency->setTaxAgencyId($confirmationArray['tax_agency_id']);
        }
        if (isset($data['user_id'])) {
            $taxAgency->setUserId($data['user_id']);
        }
        if (isset($confirmationArray['display_name'])) {
            $taxAgency->setDisplayName($confirmationArray['display_name']);
        }
        $taxAgency->setSyncToken($confirmationArray['sync_token']);

        try {
            $this->getObjectManager()->persist($taxAgency);
            $this->getObjectManager()->flush();
        } catch(\Exception $e) {
            // TODO: implement #36
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG . $e->getMessage(), 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }            
        
        $hydrator = $this->getHydrator();
        $taxAgencyArray = $hydrator->extract($taxAgency);
        
        return $taxAgencyArray;
    }

    public function updateEntity($id, $data)
    {
        try {
            $taxAgency = $this->getObjectManager()->find('\Application\Entity\TaxAgency', $id);
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }
        if (!$taxAgency) {
            throw new PlugintoException(PlugintoException::INVALID_AGENCY_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_AGENCY_ID_ERROR_CODE);
        }
        if (!$this->getUserId() || $taxAgency->getUserId() != $this->getUserId()) {
            throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_USER_ID_ERROR_CODE);
        }
        $data['user_id'] = $this->getUserId();
        
        $taxAgencyModel = $this->getServiceLocator()->get('TaxAgencyModelWrapper')->get($this->getUserAccountingVendor());
        $confirmationArray = $taxAgencyModel->updateTaxAgency($taxAgency, $data);
        
        if (isset($confirmationArray['vat_id'])) {
            $taxAgency->setVatId($confirmationArray['vat_id']);
        }
        if (isset($confirmationArray['tax_name'])) {
            $taxAgency->setTaxAgencyName($confirmationArray['tax_name']);
        }
        if (isset($confirmationArray['display_name'])) {
            $taxAgency->setDisplayName($confirmationArray['display_name']);
        }
        if (isset($confirmationArray['name'])) {
            $taxAgency->setName($confirmationArray['name']);
        }
        if (isset($confirmationArray['surname'])) {
            $taxAgency->setSurname($confirmationArray['surname']);
        }
        if (isset($confirmationArray['address1'])) {
            $taxAgency->setAddress1($confirmationArray['address1']);
        }
        if (isset($confirmationArray['address2'])) {
            $taxAgency->setAddress2($confirmationArray['address2']);
        }
        if (isset($confirmationArray['city'])) {
            $taxAgency->setCity($confirmationArray['city']);
        }
        if (isset($confirmationArray['postcode'])) {
            $taxAgency->setPostcode($confirmationArray['postcode']);
        }
        if (isset($confirmationArray['country'])) {
            $taxAgency->setCountry($confirmationArray['country']);
        }
        if (isset($confirmationArray['sync_token'])) {
            $taxAgency->setSyncToken($confirmationArray['sync_token']);
        }

        try {
            $this->getObjectManager()->persist($taxAgency);
            $this->getObjectManager()->flush();
        } catch(\Exception $e) {
            // TODO: implement #36
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }
        
        return $taxAgency;
    }

    public function deleteEntity($id)
    {
        try {
            $taxAgency = $this->getObjectManager()->find('\Application\Entity\TaxAgency', $id);
            if (!$taxAgency) {
                throw new PlugintoException(PlugintoException::INVALID_AGENCY_ID_ERROR_MSG, 
                                            PlugintoException::INVALID_AGENCY_ID_ERROR_CODE);
            }
            if (!$this->getUserId() || $taxAgency->getUserId() != $this->getUserId()) {
                throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                                            PlugintoException::INVALID_USER_ID_ERROR_CODE);
            }
            
            $hydrator = $this->getHydrator();
            $taxAgencyArray = $hydrator->extract($taxAgency);
            
            // Db transaction begins
            $this->getObjectManager()->beginTransaction();
            
            $this->getObjectManager()->remove($taxAgency);
            $this->getObjectManager()->flush();
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }
        
        $taxAgencyModel = $this->getServiceLocator()->get('TaxAgencyModelWrapper')->get($this->getUserAccountingVendor());
        try {
            $confirmationArray = $taxAgencyModel->deleteTaxAgency($taxAgency, $taxAgencyArray);
        } catch(\Exception $e) {
            $this->getObjectManager()->rollback();
            throw new PlugintoException($e->getMessage(), $e->getCode());
        }
        
        // Db transaction commits
        $this->getObjectManager()->commit();
        
        return $confirmationArray;
    }
}