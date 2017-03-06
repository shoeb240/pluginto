<?php
namespace Input\Model;

use Application\Model\AbstractModel;
use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;
use lib\exception\PlugintoException;

class Account extends AbstractModel
{
    /* TODO: use PHPDoc's DocBlock */

    public function getHydrator()
    {
        if (!$this->_hydrator) {
            $objectManager = $this->getObjectManager();
            $this->_hydrator = new DoctrineHydrator($objectManager, 'Application\Entity\Account', false);
        }

        return $this->_hydrator;
    }
    
    public function fetchAll()
    {
        if (!$this->getUserId()) {
            throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                      PlugintoException::INVALID_USER_ID_ERROR_CODE);
        }
        
        if ($this->getFromVendor()) {
            $accountAccountingModel = $this->getServiceLocator()->get('AccountModelWrapper')->get($this->getUserAccountingVendor()); //Qb
            $vendorAccountArr = $accountAccountingModel->getAccount($this->getUserId());
            $dataArray = array();
            if (!empty($vendorAccountArr->Results)) {
                foreach($vendorAccountArr->Results as $eachObj) {
                    $dataArray[] = $accountAccountingModel->prepareReturnArray($eachObj);
                }
            }

            return $dataArray;
        }

        try {
            $accounts = $this->getObjectManager()->getRepository('\Application\Entity\Account')->findBy(array('user_id' => $this->getUserId()));
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }

        $hydrator = $this->getHydrator();
        $dataArray = array();
        if ($accounts) {
            $accountModel = $this->getServiceLocator()->get('AccountModelWrapper')->get($this->getUserAccountingVendor()); // Qb
            foreach ($accounts as $account) {
                $eachData = $hydrator->extract($account);
                $eachData = $accountModel->dbVendorFieldFilter($eachData); // New Sage
                $dataArray[] = $eachData;
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
            $accounts = $this->getObjectManager()->getRepository('\Application\Entity\Account')->findBy(array('user_id' => $this->getUserId()));
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }
        
        return $accounts;
    }
    
    public function getAllVendorAccountId()
    {
        if (!$this->getUserId()) {
            throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                      PlugintoException::INVALID_USER_ID_ERROR_CODE);
        }
        
        try {
            $accounts = $this->getObjectManager()->getRepository('\Application\Entity\Account')->findBy(array('user_id' => $this->getUserId()));
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }

        $dataArray = array();
        if ($accounts) {
            foreach ($accounts as $account) {
                $dataArray[$account->getId()] = $account->getVendorAccountId();
            }
        }
        
