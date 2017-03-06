<?php
namespace Input\Model;

use Application\Model\AbstractModel;
use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;
use lib\exception\PlugintoException;

class Customer extends AbstractModel
{
    /* TODO: use PHPDoc's DocBlock */

    protected function getHydrator()
    {
        if (!$this->_hydrator) {
            $objectManager = $this->getObjectManager();
            $this->_hydrator = new DoctrineHydrator($objectManager, 'Application\Entity\Customer', false);
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

        if ($this->getFromVendor()) {
            $customerArray['user_id'] = $this->getUserId();
            $customerAccountingModel = $this->getServiceLocator()->get('CustomerModelWrapper')->get($this->getUserAccountingVendor()); //Qb
            $vendorCustomerArr = $customerAccountingModel->getCustomer($customerArray);
            $dataArray = array();
            if (!empty($vendorCustomerArr->Results)) {
                foreach($vendorCustomerArr->Results as $eachObj) {
                    $dataArray[] = $customerAccountingModel->prepareReturnArray($eachObj);
                }
            }
            
            return $dataArray;
        }

        try {
            $companies = $this->getObjectManager()->getRepository('\Application\Entity\Customer')->findBy(array('user_id' => $this->getUserId()));
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG . $e->getMessage(), 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }

        $hydrator = $this->getHydrator();
        $dataArray = array();
        if ($companies) {
            $customerModel = $this->getServiceLocator()->get('CustomerModelWrapper')->get($this->getUserAccountingVendor()); // Qb
            foreach ($companies as $customer) {
                $eachData = $hydrator->extract($customer);
                $eachVendorData = $customerModel->dbVendorFieldFilter($eachData); // New Sage
                $dataArray[] = $eachVendorData;
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
            $companies = $this->getObjectManager()->getRepository('\Application\Entity\Customer')->findBy(array('user_id' => $this->getUserId()));
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }

        // nned to implement dbVendorFieldFilter type method
        
        return $companies;
    }
    
    public function getEntityById($id)
    {
        try {
            $customer = $this->getObjectManager()->find('\Application\Entity\Customer', $id);
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }
        if (!$customer) {
            throw new PlugintoException(PlugintoException::INVALID_COMPANY_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_COMPANY_ID_ERROR_CODE);
        }
        if (!$this->getUserId() || $customer->getUserId() != $this->getUserId()) {
            throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_USER_ID_ERROR_CODE);
        }
        
        $hydrator = $this->getHydrator();
        $dataArray = $hydrator->extract($customer);

        $customerModel = $this->getServiceLocator()->get('CustomerModelWrapper')->get($this->getUserAccountingVendor()); // Qb
        $dataArray = $customerModel->dbVendorFieldFilter($dataArray); // New Sage
                
        return $dataArray;
    }
    
    public function getEntityBySupplierId($id)
    {
        try {
            $customer = $this->getObjectManager()->getRepository('\Application\Entity\Customer')->findOneBy(array('vendor_customer_id' => $id));
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }
        if (!$customer) {
            throw new PlugintoException(PlugintoException::INVALID_COMPANY_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_COMPANY_ID_ERROR_CODE);
        }
        if (!$this->getUserId() || $customer->getUserId() != $this->getUserId()) {
            throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_USER_ID_ERROR_CODE);
        }
        
        $hydrator = $this->getHydrator();
        $dataArray = $hydrator->extract($customer);

        $customerModel = $this->getServiceLocator()->get('CustomerModelWrapper')->get($this->getUserAccountingVendor()); // Qb
        $dataArray = $customerModel->dbVendorFieldFilter($dataArray); // New Sage
        
        return $dataArray;
    }

    public function createEntity($data)
    {
        if (!$this->getUserId()) {
            throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_USER_ID_ERROR_CODE);
        }
        $data['user_id'] = $this->getUserId();
        
        $customerModel = $this->getServiceLocator()->get('CustomerModelWrapper')->get($this->getUserAccountingVendor()); // Qb
        $confirmationArray = $customerModel->createCustomer($data);
        
        $customer = new \Application\Entity\Customer();

        if (isset($data['user_id'])) {
            $customer->setUserId($data['user_id']);
        }
        if (isset($confirmationArray['vendor_customer_id'])) {
            $customer->setVendorCustomerId($confirmationArray['vendor_customer_id']);
        }
        if (isset($confirmationArray['vat_id'])) {
            $customer->setVatId($confirmationArray['vat_id']);
        }
        if (isset($confirmationArray['sync_token'])) {
            $customer->setSyncToken($confirmationArray['sync_token']);
        }
        
