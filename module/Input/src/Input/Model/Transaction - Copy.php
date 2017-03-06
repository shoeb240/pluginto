<?php
namespace Input\Model;

use Application\Entity\Amount;
use Application\Model\AbstractModel;
use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;
use lib\exception\PlugintoException;

class Transaction extends AbstractModel
{
    /* TODO: use PHPDoc's DocBlock */
    protected $_amount;
    private $_validator;

    protected function getHydrator()
    {
        if (!$this->_hydrator) {
            $objectManager = $this->getObjectManager();
            $this->_hydrator = new DoctrineHydrator($objectManager, 'Application\Entity\Transaction', false);
        }

        return $this->_hydrator;
    }
    
    // used for unittest
    public function setAmount(Amount $amount)
    {
        $this->_amount = $amount;
    }
    
    private function _getValidator()
    {
        if (!$this->_validator) {
            $this->_validator = new \Input\Model\Validator($this->getServiceLocator());
        }
        
        return $this->_validator;
    }

    // used in InputController getList()
    public function fetchAll()
    {
        if (!$this->getUserId()) {
            throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                      PlugintoException::INVALID_USER_ID_ERROR_CODE);
        }
        
        try {
            $transactions = $this->getObjectManager()->getRepository('\Application\Entity\Transaction')->findBy(array('user_id' => $this->getUserId()));
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }    

        $hydrator = $this->getHydrator();
        $dataArray = array();
        if ($transactions) {
            foreach ($transactions as $transaction) {
                $dataArray[] = $hydrator->extract($transaction);
            }
        }
        
