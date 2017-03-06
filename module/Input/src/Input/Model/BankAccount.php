<?php
namespace Input\Model;

use Application\Model\AbstractModel;
use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;
use lib\exception\PlugintoException;

class BankAccount extends AbstractModel
{
    /* TODO: use PHPDoc's DocBlock */

    protected function getHydrator()
    {
        if (!$this->_hydrator) {
            $objectManager = $this->getObjectManager();
            $this->_hydrator = new DoctrineHydrator($objectManager, 'Application\Entity\BankAccount', false);
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
            $bankAccountAccountingModel = $this->getServiceLocator()->get('BankAccountModelWrapper')->get($this->getUserAccountingVendor()); //Qb
            $vendorBankAccountArr = $bankAccountAccountingModel->getBankAccount($this->getUserId());
            $dataArray = array();
            if (!empty($vendorBankAccountArr->Results)) {
                foreach($vendorBankAccountArr->Results as $eachObj) {
                    $dataArray[] = $bankAccountAccountingModel->prepareReturnArray($eachObj);
                }
            }
            
            return $dataArray;
        }

        try {
            $bankAccounts = $this->getObjectManager()->getRepository('\Application\Entity\BankAccount')->findBy(array('user_id' => $this->getUserId()));
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG . $e->getMessage(), 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }

        $hydrator = $this->getHydrator();
        $dataArray = array();
        if ($bankAccounts) {
            $bankAccountModel = $this->getServiceLocator()->get('BankAccountModelWrapper')->get($this->getUserAccountingVendor()); // Qb
            foreach ($bankAccounts as $bankAccount) {
                $eachData = $hydrator->extract($bankAccount);
                $eachData = $bankAccountModel->dbVendorFieldFilter($eachData); // New Sage
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
            $bankAccounts = $this->getObjectManager()->getRepository('\Application\Entity\BankAccount')->findBy(array('user_id' => $this->getUserId()));
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }

        // nned to implement dbVendorFieldFilter type method
        
        return $bankAccounts;
    }
    
    public function getEntityById($id)
    {
        try {
            $bankAccount = $this->getObjectManager()->find('\Application\Entity\BankAccount', $id);
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }
        if (!$bankAccount) {
            throw new PlugintoException(PlugintoException::INVALID_BANK_ACCOUNT_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_BANK_ACCOUNT_ID_ERROR_CODE);
        }
        if (!$this->getUserId() || $bankAccount->getUserId() != $this->getUserId()) {
            throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_USER_ID_ERROR_CODE);
        }
        
        $hydrator = $this->getHydrator();
        $dataArray = $hydrator->extract($bankAccount);

        $bankAccountModel = $this->getServiceLocator()->get('BankAccountModelWrapper')->get($this->getUserAccountingVendor()); // Qb
        $dataArray = $bankAccountModel->dbVendorFieldFilter($dataArray); // New Sage
                
        return $dataArray;
    }
    
    public function getEntityBySupplierId($id)
    {
        try {
            $bankAccount = $this->getObjectManager()->getRepository('\Application\Entity\BankAccount')->findOneBy(array('vendor_bankAccount_id' => $id));
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }
        if (!$bankAccount) {
            throw new PlugintoException(PlugintoException::INVALID_BANK_ACCOUNT_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_BANK_ACCOUNT_ID_ERROR_CODE);
        }
        if (!$this->getUserId() || $bankAccount->getUserId() != $this->getUserId()) {
            throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_USER_ID_ERROR_CODE);
        }
        
        $hydrator = $this->getHydrator();
        $dataArray = $hydrator->extract($bankAccount);
        
        $bankAccountModel = $this->getServiceLocator()->get('BankAccountModelWrapper')->get($this->getUserAccountingVendor()); // Qb
        $dataArray = $bankAccountModel->dbVendorFieldFilter($dataArray); // New Sage

        return $dataArray;
    }

    public function createEntity($data)
    {
        if (!$this->getUserId()) {
            throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_USER_ID_ERROR_CODE);
        }
        $data['user_id'] = $this->getUserId();
        