        return $dataArray;
    }

    public function getEntityById($id)
    {
        try {
            $account = $this->getObjectManager()->find('\Application\Entity\Account', $id);
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }
        if (!$account) {
            throw new PlugintoException(PlugintoException::INVALID_ACCOUNT_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_ACCOUNT_ID_ERROR_CODE);
        }
        if (!$this->getUserId() || $account->getUserId() != $this->getUserId()) {
            throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_USER_ID_ERROR_CODE);
        }
        
        $hydrator = $this->getHydrator();
        $dataArray = $hydrator->extract($account);
        
        $accountModel = $this->getServiceLocator()->get('AccountModelWrapper')->get($this->getUserAccountingVendor()); // Qb
        $dataArray = $accountModel->dbVendorFieldFilter($dataArray); // New Sage

        return $dataArray;
    }
    
    public function createEntity($data)
    {
        if (!$this->getUserId()) {
            throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_USER_ID_ERROR_CODE);
        }
        $data['user_id'] = $this->getUserId();

        $accountModel = $this->getServiceLocator()->get('AccountModelWrapper')->get($this->getUserAccountingVendor());
        $confirmationArray = $accountModel->createAccount($data);

        $account = new \Application\Entity\Account();

        if (isset($confirmationArray['vendor_account_id'])) {
            $account->setVendorAccountId($confirmationArray['vendor_account_id']);
        }
        if (isset($data['user_id'])) {
            $account->setUserId($data['user_id']);
        }
        if (isset($confirmationArray['name'])) {
            $account->setName($confirmationArray['name']);
        }
        if (isset($confirmationArray['category_id'])) {
            $account->setCategoryId($confirmationArray['category_id']);
        }
        if (isset($confirmationArray['category_description'])) {
            $account->setCategoryDescription($confirmationArray['category_description']);
        }
        if (isset($confirmationArray['account_type'])) {
            $account->setAccountType($confirmationArray['account_type']);
        }
        if (isset($confirmationArray['account_sub_type'])) {
            $account->setAccountSubType($confirmationArray['account_sub_type']);
        }
        if (isset($confirmationArray['description'])) {
            $account->setDescription($confirmationArray['description']);
        }
        if (isset($confirmationArray['sync_token'])) {
            $account->setSyncToken($confirmationArray['sync_token']);
        }
        
        try {
            $this->getObjectManager()->persist($account);
            $this->getObjectManager()->flush();
        } catch(\Exception $e) {
            // TODO: implement #36
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG . $e->getMessage(), 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }            
        
        $hydrator = $this->getHydrator();
        $accountArray = $hydrator->extract($account);
        
        $accountArray = $accountModel->dbVendorFieldFilter($accountArray); // New Sage
        
        return $accountArray;
    }

    public function updateEntity($id, $data)
    {
        try {
            $account = $this->getObjectManager()->find('\Application\Entity\Account', $id);
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }
        if (!$account) {
            throw new PlugintoException(PlugintoException::INVALID_ACCOUNT_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_ACCOUNT_ID_ERROR_CODE);
        }
        if (!$this->getUserId() || $account->getUserId() != $this->getUserId()) {
            throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_USER_ID_ERROR_CODE);
        }        
        $data['user_id'] = $this->getUserId();
        
        $accountModel = $this->getServiceLocator()->get('AccountModelWrapper')->get($this->getUserAccountingVendor());
        $confirmationArray = $accountModel->updateAccountEntity($account, $data);
        
        if (isset($confirmationArray['vendor_account_id'])) {
            $account->setVendorAccountId($confirmationArray['vendor_account_id']);
        }
        if (isset($confirmationArray['name'])) {
            $account->setName($confirmationArray['name']);
        }
        if (isset($confirmationArray['category_id'])) {
            $account->setCategoryId($confirmationArray['category_id']);
        }
        if (isset($confirmationArray['category_description'])) {
            $account->setCategoryDescription($confirmationArray['category_description']);
        }
        if (isset($confirmationArray['account_type'])) {
            $account->setAccountType($confirmationArray['account_type']);
        }
        if (isset($confirmationArray['account_sub_type'])) {
            $account->setAccountSubType($confirmationArray['account_sub_type']);
        }
        if (isset($confirmationArray['description'])) {
            $account->setDescription($confirmationArray['description']);
        }
        if (isset($confirmationArray['sync_token'])) {
            $account->setSyncToken($confirmationArray['sync_token']);
        }

        try {
            $this->getObjectManager()->persist($account);
            $this->getObjectManager()->flush();
        } catch(\Exception $e) {
            // TODO: implement #36
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }
        
        $hydrator = $this->getHydrator();
        $accountArray = $hydrator->extract($account);
        
        $accountArray = $accountModel->dbVendorFieldFilter($accountArray); // New Sage
        
        return $accountArray;
    }

    public function deleteEntity($id)
    {
        try {
            $account = $this->getObjectManager()->find('\Application\Entity\Account', $id);
            if (!$account) {
                throw new PlugintoException(PlugintoException::INVALID_ACCOUNT_ID_ERROR_MSG, 
                                            PlugintoException::INVALID_ACCOUNT_ID_ERROR_CODE);
            }
            if (!$this->getUserId() || $account->getUserId() != $this->getUserId()) {
                throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                                            PlugintoException::INVALID_USER_ID_ERROR_CODE);
            }
            
            $hydrator = $this->getHydrator();
            $accountArray = $hydrator->extract($account);
            
            // Db transaction begins
            $this->getObjectManager()->beginTransaction();
            
            $this->getObjectManager()->remove($account);
            $this->getObjectManager()->flush();
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }
        
        $accountModel = $this->getServiceLocator()->get('AccountModelWrapper')->get($this->getUserAccountingVendor());
        try {
            $confirmationArray = $accountModel->deleteAccount($account, $accountArray);
        } catch(\Exception $e) {
            $this->getObjectManager()->rollback();
            throw new PlugintoException($e->getMessage(), $e->getCode());
        }
        
        // Db transaction commits
        $this->getObjectManager()->commit();
        
        return $confirmationArray;
    }
    
    public function createAll($userId, $objectArr)
    {
        if (empty($userId)) {
            throw new PlugintoException(PlugintoException::MISSING_REQUIRED_FIELD_USER_ID_ERROR_MSG, 
                                        PlugintoException::MISSING_REQUIRED_FIELD_USER_ID_ERROR_CODE);
        }

        $accountArray = array();
                
        foreach($objectArr as $ippAccount) {
            if (empty($ippAccount->Id)) {
                continue;
            }
            
            $account = new \Application\Entity\Account();

            if (isset($ippAccount->Id)) {
                $account->setVendorAccountId($ippAccount->Id);
            }
            if (isset($userId)) {
                $account->setUserId($userId);
            }
            if (isset($ippAccount->Name)) {
                $account->setName($ippAccount->Name);
            }
            if (isset($ippAccount->AccountType)) {
                $account->setAccountType($ippAccount->AccountType);
            }
            if (isset($ippAccount->AccountSubType)) {
                $account->setAccountSubType($ippAccount->AccountSubType);
            }
            $account->setSyncToken($ippAccount->SyncToken);
            
            $this->getObjectManager()->persist($account);
            
        }
        
        try {
            $this->getObjectManager()->flush();
        } catch(\Exception $e) {
            // TODO: implement #36
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG . $e->getMessage(), 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }
        
        return true;
    }
    
    public function updateAll($objectArr)
    {
        if (!$objectArr) return false;
        
        foreach($objectArr as $account) {
            $this->getObjectManager()->persist($account);
        }
        
        try {
            $this->getObjectManager()->flush();
        } catch(\Exception $e) {
            // TODO: implement #36
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG . $e->getMessage(), 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }
        
        return true;
    }
    
    public function deleteAll($objectArr)
    {
        if (!$objectArr) return false;
        
        foreach($objectArr as $account) {
            $this->getObjectManager()->remove($account);
        }
        
        try {
            $this->getObjectManager()->flush();
        } catch(\Exception $e) {
            // TODO: implement #36
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG . $e->getMessage(), 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }
        
        return true;
    }
    
}