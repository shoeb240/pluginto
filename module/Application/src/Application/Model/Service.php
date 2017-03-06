<?php
namespace Application\Model;

use Application\Model\BaseModel;
use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;
use lib\exception\PlugintoException;

class Service extends BaseModel
{
    /* TODO: use PHPDoc's DocBlock */
    protected function getHydrator()
    {
        if (!$this->_hydrator) {
            $objectManager = $this->getObjectManager();
            $this->_hydrator = new DoctrineHydrator($objectManager, 'Application\Entity\Service', false);
        }

        return $this->_hydrator;
    }

    public function fetchAll()
    {
        if (!$this->getUserId()) {
            throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                      PlugintoException::INVALID_USER_ID_ERROR_CODE);
        }
        
        try {
            $services = $this->getObjectManager()->getRepository('\Application\Entity\Service')->findBy(array('user_id' => $this->getUserId()));
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }

        $hydrator = $this->getHydrator();
        $dataArray = array();
        if ($services) {
            $accountingModel = $this->getServiceLocator()->get('AccountingModelWrapper')->get($this->getUserAccountingVendor()); // Qb
            foreach ($services as $service) {
                $eachData = $hydrator->extract($service);
                $eachData = $accountingModel->dbVendorFieldFilterService($eachData); // New Sage
                $dataArray[] = $eachData;
            }
        }

