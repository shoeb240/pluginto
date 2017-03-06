<?php
namespace Input\Model;

use Application\Model\AbstractModel;
use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;
use lib\exception\PlugintoException;

class Company extends AbstractModel
{
    /* TODO: use PHPDoc's DocBlock */

    protected function getHydrator()
    {
        if (!$this->_hydrator) {
            $objectManager = $this->getObjectManager();
            $this->_hydrator = new DoctrineHydrator($objectManager, 'Application\Entity\Company', false);
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
            $companyArray['user_id'] = $this->getUserId();
            $companyAccountingModel = $this->getServiceLocator()->get('CompanyModelWrapper')->get($this->getUserAccountingVendor()); //Qb
            $vendorCompanyArr = $companyAccountingModel->getCustomer($companyArray);
            foreach($vendorCompanyArr->Results as $eachObj) {
                $dataArray[] = $companyAccountingModel->prepareReturnArray($eachObj);
            }
            
            return $dataArray;
        }
        
        try {
            $companies = $this->getObjectManager()->getRepository('\Application\Entity\Company')->findBy(array('user_id' => $this->getUserId()));
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }

        $hydrator = $this->getHydrator();
        $dataArray = array();
        if ($companies) {
            $companyModel = $this->getServiceLocator()->get('CompanyModelWrapper')->get($this->getUserAccountingVendor()); // Qb
            foreach ($companies as $company) {
                $eachData = $hydrator->extract($company);
                $eachVendorData = $companyModel->dbVendorFieldFilter($eachData); // New Sage
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
            $companies = $this->getObjectManager()->getRepository('\Application\Entity\Company')->findBy(array('user_id' => $this->getUserId()));
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
            $company = $this->getObjectManager()->find('\Application\Entity\Company', $id);
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }
        if (!$company) {
            throw new PlugintoException(PlugintoException::INVALID_COMPANY_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_COMPANY_ID_ERROR_CODE);
        }
        if (!$this->getUserId() || $company->getUserId() != $this->getUserId()) {
            throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_USER_ID_ERROR_CODE);
        }
        
        $hydrator = $this->getHydrator();
        $dataArray = $hydrator->extract($company);

        $companyModel = $this->getServiceLocator()->get('CompanyModelWrapper')->get($this->getUserAccountingVendor()); // Qb
        $dataArray = $companyModel->dbVendorFieldFilter($dataArray); // New Sage
                
        return $dataArray;
    }
    
    public function getEntityBySupplierId($id)
    {
        try {
            $company = $this->getObjectManager()->getRepository('\Application\Entity\Company')->findOneBy(array('vendor_company_id' => $id));
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }
        if (!$company) {
            throw new PlugintoException(PlugintoException::INVALID_COMPANY_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_COMPANY_ID_ERROR_CODE);
        }
        if (!$this->getUserId() || $company->getUserId() != $this->getUserId()) {
            throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_USER_ID_ERROR_CODE);
        }
        
        $hydrator = $this->getHydrator();
        $dataArray = $hydrator->extract($company);

        $companyModel = $this->getServiceLocator()->get('CompanyModelWrapper')->get($this->getUserAccountingVendor()); // Qb
        $dataArray = $companyModel->dbVendorFieldFilter($dataArray); // New Sage
        
        return $dataArray;
    }

    public function createEntity($data)
    {
        if (!$this->getUserId()) {
            throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_USER_ID_ERROR_CODE);
        }
        $data['user_id'] = $this->getUserId();
        