//        if (empty($data['bankAccount_type'])) {
//            throw new PlugintoException(PlugintoException::MISSING_REQUIRED_FIELD_BANK_ACCOUNT_TYPE_ERROR_MSG, 
//                                        PlugintoException::MISSING_REQUIRED_FIELD_BANK_ACCOUNT_TYPE_ERROR_CODE);
//        }

        $bankAccountModel = $this->getServiceLocator()->get('BankAccountModelWrapper')->get($this->getUserAccountingVendor()); // Qb
        $confirmationArray = $bankAccountModel->createBankAccount($data);
        
        $bankAccount = new \Application\Entity\BankAccount();

        if (isset($data['user_id'])) {
            $bankAccount->setUserId($data['user_id']);
        }
        if (isset($confirmationArray['vendor_bank_account_id'])) {
            $bankAccount->setVendorBankAccountId($confirmationArray['vendor_bank_account_id']);
        }
        if (isset($confirmationArray['account_name'])) {
            $bankAccount->setAccountName($confirmationArray['account_name']);
        }
        if (isset($confirmationArray['account_number'])) {
            $bankAccount->setAccountNumber($confirmationArray['account_number']);
        }
        if (isset($confirmationArray['bank_name'])) {
            $bankAccount->setBankName($confirmationArray['bank_name']);
        }
        if (isset($confirmationArray['branch_name'])) {
            $bankAccount->setBranchName($confirmationArray['branch_name']);
        }

        try {
            echo '<pre>';
            print_r($bankAccount);
            echo '</pre>';
            //die();
            $this->getObjectManager()->persist($bankAccount);
            $this->getObjectManager()->flush();
        } catch(\Exception $e) {
            // TODO: implement #36
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG . $e->getMessage(), 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }            
        
        $hydrator = $this->getHydrator();
        $bankAccountArray = $hydrator->extract($bankAccount);
        
        $bankAccountArray = $bankAccountModel->dbVendorFieldFilter($bankAccountArray); // New Sage
        
        return $bankAccountArray;
    }

    public function updateEntity($id, $data)
    {
        try {
            $bankAccount = $this->getObjectManager()->find('\Application\Entity\BankAccount', $id);
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }
        if (!$bankAccount) {
            throw new PlugintoException(PlugintoException::INVALID_BANK_ACCOUNT_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_BANK_ACCOUNT_ID_ERROR_CODE);
        }
        if (!$this->getUserId() || $bankAccount->getUserId() != $this->getUserId()) {
            throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_USER_ID_ERROR_CODE);
        }
        $data['user_id'] = $this->getUserId();
        
        $bankAccountModel = $this->getServiceLocator()->get('BankAccountModelWrapper')->get($this->getUserAccountingVendor());
        $confirmationArray = $bankAccountModel->updateBankAccount($bankAccount, $data);
        
        if (isset($confirmationArray['account_name'])) {
            $bankAccount->setAccountName($confirmationArray['account_name']);
        }
        if (isset($confirmationArray['account_number'])) {
            $bankAccount->setDisplayName($confirmationArray['account_number']);
        }
        if (isset($confirmationArray['bank_name'])) {
            $bankAccount->setVatId($confirmationArray['bank_name']);
        }
        if (isset($confirmationArray['branch_name'])) {
            $bankAccount->setBranchName($confirmationArray['branch_name']);
        }

        try {
            $this->getObjectManager()->persist($bankAccount);
            $this->getObjectManager()->flush();
        } catch(\Exception $e) {
            // TODO: implement #36
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }
        
        $hydrator = $this->getHydrator();
        $bankAccountArray = $hydrator->extract($bankAccount);
        
        $bankAccountArray = $bankAccountModel->dbVendorFieldFilter($bankAccountArray); // New Sage
        
        return $bankAccountArray;
    }

    public function deleteEntity($id)
    {
        try {
            $bankAccount = $this->getObjectManager()->find('\Application\Entity\BankAccount', $id);
            if (!$bankAccount) {
                throw new PlugintoException(PlugintoException::INVALID_BANK_ACCOUNT_ID_ERROR_MSG, 
                                            PlugintoException::INVALID_BANK_ACCOUNT_ID_ERROR_CODE);
            }
            if (!$this->getUserId() || $bankAccount->getUserId() != $this->getUserId()) {
                throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                                            PlugintoException::INVALID_USER_ID_ERROR_CODE);
            }
            
            $hydrator = $this->getHydrator();
            $bankAccountArray = $hydrator->extract($bankAccount);
            
            // Db transaction begins
            $this->getObjectManager()->beginTransaction();
            
            $this->getObjectManager()->remove($bankAccount);
            $this->getObjectManager()->flush();
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }
        
        $bankAccountModel = $this->getServiceLocator()->get('BankAccountModelWrapper')->get($this->getUserAccountingVendor());
        try {
            $confirmationArray = $bankAccountModel->deleteBankAccount($bankAccount, $bankAccountArray);
        } catch(\Exception $e) {
            $this->getObjectManager()->rollback();
            throw new PlugintoException($e->getMessage(), $e->getCode());
        }
        
        // Db transaction commits
        $this->getObjectManager()->commit();
        
        return $confirmationArray;
    }
}