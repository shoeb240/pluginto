<?php
namespace Input\Controller;

use Zend\View\Model\JsonModel;
use lib\exception\PlugintoException;

require_once(__DIR__ . '/../../../../../lib/exception/PlugintoException.php');

class SyncController extends BaseController
{
    public function accountAction()
    {
        $userId = $this->validateUser();
        
        $accountInputModel = new \Input\Model\Account($this->getServiceLocator());
        $accountInputModel->setUserId($userId);
        $plugintoCompanyArr = $accountInputModel->fetchAllObj();

        $accountArray['user_id'] = $userId;
        $accountAccountingModel = $this->getServiceLocator()->get('AccountModelWrapper')->get('Qb'); //Qb
        $accountArr = $accountAccountingModel->getAccount($userId);
        
        $accountInputModel = new \Input\Model\Account($this->getServiceLocator());
        $accountInputModel->setUserId($userId);
        $plugintoAccountArr = $accountInputModel->fetchAllObj();
        
        $accountQbModel = $this->getServiceLocator()->get('AccountModelWrapper')->get('Qb');
        $vendorAccountArr = $accountQbModel->getAccount($userId);
        
        $updateArr = array();
        $insertArr = array();
        $deleteArr = $plugintoAccountArr;
        foreach($vendorAccountArr as $vendorAccount) {
            $matched = false;
            foreach($plugintoAccountArr as $key => $plugintoAccount) {
                if ($vendorAccount->Id == $plugintoAccount->getVendorAccountId()) {
                    if ($vendorAccount->SyncToken != $plugintoAccount->getSyncToken()) {
                        $plugintoAccount->setName($vendorAccount->Name);
                        $plugintoAccount->setAccountType($vendorAccount->AccountType);
                        $plugintoAccount->setAccountSubType($vendorAccount->AccountSubType);
                        $plugintoAccount->setSyncToken($vendorAccount->SyncToken);
                        $updateArr[] = $plugintoAccount;
                    }
                    unset($deleteArr[$key]);
                    $matched = true;
                    break;
                }
            }
            if ($matched === false) {
                $insertArr[] = $vendorAccount;
            }
        }

        $accountInputModel->getObjectManager()->beginTransaction();
        
        try {
            $accountInputModel->createAll($userId, $insertArr);
            $accountInputModel->updateAll($updateArr);
            $accountInputModel->deleteAll($deleteArr);
            $accountInputModel->getObjectManager()->commit();
            $result['data'] = true;
        } catch (PlugintoException $ex) {
            $accountInputModel->getObjectManager()->rollback();
            $result['error_code'] = $ex->getCode();
            $result['error_msg'] = $ex->getMessage();
        }

        return new JsonModel($result);
    }
    
    public function itemAction()
    {
        $userId = $this->validateUser();
        
        $itemInputModel = new \Input\Model\Item($this->getServiceLocator());
        $itemInputModel->setUserId($userId);
        $plugintoCompanyArr = $itemInputModel->fetchAllObj();

        $itemArray['user_id'] = $userId;
        $itemAccountingModel = $this->getServiceLocator()->get('ItemModelWrapper')->get('Qb'); //Qb
        $itemArr = $itemAccountingModel->getItem($userId);
        
        $itemInputModel = new \Input\Model\Item($this->getServiceLocator());
        $itemInputModel->setUserId($userId);
        $plugintoItemArr = $itemInputModel->fetchAllObj();
        
        $itemQbModel = $this->getServiceLocator()->get('ItemModelWrapper')->get('Qb');
        $vendorItemArr = $itemQbModel->getItem($userId);
        
        $updateArr = array();
        $insertArr = array();
        $deleteArr = $plugintoItemArr;
        foreach($vendorItemArr as $vendorItem) {
            $matched = false;
            foreach($plugintoItemArr as $key => $plugintoItem) {
                if ($vendorItem->Id == $plugintoItem->getVendorItemId()) {
                    if ($vendorItem->SyncToken != $plugintoItem->getSyncToken()) {
                        $plugintoItem->setName($vendorItem->Name);
                        $plugintoItem->setItemType($vendorItem->ItemType);
                        $plugintoItem->setItemSubType($vendorItem->ItemSubType);
                        $plugintoItem->setSyncToken($vendorItem->SyncToken);
                        $updateArr[] = $plugintoItem;
                    }
                    unset($deleteArr[$key]);
                    $matched = true;
                    break;
                }
            }
            if ($matched === false) {
                $insertArr[] = $vendorItem;
            }
        }

        $itemInputModel->getObjectManager()->beginTransaction();
        
        try {
            $itemInputModel->createAll($userId, $insertArr);
            $itemInputModel->updateAll($updateArr);
            $itemInputModel->deleteAll($deleteArr);
            $itemInputModel->getObjectManager()->commit();
            $result['data'] = true;
        } catch (PlugintoException $ex) {
            $itemInputModel->getObjectManager()->rollback();
            $result['error_code'] = $ex->getCode();
            $result['error_msg'] = $ex->getMessage();
        }

        return new JsonModel($result);
    }
    