//        if (empty($data['company_type'])) {
//            throw new PlugintoException(PlugintoException::MISSING_REQUIRED_FIELD_COMPANY_TYPE_ERROR_MSG, 
//                                        PlugintoException::MISSING_REQUIRED_FIELD_COMPANY_TYPE_ERROR_CODE);
//        }

        $companyModel = $this->getServiceLocator()->get('CompanyModelWrapper')->get($this->getUserAccountingVendor()); // Qb
        $confirmationArray = $companyModel->createCompany($data);
        
        $company = new \Application\Entity\Company();

        if (isset($data['user_id'])) {
            $company->setUserId($data['user_id']);
        }
        if (isset($confirmationArray['vendor_company_id'])) {
            $company->setVendorCompanyId($confirmationArray['vendor_company_id']);
        }
        if (isset($confirmationArray['vat_id'])) {
            $company->setVatId($confirmationArray['vat_id']);
        }
        if (isset($confirmationArray['company_name'])) {
            $company->setCompanyName($confirmationArray['company_name']);
        }
        if (isset($confirmationArray['display_name'])) {
            $company->setDisplayName($confirmationArray['display_name']);
        }
        if (isset($confirmationArray['name'])) {
            $company->setName($confirmationArray['name']);
        }
        if (isset($confirmationArray['surname'])) {
            $company->setSurname($confirmationArray['surname']);
        }
        if (isset($confirmationArray['address1'])) {
            $company->setAddress1($confirmationArray['address1']);
        }
        if (isset($confirmationArray['address2'])) {
            $company->setAddress2($confirmationArray['address2']);
        }
        if (isset($confirmationArray['city'])) {
            $company->setCity($confirmationArray['city']);
        }
        if (isset($confirmationArray['postcode'])) {
            $company->setPostcode($confirmationArray['postcode']);
        }
        if (isset($confirmationArray['country'])) {
            $company->setCountry($confirmationArray['country']);
        }
        if (isset($data['company_type'])) {
            $company->setCompanyType($data['company_type']);
        }
        $company->setSyncToken($confirmationArray['sync_token']);

        try {
            $this->getObjectManager()->persist($company);
            $this->getObjectManager()->flush();
        } catch(\Exception $e) {
            // TODO: implement #36
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG . $e->getMessage(), 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }            
        
        $hydrator = $this->getHydrator();
        $companyArray = $hydrator->extract($company);
        
        return $companyArray;
    }

    public function updateEntity($id, $data)
    {
        try {
            $company = $this->getObjectManager()->find('\Application\Entity\Company', $id);
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }
        if (!$company) {
            throw new PlugintoException(PlugintoException::INVALID_COMPANY_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_COMPANY_ID_ERROR_CODE);
        }
        if (!$this->getUserId() || $company->getUserId() != $this->getUserId()) {
            throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_USER_ID_ERROR_CODE);
        }
        
        $companyModel = $this->getServiceLocator()->get('CompanyModelWrapper')->get('Qb');
        $confirmationArray = $companyModel->updateCompany($company, $data);
        
        if (isset($confirmationArray['vat_id'])) {
            $company->setVatId($confirmationArray['vat_id']);
        }
        if (isset($confirmationArray['company_name'])) {
            $company->setCompanyName($confirmationArray['company_name']);
        }
        if (isset($confirmationArray['display_name'])) {
            $company->setDisplayName($confirmationArray['display_name']);
        }
        if (isset($confirmationArray['name'])) {
            $company->setName($confirmationArray['name']);
        }
        if (isset($confirmationArray['surname'])) {
            $company->setSurname($confirmationArray['surname']);
        }
        if (isset($confirmationArray['address1'])) {
            $company->setAddress1($confirmationArray['address1']);
        }
        if (isset($confirmationArray['address2'])) {
            $company->setAddress2($confirmationArray['address2']);
        }
        if (isset($confirmationArray['city'])) {
            $company->setCity($confirmationArray['city']);
        }
        if (isset($confirmationArray['postcode'])) {
            $company->setPostcode($confirmationArray['postcode']);
        }
        if (isset($confirmationArray['country'])) {
            $company->setCountry($confirmationArray['country']);
        }
        if (isset($confirmationArray['sync_token'])) {
            $company->setSyncToken($confirmationArray['sync_token']);
        }

        try {
            $this->getObjectManager()->persist($company);
            $this->getObjectManager()->flush();
        } catch(\Exception $e) {
            // TODO: implement #36
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }
        
        $hydrator = $this->getHydrator();
        $companyArray = $hydrator->extract($company);
        
        return $companyArray;
    }

    public function deleteEntity($id)
    {
        try {
            $company = $this->getObjectManager()->find('\Application\Entity\Company', $id);
            if (!$company) {
                throw new PlugintoException(PlugintoException::INVALID_COMPANY_ID_ERROR_MSG, 
                                            PlugintoException::INVALID_COMPANY_ID_ERROR_CODE);
            }
            if (!$this->getUserId() || $company->getUserId() != $this->getUserId()) {
                throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                                            PlugintoException::INVALID_USER_ID_ERROR_CODE);
            }
            
            $hydrator = $this->getHydrator();
            $companyArray = $hydrator->extract($company);
            
            // Db transaction begins
            $this->getObjectManager()->beginTransaction();
            
            $this->getObjectManager()->remove($company);
            $this->getObjectManager()->flush();
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }
        
        $companyModel = $this->getServiceLocator()->get('CompanyModelWrapper')->get('Qb');
        try {
            $confirmationArray = $companyModel->deleteCompany($company, $companyArray);
        } catch(\Exception $e) {
            $this->getObjectManager()->rollback();
            throw new PlugintoException($e->getMessage(), $e->getCode());
        }
        
        // Db transaction commits
        $this->getObjectManager()->commit();
        
        return $confirmationArray;
    }
}