        return $dataArray;
    }

    public function fetchAllServiceObj($serviceType)
    {
        if (!$this->getUserId()) {
            throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                      PlugintoException::INVALID_USER_ID_ERROR_CODE);
        }
        
        try {
            $service = $this->getObjectManager()->getRepository('\Application\Entity\Service')->findBy(array('user_id' => $this->getUserId(), 'service_type' => $serviceType));
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG . $e->getMessage(), 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }

        return $service;
    }
    
    public function getEntityById($id)
    {
        try {
            $transaction = $this->getObjectManager()->find('\Application\Entity\Transaction', $id);
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }    
        if (!$transaction) {
            throw new PlugintoException(PlugintoException::INVALID_TRANSACTION_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_TRANSACTION_ID_ERROR_CODE);
        }
        if (!$this->getUserId() || $transaction->getUserId() != $this->getUserId()) {
            throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_USER_ID_ERROR_CODE);
        }

        $hydrator = $this->getHydrator();
        $dataArray = $hydrator->extract($transaction);

        return $dataArray;
    }

    public function createEntity($data)
    {
        if (!$this->getUserId()) {
            throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_USER_ID_ERROR_CODE);
        }
        $data['user_id'] = $this->getUserId();
        
        if (empty($data['company_id'])) {
            throw new PlugintoException(PlugintoException::MISSING_REQUIRED_FIELD_COMPANY_ID_ERROR_MSG, 
                                        PlugintoException::MISSING_REQUIRED_FIELD_COMPANY_ID_ERROR_CODE);
        }
        if (empty($data['account_id'])) {
            throw new PlugintoException(PlugintoException::MISSING_REQUIRED_FIELD_ACCOUNT_ID_ERROR_MSG, 
                                        PlugintoException::MISSING_REQUIRED_FIELD_ACCOUNT_ID_ERROR_CODE);
        }
        if (empty($data['amount'])) {
            throw new PlugintoException(PlugintoException::MISSING_REQUIRED_FIELD_AMOUNT_ERROR_MSG, 
                                        PlugintoException::MISSING_REQUIRED_FIELD_AMOUNT_ERROR_CODE);
        }
        if (!isset($data['description'])) {
            $data['description'] = '';
        }
        
        $transaction = new \Application\Entity\Transaction();
        
        if (isset($data['user_id'])) {
            $transaction->setUserId($data['user_id']);
        }
        if (isset($data['company_id'])) {
            $transaction->setCompanyId($data['company_id']);
        }
        if (isset($data['description'])) {
            $transaction->setDescription($data['description']);
        }
        if (isset($data['account_id'])) {
            $transaction->setAccountId($data['account_id']);
        }
        if (isset($data['bank_account_id'])) {
            $transaction->setBankAccountId($data['bank_account_id']);
        }
        if (isset($data['tax_id'])) {
            $transaction->setTaxId($data['tax_id']);
        }
        if (isset($data['amount'])) {
            $amount = new Amount();
            $amount->setValue($data['amount']);
            if (isset($data['currency'])) {
                $amount->setCurrency($data['currency']);
            } else {
                $amount->setCurrency('USD');
            }
            $transaction->setAmount($amount);
        }
        if (isset($data['payment_made'])) {
            $transaction->setPaymentMade($data['payment_made']);
        }
        if (isset($data['payment_type'])) {
            $transaction->setPaymentType($data['payment_type']);
        }
        $transaction->setDatetime(new \DateTime("now"));

        // Db transaction begins
        $this->getObjectManager()->beginTransaction();
        
        try {
            $this->getObjectManager()->persist($transaction);
            $this->getObjectManager()->flush();
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }    
        
        $hydrator = $this->getHydrator();
        $dataArray = $hydrator->extract($transaction);
        
        $accounting = $this->getServiceLocator()->get('AccountingModelWrapper')->get('Qb');
        try {
            $confirmationArray = $accounting->createAccountingServices($this->getObjectManager(), $dataArray);
        // TODO: implement #36 within a new catch block for Db errors    
        } catch(\Exception $e) {
            $this->getObjectManager()->rollback();
            throw new PlugintoException($e->getMessage(), 
                                        $e->getCode());
        }
        
        // Db transaction commits
        $this->getObjectManager()->commit();

        return $confirmationArray;
    }

    public function updateEntity($id, $data)
    {
        try {
            $transaction = $this->getObjectManager()->find('\Application\Entity\Transaction', $id);
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }
        if (!$transaction) {
            throw new PlugintoException(PlugintoException::INVALID_TRANSACTION_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_TRANSACTION_ID_ERROR_CODE);
        }
        if (!$this->getUserId() || $transaction->getUserId() != $this->getUserId()) {
            throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_USER_ID_ERROR_CODE);
        }

        $amount = $transaction->getAmount();
        if ($data['amount']) {
            $amount->setValue($data['amount']);
        }
        if ($data['currency']) {
            $amount->setCurrency($data['currency']);
        }

        // need some validation here
        if (isset($data['user_id'])) {
            $transaction->setUserId($data['user_id']);
        }
        if (isset($data['company_id'])) {
            $transaction->setCompanyId($data['company_id']);
        }
        if (isset($data['description'])) {
            $transaction->setDescription($data['description']);
        }
        if (isset($data['account_id'])) {
            $transaction->setAccountId($data['account_id']);
        }
        if (isset($data['bank_account_id'])) {
            $transaction->setBankAccountId($data['bank_account_id']);
        }
        if (isset($data['tax_id'])) {
            $transaction->setTaxId($data['tax_id']);
        }
        if ($data['amount'] || $data['currency']) {
            $transaction->setAmount($amount);
        }
        if (isset($data['payment_made'])) {
            $transaction->setPaymentMade($data['payment_made']);
        }
        if (isset($data['payment_type'])) {
            $transaction->setPaymentType($data['payment_type']);
        }
        $transaction->setDatetime(new \DateTime("now"));

        // Db transaction begins
        $this->getObjectManager()->beginTransaction();
        
        try {
            $this->getObjectManager()->persist($transaction);
            $this->getObjectManager()->flush();
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }    

        $hydrator = $this->getHydrator();
        $dataArray = $hydrator->extract($transaction);
        
        try {
            $service = $this->getObjectManager()->getRepository('\Application\Entity\Service')->findOneBy(array('transaction_id' => $id));
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }
        if (!$service) {
            throw new PlugintoException(PlugintoException::INVALID_TRANSACTION_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_TRANSACTION_ID_ERROR_CODE);
        }
        
        $accounting = $this->getServiceLocator()->get('AccountingModelWrapper')->get('Qb');
        try {
            $confirmationArray = $accounting->updateAccountingServices($service, $this->getObjectManager(), $dataArray);
        // TODO: implement #36 within a new catch block for Db errors                
        } catch(\Exception $e) {
            $this->getObjectManager()->rollback();
            throw new PlugintoException($e->getMessage(), 
                                        $e->getCode());
        }
        
        // Db transaction commits
        $this->getObjectManager()->commit();

        return $confirmationArray;
    }

    public function deleteEntity($id)
    {
        try {
            $transaction = $this->getObjectManager()->find('\Application\Entity\Transaction', $id);
            if (!$transaction) {
                throw new PlugintoException(PlugintoException::INVALID_TRANSACTION_ID_ERROR_MSG, 
                                            PlugintoException::INVALID_TRANSACTION_ID_ERROR_CODE);
            } 
            if (!$this->getUserId() || $transaction->getUserId() != $this->getUserId()) {
                throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                                            PlugintoException::INVALID_USER_ID_ERROR_CODE);
            }
            
            $hydrator = $this->getHydrator();
            $transactionArray = $hydrator->extract($transaction);
        
            // Db transaction begins
            $this->getObjectManager()->beginTransaction();
            
            $this->getObjectManager()->remove($transaction);
            $this->getObjectManager()->flush();

        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }
        
        $accounting = $this->getServiceLocator()->get('AccountingModelWrapper')->get('Qb');
        try {
            $service = $this->getObjectManager()->getRepository('\Application\Entity\Service')->findOneBy(array('transaction_id' => $id));
            $confirmationArray = $accounting->deleteAccountingServices($service, $this->getObjectManager(), $transactionArray);
        } catch(\Exception $e) {
            $this->getObjectManager()->rollback();
            throw new PlugintoException($e->getMessage(), $e->getCode());
        }
        
        // Db transaction commits
        $this->getObjectManager()->commit();
        
        return $confirmationArray;
    }
}