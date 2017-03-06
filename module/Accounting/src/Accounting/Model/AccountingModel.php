<?php
namespace Accounting\Model;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use lib\exception\PlugintoException;

abstract class AccountingModel implements ServiceLocatorAwareInterface
{
    private $serviceLocator;
    
    private $vendor;

    abstract public function isAuthenticated($userArr);
    
    abstract protected function getAuthUri();
    
    abstract protected function writeInvoice($transactionArray);
    
    abstract protected function writeSalesReceipt($transactionArray);
    
    abstract protected function writeBill($transactionArray);
    
    abstract protected function writePurchase($transactionArray);
    
    abstract protected function deleteInvoice(\Application\Entity\Service $service, $transactionArray);
    
    abstract protected function deleteSalesReceipt(\Application\Entity\Service $service, $transactionArray);
    
    abstract protected function deleteBill(\Application\Entity\Service $service, $transactionArray);
    
    abstract protected function deletePurchase(\Application\Entity\Service $service, $transactionArray);
    
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }

    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }
    
    public function getVendor()
    {
        return $this->vendor;
    }

    public function setVendor($vendor)
    {
        $this->vendor = $vendor;
    }

    public function createAccountingServices(\Doctrine\ORM\EntityManager $objectManager, $transactionArray)
    {
        $netAmountTaxable = 0;
        //foreach($transactionArray['unit_price'] as $k => $unitPrice) {
        for($k = 1; $k <= count($transactionArray['unit_price']); $k++) {
            $quantity = $transactionArray['quantity'][$k];
            if (empty($quantity)) {
                $quantity = 1;
            }
            $netAmountTaxable = $netAmountTaxable + $transactionArray['unit_price'][$k] * $quantity;
            $transactionArray['unit_price'][$k] = abs($transactionArray['unit_price'][$k]);
        }
            
        if (!empty($transactionArray['customer_id'])) {
            $customerModel = $this->getServiceLocator()->get("CustomerModel");
            $customerModel->setUserId($transactionArray['user_id']);
            $customerModel->setUserAccountingVendor($this->getVendor());
            $customerArray = $customerModel->getEntityById($transactionArray['customer_id']);
            $transactionArray['vendor_customer_id'] = $customerArray['vendor_customer_id'];
        }
        if (!empty($transactionArray['supplier_id'])) {
            $supplierModel = $this->getServiceLocator()->get("SupplierModel");
            $supplierModel->setUserId($transactionArray['user_id']);
            $supplierModel->setUserAccountingVendor($this->getVendor());
            $supplierArray = $supplierModel->getEntityById($transactionArray['supplier_id']);
            $transactionArray['vendor_supplier_id'] = $supplierArray['vendor_supplier_id'];
        }

        $accountModel = $this->getServiceLocator()->get("AccountModel");
        $accountModel->setUserId($transactionArray['user_id']);
        $accountModel->setUserAccountingVendor($this->getVendor());
        //$accountArray = $accountModel->getEntityById($transactionArray['account_id']);
        //$transactionArray['vendor_account_id'] = $accountArray['vendor_account_id'];
        $vendorAccountIdArray = $accountModel->getAllVendorAccountId();
        if (!empty($transactionArray['account_id'][1])) {
            foreach($transactionArray['account_id'] as $k => $accountId) {
                if (!$accountId) continue;
                $transactionArray['vendor_account_id'][$k] = $vendorAccountIdArray[$accountId];
            }
        }
        if (!empty($transactionArray['account_id_financial'])) {
            $transactionArray['vendor_account_id_financial'] = $vendorAccountIdArray[$transactionArray['account_id_financial']];
        }
       
        if (!empty($transactionArray['item_id'][1])) {
            $itemModel = $this->getServiceLocator()->get("ItemModel");
            $itemModel->setUserId($transactionArray['user_id']);
            $itemModel->setUserAccountingVendor($this->getVendor());
            //$itemArray = $itemModel->getEntityById($transactionArray['item_id']);
            //$transactionArray['vendor_item_id'] = $itemArray['vendor_item_id'];
            $vendorItemIdArray = $itemModel->getAllVendorItemId();
            foreach($transactionArray['item_id'] as $k => $itemId) {
                if (!$itemId) continue;
                $transactionArray['vendor_item_id'][$k] = $vendorItemIdArray[$itemId];
            }
        }

        if (!empty($transactionArray['bank_account_id'])) {
            $bankAccountModel = $this->getServiceLocator()->get("BankAccountModel");
            $bankAccountModel->setUserId($transactionArray['user_id']);
            $bankAccountModel->setUserAccountingVendor($this->getVendor());
            $bankAccountArray = $bankAccountModel->getEntityById($transactionArray['bank_account_id']);
            $transactionArray['vendor_bank_account_id'] = $bankAccountArray['vendor_bank_account_id'];
        }
        
        if (!empty($transactionArray['tax_id'])) {
            $taxModel = $this->getServiceLocator()->get("TaxModel");
            $taxModel->setUserId($transactionArray['user_id']);
            $taxModel->setUserAccountingVendor($this->getVendor());
            $taxArray = $taxModel->getEntityById($transactionArray['tax_id']);
            $transactionArray['tax_code_id'] = $taxArray['tax_code_id'];
            $taxRateList = $taxArray['tax_rate_list'];
            $taxRateListArr = explode(',', $taxRateList);
            
            $taxRateModel = $this->getServiceLocator()->get("TaxRateModel");
            $taxRateModel->setUserId($transactionArray['user_id']);
            $taxRateModel->setUserAccountingVendor($this->getVendor());
            foreach($taxRateListArr as $taxRateId) {
                $taxRateArray = $taxRateModel->getEntityById($taxRateId);

                $tax_rate_arr = array(
                    'TaxRateRef' => $taxRateArray['tax_rate_id'],
                    'TaxPercent' => $taxRateArray['rate_value'],
                    'NetAmountTaxable' => abs($netAmountTaxable)
                );

                $transactionArray['tax_rate_arr'][] = $tax_rate_arr;
            }
        }

        // Positive amount is Sale and negetive amount is Purchase
        //if ($transactionArray['unit_price'] > 0) {
        if ($netAmountTaxable > 0 || (!empty($transactionArray['amount']) && $transactionArray['amount'] > 0)) {
            if (empty($transactionArray['customer_id'])) {
                throw new PlugintoException(PlugintoException::NOT_A_CUSTOMER_ERROR_MSG, 
                                            PlugintoException::NOT_A_CUSTOMER_ERROR_CODE);
            }
            // If payment_made is true, it is a Sale otherwise Invoce
            if (empty($transactionArray['payment_made'])) {
                $confirmationArray = $this->writeInvoice($transactionArray);
            } else {
                $confirmationArray = $this->writeSalesReceipt($transactionArray);
            }    
        } else {
            if (empty($transactionArray['supplier_id'])) {
                throw new PlugintoException(PlugintoException::NOT_A_VENDOR_ERROR_MSG, 
                                            PlugintoException::NOT_A_VENDOR_ERROR_CODE);
            }
            // If payment_made is true, it is a Purchase otherwise Bill
            if (empty($transactionArray['payment_made'])) {
                $confirmationArray = $this->writeBill($transactionArray);
            } else {
                //$transactionArray['bank_account_id'] = 56;
                $confirmationArray = $this->writePurchase($transactionArray);
            }
        }

        $serviceModel = $this->getServiceLocator()->get('ServiceModel');
        $serviceModel->setObjectManager($objectManager);
        $serviceModel->setUserId($transactionArray['user_id']);
        $serviceArray = $serviceModel->createEntity($confirmationArray);

        $invoice_line_model = $this->getServiceLocator()->get('ServiceLineModel');
        $invoice_line_model->setObjectManager($objectManager);
        if (isset($confirmationArray['lines'])) {
            foreach($confirmationArray['lines'] as $line) {
                $line['service_id'] = $serviceArray['id'];
                $invoice_line_model->createEntity($line);
            }
        }
        if (isset($confirmationArray['tax_lines'])) {
            foreach($confirmationArray['tax_lines'] as $taxLine) {
                $taxLine['service_id'] = $serviceArray['id'];
                $invoice_line_model->createEntity($taxLine);
            }
        }

        return $confirmationArray;
    }
    
    public function updateAccountingServices(\Application\Entity\Service $service, \Doctrine\ORM\EntityManager $objectManager, $transactionArray)
    {
        $customerModel = $this->getServiceLocator()->get("CustomerModel");
        $customerModel->setUserId($transactionArray['user_id']);
        $customerArray = $customerModel->getEntityById($transactionArray['customer_id']);
        $transactionArray['vendor_customer_id'] = $customerArray['vendor_customer_id'];
        
        $accountModel = $this->getServiceLocator()->get("AccountModel");
        $accountModel->setUserId($transactionArray['user_id']);
        $accountArray = $accountModel->getEntityById($transactionArray['account_id']);
        $transactionArray['vendor_account_id'] = $accountArray['vendor_account_id'];
        
        $taxModel = $this->getServiceLocator()->get("TaxModel");
        $taxModel->setUserId($transactionArray['user_id']);
        $taxArray = $taxModel->getEntityById($transactionArray['tax_id']);
        $transactionArray['tax_code_id'] = $taxArray['tax_code_id'];
        
        // Positive amount is Sale and negetive amount is Purchase
        switch ($service->getServiceType()) {
            case 'Invoice':
                    if ($transactionArray['amount'] < 0) {
                        throw new PlugintoException(PlugintoException::INVOICE_AMOUNT_CANNOT_BE_NEGETIVE_ERROR_MSG, 
                                                    PlugintoException::INVOICE_AMOUNT_CANNOT_BE_NEGETIVE_ERROR_CODE);
                    }
                    $confirmationArray = $this->writeInvoice($transactionArray, true, $service);
                break;
            case 'SalesReceipt':
                    if ($transactionArray['amount'] < 0) {
                        throw new PlugintoException(PlugintoException::SALESRECEIPT_AMOUNT_CANNOT_BE_NEGETIVE_ERROR_MSG, 
                                                    PlugintoException::SALESRECEIPT_AMOUNT_CANNOT_BE_NEGETIVE_ERROR_CODE);
                    }
                    $confirmationArray = $this->writeSalesReceipt($transactionArray, true, $service);
                break;
            case 'Bill':
                    if ($transactionArray['amount'] > 0) {
                        throw new PlugintoException(PlugintoException::BILL_AMOUNT_CANNOT_BE_POSITIVE_ERROR_MSG, 
                                                    PlugintoException::BILL_AMOUNT_CANNOT_BE_POSITIVE_ERROR_CODE);
                    }
                    $confirmationArray = $this->writeBill($transactionArray, true, $service);
                break;
            case 'Purchase':
                    if ($transactionArray['amount'] > 0) {
                        throw new PlugintoException(PlugintoException::PURCHASE_AMOUNT_CANNOT_BE_POSITIVE_ERROR_MSG, 
                                                    PlugintoException::PURCHASE_AMOUNT_CANNOT_BE_POSITIVE_ERROR_CODE);
                    }
                    $confirmationArray = $this->writePurchase($transactionArray, true, $service);
                break;
            default:
                throw new PlugintoException(PlugintoException::INVALID_ACCOUNTING_SERVICE_TYPE_ERROR_MSG, 
                                            PlugintoException::INVALID_ACCOUNTING_SERVICE_TYPE_ERROR_CODE);
                break;
        }

        $serviceModel = $this->getServiceLocator()->get('ServiceModel');
        $serviceModel->setObjectManager($objectManager);
        $serviceModel->setUserId($transactionArray['user_id']);
        $serviceModel->updateEntity($service->getId(), $confirmationArray);
        
        $invoice_line_model = $this->getServiceLocator()->get('ServiceLineModel');
        $invoice_line_model->setObjectManager($objectManager);
        // Deletes previous invoice lines
        $invoice_line_model->deleteEntityByServiceId($service->getId());
        
        foreach($confirmationArray['lines'] as $line) {
            $line['service_id'] = $service->getId();
            $invoice_line_model->createEntity($line);
        }
        
        return $confirmationArray;
    }
    
    
    public function deleteAccountingServices(\Application\Entity\Service $service, \Doctrine\ORM\EntityManager $objectManager, $transactionArray)
    {
        $serviceModel = $this->getServiceLocator()->get('ServiceModel');
        $serviceModel->setObjectManager($objectManager);
        $serviceModel->setUserId($transactionArray['user_id']);
        $serviceModel->deleteEntity($service->getId());
        
        $invoice_line_model = $this->getServiceLocator()->get('ServiceLineModel');
        $invoice_line_model->setObjectManager($objectManager);
        $invoice_line_model->deleteEntityByServiceId($service->getId());
        
        switch($service->getServiceType()) {
            case 'Invoice':
                    $confirmationArray = $this->deleteInvoice($service, $transactionArray);
                break;
            case 'SalesReceipt':
                    $confirmationArray = $this->deleteSalesReceipt($service, $transactionArray);
                break;
            case 'Bill':
                    $confirmationArray = $this->deleteBill($service, $transactionArray);
                break;
            case 'Purchase':
                    $confirmationArray = $this->deletePurchase($service, $transactionArray);
                break;
        }
        
        return $confirmationArray;
    }

}