    public function customerAction()
    {
        $userId = $this->validateUser();
        
        $customerInputModel = new \Input\Model\Customer($this->getServiceLocator());
        $customerInputModel->setUserId($userId);
        $plugintoCustomerArr = $customerInputModel->fetchAllObj();

        $customerArray['user_id'] = $userId;
        $customerAccountingModel = $this->getServiceLocator()->get('CustomerModelWrapper')->get('Qb'); //Qb
        $vendorCustomerArr = $customerAccountingModel->getCustomer($customerArray);
        
        $updateArr = array();
        $insertArr = array();
        $deleteArr = $plugintoCustomerArr;
        foreach($vendorCustomerArr as $vendorCustomer) {
            $matched = false;
            foreach($plugintoCustomerArr as $key => $plugintoCustomer) {
                if ($vendorCustomer->Id == $plugintoCustomer->getVendorCustomerId()) {
                    if ($vendorCustomer->SyncToken != $plugintoCustomer->getSyncToken()) {
                        $plugintoCustomer->setCustomerName($vendorCustomer->CustomerName);
                        $plugintoCustomer->setDisplayName($vendorCustomer->DisplayName);
                        $plugintoCustomer->setName($vendorCustomer->GivenName);
                        $plugintoCustomer->setSurname($vendorCustomer->FamilyName);
                        $plugintoCustomer->setAddress1($vendorCustomer->BillAddr->Line1);
                        $plugintoCustomer->setAddress2($vendorCustomer->BillAddr->Line2);
                        $plugintoCustomer->setCity($vendorCustomer->BillAddr->City);
                        $plugintoCustomer->setPostcode($vendorCustomer->BillAddr->PostalCode);
                        $plugintoCustomer->setCountry($vendorCustomer->BillAddr->Country);
                        $plugintoCustomer->setSyncToken($vendorCustomer->SyncToken);
                        $updateArr[] = $plugintoCustomer;
                    }
                    unset($deleteArr[$key]);
                    $matched = true;
                    break;
                }
            }
            if ($matched === false) {
                $insertArr[] = $vendorCustomer;
            }
        }

        $customerInputModel->getObjectManager()->beginTransaction();
        
        try {
            $customerInputModel->createAll($userId, $insertArr);
            $customerInputModel->updateAll($updateArr);
            $customerInputModel->deleteAll($deleteArr);
            $customerInputModel->getObjectManager()->commit();
            $result['data'] = true;
        } catch (PlugintoException $ex) {
            $customerInputModel->getObjectManager()->rollback();
            $result['error_code'] = $ex->getCode();
            $result['error_msg'] = $ex->getMessage();
        }

        return new JsonModel($result);
    }
    
    public function supplierAction()
    {
        $userId = $this->validateUser();
        
        $supplierInputModel = new \Input\Model\Supplier($this->getServiceLocator());
        $supplierInputModel->setUserId($userId);
        $plugintoSupplierArr = $supplierInputModel->fetchAllObj();

        $supplierArray['user_id'] = $userId;
        $supplierAccountingModel = $this->getServiceLocator()->get('SupplierModelWrapper')->get('Qb'); //Qb
        $vendorSupplierArr = $supplierAccountingModel->getCustomer($supplierArray);
        
        $updateArr = array();
        $insertArr = array();
        $deleteArr = $plugintoSupplierArr;
        foreach($vendorSupplierArr as $vendorSupplier) {
            $matched = false;
            foreach($plugintoSupplierArr as $key => $plugintoSupplier) {
                if ($vendorSupplier->Id == $plugintoSupplier->getVendorCustomerId()) {
                    if ($vendorSupplier->SyncToken != $plugintoSupplier->getSyncToken()) {
                        $plugintoSupplier->setSupplierName($vendorSupplier->SupplierName);
                        $plugintoSupplier->setDisplayName($vendorSupplier->DisplayName);
                        $plugintoSupplier->setName($vendorSupplier->GivenName);
                        $plugintoSupplier->setSurname($vendorSupplier->FamilyName);
                        $plugintoSupplier->setAddress1($vendorSupplier->BillAddr->Line1);
                        $plugintoSupplier->setAddress2($vendorSupplier->BillAddr->Line2);
                        $plugintoSupplier->setCity($vendorSupplier->BillAddr->City);
                        $plugintoSupplier->setPostcode($vendorSupplier->BillAddr->PostalCode);
                        $plugintoSupplier->setCountry($vendorSupplier->BillAddr->Country);
                        $plugintoSupplier->setSyncToken($vendorSupplier->SyncToken);
                        $updateArr[] = $plugintoSupplier;
                    }
                    unset($deleteArr[$key]);
                    $matched = true;
                    break;
                }
            }
            if ($matched === false) {
                $insertArr[] = $vendorSupplier;
            }
        }

        $supplierInputModel->getObjectManager()->beginTransaction();
        
        try {
            $supplierInputModel->createAll($userId, $insertArr);
            $supplierInputModel->updateAll($updateArr);
            $supplierInputModel->deleteAll($deleteArr);
            $supplierInputModel->getObjectManager()->commit();
            $result['data'] = true;
        } catch (PlugintoException $ex) {
            $supplierInputModel->getObjectManager()->rollback();
            $result['error_code'] = $ex->getCode();
            $result['error_msg'] = $ex->getMessage();
        }

        return new JsonModel($result);
    }
    