        return $dataArray;
    }

    public function getEntityById($id)
    {
        try {
            $service = $this->getObjectManager()->find('\Application\Entity\Service', $id);
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }
        if (!$service) {
            throw new PlugintoException(PlugintoException::INVALID_INVOICE_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_INVOICE_ID_ERROR_CODE);
        }
        if (!$this->getUserId() || $service->getUserId() != $this->getUserId()) {
            throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_USER_ID_ERROR_CODE);
        }
        
        $hydrator = $this->getHydrator();
        $dataArray = $hydrator->extract($service);
        
        $accountingModel = $this->getServiceLocator()->get('AccountingModelWrapper')->get($this->getUserAccountingVendor()); // Qb
        $dataArray = $accountingModel->dbVendorFieldFilterService($dataArray); // New Sage
        
        $serviceLineModel = $this->getServiceLocator()->get('ServiceLineModel');
        $serviceLineArray = $serviceLineModel->getEntityByServiceId($id);
        
        $dataArray['Line'] = $serviceLineArray;
        
        return $dataArray;
    }

    public function createEntity($data)
    {
        if (!$this->getUserId()) {
            throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_USER_ID_ERROR_CODE);
        }
        $data['user_id'] = $this->getUserId();
        
        $service = new \Application\Entity\Service();

        if (isset($data['vendor_service_id'])) {
            $service->setVendorServiceId($data['vendor_service_id']);
        }
        if (isset($data['transaction_id'])) {
            $service->setTransactionId($data['transaction_id']);
        }
        if (isset($data['user_id'])) {
            $service->setUserId($data['user_id']);
        }
        if (isset($data['customer_id'])) {
            $service->setCustomerId($data['customer_id']);
        }
        if (isset($data['supplier_id'])) {
            $service->setSupplierId($data['supplier_id']);
        }
        if (isset($data['description'])) {
            $service->setDescription($data['description']);
        }
        if (isset($data['account_id'])) {
            $service->setAccountId($data['account_id']);
        }
        if (isset($data['bank_account_id'])) {
            $service->setBankAccountId($data['bank_account_id']);
        }
        if (isset($data['tax_id'])) {
            $service->setTaxId($data['tax_id']);
        }
        if (isset($data['doc_number'])) {
            $service->setDocNumber($data['doc_number']);
        }
        if (isset($data['service_type'])) {
            $service->setServiceType($data['service_type']);
        }
        if (isset($data['tax_percentage'])) {
            $service->setTaxPercentage($data['tax_percentage']);
        }
        if (isset($data['tax'])) {
            $service->setTax($data['tax']);
        }
        if (isset($data['discount_percentage'])) {
            $service->setDiscountPercentage($data['discount_percentage']);
        }
        if (isset($data['discount'])) {
            $service->setDiscount($data['discount']);
        }
        if (isset($data['balance'])) {
            $service->setBalance($data['balance']);
        }
        if (isset($data['total_amt'])) {
            $service->setTotalAmt($data['total_amt']);
        }
        if (isset($data['due_date'])) {
            $service->setDueDate($data['due_date']);
        }
        if (isset($data['txn_date'])) {
            $service->setTxnDate($data['txn_date']);
        }
        if (isset($data['sync_token'])) {
            $service->setSyncToken($data['sync_token']);
        }

        try {
            $this->getObjectManager()->persist($service);
            $this->getObjectManager()->flush();
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG . $e->getMessage(), 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }
        
        $hydrator = $this->getHydrator();
        $dataArray = $hydrator->extract($service);

        return $dataArray;
    }

    public function updateEntity($id, $data)
    {
        $service = $this->getObjectManager()->find('\Application\Entity\Service', $id);
        
        if (!$this->getUserId() || $service->getUserId() != $this->getUserId()) {
            throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_USER_ID_ERROR_CODE);
        }

        if (isset($data['vendor_service_id'])) {
            $service->setVendorServiceId($data['vendor_service_id']);
        }
        if (isset($data['doc_number'])) {
            $service->setDocNumber($data['doc_number']);
        }
        if (isset($data['balance'])) {
            $service->setBalance($data['balance']);
        }
        if (isset($data['total_amt'])) {
            $service->setTotalAmt($data['total_amt']);
        }
        if (isset($data['due_date'])) {
            $service->setDueDate($data['due_date']);
        }
        if (isset($data['txn_date'])) {
            $service->setTxnDate($data['txn_date']);
        }
        if (isset($data['sync_token'])) {
            $service->setSyncToken($data['sync_token']);
        }

        try {
            $this->getObjectManager()->persist($service);
            $this->getObjectManager()->flush();
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }
        
        $hydrator = $this->getHydrator();
        $dataArray = $hydrator->extract($service);

        return $dataArray;
    }

    public function deleteEntity($id)
    {
        try {
            $service = $this->getObjectManager()->find('\Application\Entity\Service', $id);
            
            if ($service) {
                if (!$this->getUserId() || $service->getUserId() != $this->getUserId()) {
                    throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                                                PlugintoException::INVALID_USER_ID_ERROR_CODE);
                }
            
                $this->getObjectManager()->remove($service);
                $this->getObjectManager()->flush();

                return true;
            }
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }

        // I probably need to raise an exception here
        return false;
    }
    
    public function createAll($userId, $objectArr, $serviceType)
    {
        if (empty($userId)) {
            throw new PlugintoException(PlugintoException::MISSING_REQUIRED_FIELD_USER_ID_ERROR_MSG, 
                                        PlugintoException::MISSING_REQUIRED_FIELD_USER_ID_ERROR_CODE);
        }

        $lineObjectArr = array();
        foreach($objectArr as $ippService) {
            if (empty($ippService->Id)) {
                continue;
            }
            
            $service = new \Application\Entity\Service();

            $service->setVendorServiceId($ippService->Id);
            $service->setTransactionId('');
            $service->setUserId($userId);
            if (isset($ippService->CustomerRef)) {
                $service->setCustomerId($ippService->CustomerRef);
            } else {
                $service->setCustomerId('');
            }
            if (isset($ippService->VendorRef)) {
                $service->setSupplierId($ippService->VendorRef);
            } else {
                $service->setSupplierId('');
            }
            $service->setTaxId('');
            $service->setDocNumber($ippService->DocNumber);
            $service->setServiceType($serviceType);
            $service->setBalance($ippService->Balance);
            $service->setTotalAmt($ippService->TotalAmt);
            //$service->setDueDate($ippService->DueDate);
            //$service->setTxnDate($ippService->TxnDate);
            $service->setSyncToken($ippService->SyncToken);
            
            try {
                $this->getObjectManager()->persist($service);
                $this->getObjectManager()->flush();
            } catch(\Exception $e) {
                // TODO: implement #36
                throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG . $e->getMessage(), 
                                            PlugintoException::INTERNAL_SERVER_ERROR_CODE);
            }

            $lineObjectArr[$service->getId()] = $ippService->Line;
        }
        
        $invoice_line_model = $this->getServiceLocator()->get('ServiceLineModel');
        $invoice_line_model->setObjectManager($this->getObjectManager());
        $invoice_line_model->createAll($lineObjectArr);
        
        return true;
    }
    
    public function updateAll($objectArr, $updateLineArr)
    {
        if (!$objectArr) return false;
        
        foreach($objectArr as $service) {
            $this->getObjectManager()->persist($service);
        }
        
        try {
            $this->getObjectManager()->flush();
        } catch(\Exception $e) {
            // TODO: implement #36
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG . $e->getMessage(), 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }
        
        $invoice_line_model = $this->getServiceLocator()->get('ServiceLineModel');
        $invoice_line_model->setObjectManager($this->getObjectManager());
        
        $deleteInvoiceLineArr = array_keys($updateLineArr);
        foreach($deleteInvoiceLineArr as $serviceId) {
            $invoice_line_model->deleteEntityByServiceId($serviceId);
        }
        
        $invoice_line_model->createAll($updateLineArr);
        
        return true;
    }
    
    public function deleteAll($objectArr)
    {
        if (!$objectArr) return false;
        
        foreach($objectArr as $service) {
            $this->getObjectManager()->remove($service);
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