        // Below are user input
        if (isset($confirmationArray['customer_name'])) {
            $customer->setCustomerName($confirmationArray['customer_name']);
        }
        if (isset($confirmationArray['display_name'])) {
            $customer->setDisplayName($confirmationArray['display_name']);
        }
        if (isset($confirmationArray['name'])) {
            $customer->setName($confirmationArray['name']);
        }
        if (isset($confirmationArray['surname'])) {
            $customer->setSurname($confirmationArray['surname']);
        }
        if (isset($confirmationArray['address1'])) {
            $customer->setAddress1($confirmationArray['address1']);
        }
        if (isset($confirmationArray['address2'])) {
            $customer->setAddress2($confirmationArray['address2']);
        }
        if (isset($confirmationArray['city'])) {
            $customer->setCity($confirmationArray['city']);
        }
        if (isset($confirmationArray['postcode'])) {
            $customer->setPostcode($confirmationArray['postcode']);
        }
        if (isset($confirmationArray['country'])) {
            $customer->setCountry($confirmationArray['country']);
        }

        try {
            $this->getObjectManager()->persist($customer);
            $this->getObjectManager()->flush();
        } catch(\Exception $e) {
            // TODO: implement #36
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG . $e->getMessage(), 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }            
        
        $hydrator = $this->getHydrator();
        $customerArray = $hydrator->extract($customer);
        
        $customerArray = $customerModel->dbVendorFieldFilter($customerArray); // New Sage
        
        return $customerArray;
    }

    public function updateEntity($id, $data)
    {
        try {
            $customer = $this->getObjectManager()->find('\Application\Entity\Customer', $id);
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }
        if (!$customer) {
            throw new PlugintoException(PlugintoException::INVALID_COMPANY_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_COMPANY_ID_ERROR_CODE);
        }
        if (!$this->getUserId() || $customer->getUserId() != $this->getUserId()) {
            throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_USER_ID_ERROR_CODE);
        }
        $data['user_id'] = $this->getUserId();
        
        $customerModel = $this->getServiceLocator()->get('CustomerModelWrapper')->get($this->getUserAccountingVendor());
        $confirmationArray = $customerModel->updateCustomer($customer, $data);
        
        if (isset($confirmationArray['vat_id'])) {
            $customer->setVatId($confirmationArray['vat_id']);
        }
        if (isset($confirmationArray['customer_name'])) {
            $customer->setCustomerName($confirmationArray['customer_name']);
        }
        if (isset($confirmationArray['display_name'])) {
            $customer->setDisplayName($confirmationArray['display_name']);
        }
        if (isset($confirmationArray['name'])) {
            $customer->setName($confirmationArray['name']);
        }
        if (isset($confirmationArray['surname'])) {
            $customer->setSurname($confirmationArray['surname']);
        }
        if (isset($confirmationArray['address1'])) {
            $customer->setAddress1($confirmationArray['address1']);
        }
        if (isset($confirmationArray['address2'])) {
            $customer->setAddress2($confirmationArray['address2']);
        }
        if (isset($confirmationArray['city'])) {
            $customer->setCity($confirmationArray['city']);
        }
        if (isset($confirmationArray['postcode'])) {
            $customer->setPostcode($confirmationArray['postcode']);
        }
        if (isset($confirmationArray['country'])) {
            $customer->setCountry($confirmationArray['country']);
        }
        if (isset($confirmationArray['sync_token'])) {
            $customer->setSyncToken($confirmationArray['sync_token']);
        }

        try {
            $this->getObjectManager()->persist($customer);
            $this->getObjectManager()->flush();
        } catch(\Exception $e) {
            // TODO: implement #36
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }
        
        $hydrator = $this->getHydrator();
        $customerArray = $hydrator->extract($customer);
        
        $customerArray = $customerModel->dbVendorFieldFilter($customerArray); // New Sage
        
        return $customerArray;
    }

    public function deleteEntity($id)
    {
        try {
            $customer = $this->getObjectManager()->find('\Application\Entity\Customer', $id);
            if (!$customer) {
                throw new PlugintoException(PlugintoException::INVALID_COMPANY_ID_ERROR_MSG, 
                                            PlugintoException::INVALID_COMPANY_ID_ERROR_CODE);
            }
            if (!$this->getUserId() || $customer->getUserId() != $this->getUserId()) {
                throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                                            PlugintoException::INVALID_USER_ID_ERROR_CODE);
            }
            
            $hydrator = $this->getHydrator();
            $customerArray = $hydrator->extract($customer);
            
            // Db transaction begins
            $this->getObjectManager()->beginTransaction();
            
            $this->getObjectManager()->remove($customer);
            $this->getObjectManager()->flush();
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }
        
        $customerModel = $this->getServiceLocator()->get('CustomerModelWrapper')->get($this->getUserAccountingVendor());
        try {
            $confirmationArray = $customerModel->deleteCustomer($customer, $customerArray);
        } catch(\Exception $e) {
            $this->getObjectManager()->rollback();
            throw new PlugintoException($e->getMessage(), $e->getCode());
        }
        
        // Db transaction commits
        $this->getObjectManager()->commit();
        
        return $confirmationArray;
    }
}