    public function taxAgencyAction()
    {
        $userId = $this->validateUser();
        
        $taxAgencyInputModel = new \Input\Model\TaxAgency($this->getServiceLocator());
        $taxAgencyInputModel->setUserId($userId);
        $plugintoTaxAgencyArr = $taxAgencyInputModel->fetchAllObj();
        
        $taxAgencyQbModel = $this->getServiceLocator()->get('TaxAgencyModelWrapper')->get('Qb');
        $vendorTaxAgencyArr = $taxAgencyQbModel->getTaxAgency($userId);
        
        $updateArr = array();
        $insertArr = array();
        $deleteArr = $plugintoTaxAgencyArr;
        foreach($vendorTaxAgencyArr as $vendorTaxAgency) {
            $matched = false;
            foreach($plugintoTaxAgencyArr as $key => $plugintoTaxAgency) {
                if ($vendorTaxAgency->Id == $plugintoTaxAgency->getTaxAgencyId()) {
                    if ($vendorTaxAgency->SyncToken != $plugintoTaxAgency->getSyncToken()) {
                        $plugintoTaxAgency->setDisplayName($vendorTaxAgency->DisplayName);
                        $plugintoTaxAgency->setSyncToken($vendorTaxAgency->SyncToken);
                        $updateArr[] = $plugintoTaxAgency;
                    }
                    unset($deleteArr[$key]);
                    $matched = true;
                    break;
                }
            }
            if ($matched === false) {
                $insertArr[] = $vendorTaxAgency;
            }
        }

        $taxAgencyInputModel->getObjectManager()->beginTransaction();
        
        try {
            $taxAgencyInputModel->createAll($userId, $insertArr);
            $taxAgencyInputModel->getObjectManager()->commit();
            $result['data'] = true;
        } catch (PlugintoException $ex) {
            $taxAgencyInputModel->getObjectManager()->rollback();
            $result['error_code'] = $ex->getCode();
            $result['error_msg'] = $ex->getMessage();
        }

        return new JsonModel($result);
    }
    
    public function taxAction()
    {
        $userId = $this->validateUser();
        
        $taxInputModel = new \Input\Model\Tax($this->getServiceLocator());
        $taxInputModel->setUserId($userId);
        $plugintoTaxArr = $taxInputModel->fetchAllObj();
        
        $taxQbModel = $this->getServiceLocator()->get('TaxModelWrapper')->get('Qb');
        $taxArray['user_id'] = $userId;
        $vendorTaxArr = $taxQbModel->getTax($taxArray);
        
        $insertArr = array();
        $deleteArr = $plugintoTaxArr;
        foreach($vendorTaxArr as $vendorTax) {
            $matched = false;
            foreach($plugintoTaxArr as $key => $plugintoTax) {
                if ($vendorTax->Id == $plugintoTax->getTaxCodeId()) {
                    unset($deleteArr[$key]);
                    $matched = true;
                    break;
                }
            }
            if ($matched === false) {
                $insertArr[] = $vendorTax;
            }
        }

        $taxInputModel->getObjectManager()->beginTransaction();
        
        try {
            $taxInputModel->createAll($userId, $insertArr);
            $taxInputModel->getObjectManager()->commit();
            $result['data'] = true;
        } catch (PlugintoException $ex) {
            $taxInputModel->getObjectManager()->rollback();
            $result['error_code'] = $ex->getCode();
            $result['error_msg'] = $ex->getMessage();
        }

        return new JsonModel($result);
    }
    
