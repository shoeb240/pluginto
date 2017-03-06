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
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG . $e->getMessage(), 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }    

        $hydrator = $this->getHydrator();
        $dataArray = array();
        if ($transactions) {
            $accountingModel = $this->getServiceLocator()->get('AccountingModelWrapper')->get($this->getUserAccountingVendor()); // Qb
            foreach ($transactions as $transaction) {
                $eachData = $hydrator->extract($transaction);
                $eachData = $accountingModel->dbVendorFieldFilterTransaction($eachData); // New Sage
                $dataArray[] = $eachData;
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

        $accountingModel = $this->getServiceLocator()->get('AccountingModelWrapper')->get($this->getUserAccountingVendor()); // Qb
        $dataArray = $accountingModel->dbVendorFieldFilterTransaction($dataArray); // New Sage

        $transactionLineModel = $this->getServiceLocator()->get('TransactionLineModel');
        $transactionLineArray = $transactionLineModel->getEntityByTransactionId($id);
        $dataArray['Line'] = $transactionLineArray;
        
        return $dataArray;
    }

    public function createEntity($data)
    {
        if (!$this->getUserId()) {
            throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_USER_ID_ERROR_CODE);
        }
        $data['user_id'] = $this->getUserId();

        if (empty($data['customer_id']) && empty($data['supplier_id'])) {
            throw new PlugintoException(PlugintoException::MISSING_REQUIRED_FIELD_COMPANY_ID_ERROR_MSG, 
                                        PlugintoException::MISSING_REQUIRED_FIELD_COMPANY_ID_ERROR_CODE);
        }
        if (empty($data['account_id']) && empty($data['item_id']) && empty($data['bank_account_id'])) {
            throw new PlugintoException(PlugintoException::MISSING_REQUIRED_FIELD_ACCOUNT_ID_ERROR_MSG, 
                                        PlugintoException::MISSING_REQUIRED_FIELD_ACCOUNT_ID_ERROR_CODE);
        }

        if (empty($data['unit_price'])) {
            throw new PlugintoException(PlugintoException::MISSING_REQUIRED_FIELD_AMOUNT_ERROR_MSG, 
                                        PlugintoException::MISSING_REQUIRED_FIELD_AMOUNT_ERROR_CODE);
        }
        if (!isset($data['description'])) {
            $data['description'] = '';
        }
        
        $transaction = new \Application\Entity\Transaction();
        
        $dataArray = $data;
        
        if (isset($data['user_id'])) {
            $transaction->setUserId($data['user_id']);
        }
        if (isset($data['customer_id'])) {
            $transaction->setCustomerId($data['customer_id']);
        }
        if (isset($data['supplier_id'])) {
            $transaction->setSupplierId($data['supplier_id']);
        }
        if (isset($data['account_id_financial'])) {
            $transaction->setAccountId($data['account_id_financial']);
        }
        if (isset($data['bank_account_id'])) {
            $transaction->setBankAccountId($data['bank_account_id']);
        }
        if (isset($data['tax_id'])) {
            $transaction->setTaxId($data['tax_id']);
        }
        if (isset($data['tax_percent_total'])) {
            $transaction->setTaxPercentTotal($data['tax_percent_total']);
        }
        if (isset($data['tax_amount_total'])) {
            $transaction->setTaxAmountTotal($data['tax_amount_total']);
        }
        if (isset($data['discount_percent_total'])) {
            $transaction->setDiscountPercentTotal($data['discount_percent_total']);
        }
        if (isset($data['discount_amount_total'])) {
            $transaction->setDiscountAmountTotal($data['discount_amount_total']);
        }
        if (isset($data['amount'])) {
            $transaction->setAmount($data['amount']);
        }
        if (isset($data['currency'])) {
            $transaction->setCurrency($data['currency']);
        } else {
            $transaction->setCurrency('USD');
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
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG . $e->getMessage(), 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }
        
        //$hydrator = $this->getHydrator();
        //$dataArray = $hydrator->extract($transaction);
        
        if (isset($data['item_id']) || isset($data['account_id'])) {
            $invoice_line_model = $this->getServiceLocator()->get('TransactionLineModel');
            $invoice_line_model->setObjectManager($this->getObjectManager());
            
            $count = max(count($data['item_id']), count($data['account_id']));
            
            for($k = 1; $k <= $count; $k++) {    
                
                if (empty($data['item_id'][$k]) && empty($data['account_id'][$k])) continue;
                $lineData = array();
                $lineData['transaction_id'] = $transaction->getId();
                $lineData['line_num'] = $k;

                if (isset($data['item_id'][$k])) {
                    $lineData['item_id'] = $data['item_id'][$k];
                }
                if (isset($data['account_id'][$k])) {
                    $lineData['account_id'] = $data['account_id'][$k];
                }
                if (isset($data['description'][$k])) {
                    $lineData['description'] = $data['description'][$k];
                }
                if (isset($data['quantity'][$k])) {
                    $lineData['quantity'] = $data['quantity'][$k];
                }
                if (isset($data['unit_price'][$k])) {
                    $lineData['unit_price'] = $data['unit_price'][$k];
                }
                if (isset($data['tax_percentage'][$k])) {
                    $lineData['tax_percentage'] = $data['tax_percentage'][$k];
                }
                if (isset($data['tax'][$k])) {
                    $lineData['tax'] = $data['tax'][$k];
                }
                if (isset($data['discount_percentage'][$k])) {
                    $lineData['discount_percentage'] = $data['discount_percentage'][$k];
                }
                if (isset($data['discount'][$k])) {
                    $lineData['discount'] = $data['discount'][$k];
                }
                if (isset($data['unit_price'][$k])) {
                    $lineData['unit_price'] = $data['unit_price'][$k];
                }
                if (isset($data['detail_type'][$k])) {
                    $lineData['detail_type'] = $data['detail_type'][$k];
                }
                
                $invoice_line_model->createEntity($lineData);
                
            }
            
            if (!empty($data['discount_percent_total']) || !empty($data['discount_amount_total'])) {
                $invoice_line_model = $this->getServiceLocator()->get('TransactionLineModel');
                $invoice_line_model->setObjectManager($this->getObjectManager());
                $lineData = array();

                $lineData['transaction_id'] = $transaction->getId();
                $lineData['description'] = 'Discount';
                if (!empty($data['discount_percent_total'])) {
                    $lineData['discount_percentage'] = $data['discount_percent_total'];
                }
                if (!empty($data['discount_amount_total'])) {
                    $lineData['discount'] = $data['discount_amount_total'];
                }

                $invoice_line_model->createEntity($lineData);
            }

        }
        
        
        $dataArray['id'] = $transaction->getId();
        $accounting = $this->getServiceLocator()->get('AccountingModelWrapper')->get($this->getUserAccountingVendor()); //Qb
        try {
            $confirmationArray = $accounting->createAccountingServices($this->getObjectManager(), $dataArray);
        // TODO: implement #36 within a new catch block for Db errors    
        } catch(\Exception $e) {
            $this->getObjectManager()->rollback();
            throw new PlugintoException($e->getMessage(), 
                                        $e->getCode());
        }
        
//        echo '<pre>';
//        print_r($transaction);
//        print_r($confirmationArray);
//        echo '</pre>';
        //die('here');
        
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

        // need some validation here
        if (isset($data['user_id'])) {
            $transaction->setUserId($data['user_id']);
        }
        if (isset($data['customer_id'])) {
            $transaction->setCustomerId($data['customer_id']);
        }
        if (isset($data['supplier_id'])) {
            $transaction->setSupplierId($data['supplier_id']);
        }
        if (isset($data['bank_account_id'])) {
            $transaction->setBankAccountId($data['bank_account_id']);
        }
        if (isset($data['tax_id'])) {
            $transaction->setTaxId($data['tax_id']);
        }
        if (isset($data['tax_percent_total'])) {
            $transaction->setTaxPercentTotal($data['tax_percent_total']);
        }
        if (isset($data['tax_amount_total'])) {
            $transaction->setTaxAmountTotal($data['tax_amount_total']);
        }
        if (isset($data['discount_percent_total'])) {
            $transaction->setDiscountPercentTotal($data['discount_percent_total']);
        }
        if (isset($data['discount_amount_total'])) {
            $transaction->setDiscountAmountTotal($data['discount_amount_total']);
        }
        if (isset($data['amount'])) {
            $transaction->setAmount($data['amount']);
        }
        if (isset($data['currency'])) {
            $transaction->setCurrency($data['currency']);
        } else {
            $transaction->setCurrency('USD');
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
        
        $accounting = $this->getServiceLocator()->get('AccountingModelWrapper')->get($this->getUserAccountingVendor());
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
        
        $accounting = $this->getServiceLocator()->get('AccountingModelWrapper')->get($this->getUserAccountingVendor());
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