    public function taxRateAction()
    {
        $userId = $this->validateUser();
        
        $taxRateInputModel = new \Input\Model\TaxRate($this->getServiceLocator());
        $taxRateInputModel->setUserId($userId);
        $plugintoTaxRateArr = $taxRateInputModel->fetchAllObj();
        
        $taxQbModel = $this->getServiceLocator()->get('TaxModelWrapper')->get('Qb');
        $taxRateArray['user_id'] = $userId;
        $vendorTaxRateArr = $taxQbModel->getTaxRate($taxRateArray);
        
        $insertArr = array();
        $deleteArr = $plugintoTaxRateArr;
        foreach($vendorTaxRateArr as $vendorTaxRate) {
            $matched = false;
            foreach($plugintoTaxRateArr as $key => $plugintoTaxRate) {
                if ($vendorTaxRate->Id == $plugintoTaxRate->getTaxRateId()) {
                    unset($deleteArr[$key]);
                    $matched = true;
                    break;
                }
            }
            if ($matched === false) {
                $insertArr[] = $vendorTaxRate;
            }
        }

        $taxRateInputModel->getObjectManager()->beginTransaction();
        
        try {
            $taxRateInputModel->createAll($userId, $insertArr);
            $taxRateInputModel->getObjectManager()->commit();
            $result['data'] = true;
        } catch (PlugintoException $ex) {
            $taxRateInputModel->getObjectManager()->rollback();
            $result['error_code'] = $ex->getCode();
            $result['error_msg'] = $ex->getMessage();
        }

        return new JsonModel($result);
    }
    
    public function serviceAction()
    {
        $userId = $this->validateUser();
        
        $serviceList = array('Invoice', 'SalesReceipt', 'Bill', 'Purchase');
        
        foreach($serviceList as $serviceName) {
            $result[$serviceName] = $this->service($userId, $serviceName);
        }
        
        return new JsonModel($result);
    }
    
    private function service($userId, $serviceName)
    {
        $transactionInputModel = new \Input\Model\Transaction($this->getServiceLocator());
        $transactionInputModel->setUserId($userId);
        $plugintoServiceArr = $transactionInputModel->fetchAllServiceObj($serviceName);
        
        $serviceQbModel = $this->getServiceLocator()->get('AccountingModelWrapper')->get('Qb');
        $transactionArray['user_id'] = $userId;
        $vendorServiceArr = $serviceQbModel->getService($transactionArray, $serviceName);
        
        $updateArr = array();
        $insertArr = array();
        $deleteArr = $plugintoServiceArr;
        foreach($vendorServiceArr as $vendorService) {
            $matched = false;
            foreach($plugintoServiceArr as $key => $plugintoService) {
                if ($vendorService->Id == $plugintoService->getVendorServiceId()) {
                    //echo $vendorService->Id .'=='. $plugintoService->getVendorServiceId() . '--' . $vendorService->SyncToken . '!=' . $plugintoService->getSyncToken() . '<br />';
                    if ($vendorService->SyncToken != $plugintoService->getSyncToken()) {
                        $plugintoService->setUserId($userId);
                        if (isset($vendorService->CustomerRef)) {
                            $plugintoService->setCustomerId($vendorService->CustomerRef);
                        }
                        if (isset($vendorService->VendorRef)) {
                            $plugintoService->setSupplierId($vendorService->VendorRef);
                        }
                        //$plugintoService->setAccountId($data['account_id']);
                        //$plugintoService->setBankAccountId($data['bank_account_id']);
                        //$plugintoService->setTaxId($data['tax_id']);
                        $plugintoService->setDocNumber($vendorService->DocNumber);
                        $plugintoService->setBalance($vendorService->Balance);
                        $plugintoService->setTotalAmt($vendorService->TotalAmt);
                        //$plugintoService->setDueDate($vendorService->DueDate);
                        //$plugintoService->setTxnDate($vendorService->TxnDate);
                        $plugintoService->setSyncToken($vendorService->SyncToken);
                        
                        $updateArr[$vendorService->Id] = $plugintoService;
                        $updateLineArr[$plugintoService->getId()] = $vendorService->Line;
                    }
                    unset($deleteArr[$key]);
                    $matched = true;
                    break;
                }
            }
            if ($matched === false) {
                $insertArr[] = $vendorService;
            }
        }

        $serviceModel = $this->getServiceLocator()->get('ServiceModel');
        $serviceModel->setUserId($userId);
        $serviceModel->setObjectManager($transactionInputModel->getObjectManager());
        
        $transactionInputModel->getObjectManager()->beginTransaction();
        
        try {
            $serviceModel->createAll($userId, $insertArr, $serviceName);
            $serviceModel->updateAll($updateArr, $updateLineArr);
            $serviceModel->deleteAll($deleteArr);
            $transactionInputModel->getObjectManager()->commit();
            $result['data'] = true;
        } catch (PlugintoException $ex) {
            $transactionInputModel->getObjectManager()->rollback();
            $result['error_code'] = $ex->getCode();
            $result['error_msg'] = $ex->getMessage();
        }

        return $result;
    }
    
}