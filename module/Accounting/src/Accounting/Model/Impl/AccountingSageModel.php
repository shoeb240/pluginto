<?php
namespace Accounting\Model\Impl;

use Accounting\Model\AccountingModel;
use lib\exception\PlugintoException;

class AccountingSageModel extends AccountingModel
{
    private $_dataService;
    
    protected function getConsumerKey()
    {
        return $this->getServiceLocator()->get('GlobalSettings')->getConsumerKey();
    }
    
    protected function getConsumerSecret()
    {
        return $this->getServiceLocator()->get('GlobalSettings')->getConsumerSecret();
    }
    
    private function setCurrentDataService($dataService) {
        $this->_dataService = $dataService;
    }
    
    private function getCurrentDataService() {
        return $this->_dataService;
    }
    
    protected function getDataService($id)
    {
        require_once(__DIR__ . '/../../../../../../lib/sage/DataService.php');
        
        $userModel = $this->getServiceLocator()->get("UserModel");
        $userArr = $userModel->getEntityById($id);
        $userPropertyArr = $userArr['property_list'];
        
        $apiKey = $userPropertyArr['vendor_api_key']; //'{28FBCB45-262C-4F55-9035-CCD4847AB4BF}';
        $userId = $userArr['user_login']; //'shoeb240@gmail.com';
        $userPass = $userPropertyArr['vendor_password']; //'Shoeb123#';
        $companyId = $userPropertyArr['vendor_company_id']; //'112320';
        
        $dataService = new \DataService($userId, $userPass, $apiKey, $companyId);
        
        if (!$dataService) {
            throw new PlugintoException(PlugintoException::DATA_SERVICE_RENDER_FAILED_ERROR_MSG, 
                                        PlugintoException::DATA_SERVICE_RENDER_FAILED_ERROR_CODE);
        }

        return $dataService;
    }
    
    public function getAuthUri()
    {
	return '/qb/oauth';
    }

    public function isAuthenticated($userArr) {
	$userPropertyArr = $userArr['property_list'];
	if (empty($userPropertyArr['vendor_api_key']) || empty($userPropertyArr['vendor_password']) || empty($userPropertyArr['vendor_company_id'])) {
            return false;
        }
        
	//TODO - check if we can check at QB that we are really authenticated (call some method?)
        return true;

    }
    
    public function notAuthenticated($userModel, $userArr, $id, $returnUrl)
    {
        throw new PlugintoException("Sage account credentials are not set for this Pluginto account. Update vendor_api_key, vendor_password and vendor_company_id.", 
                            PlugintoException::USER_NOT_AUTHENTICATED_ERROR_CODE);
    }
    
    public function getService($transactionArray, $serviceName)
    {
        $dataService = $this->getDataService($transactionArray['user_id']);
        
        try {
            $data = $dataService->FindAll($serviceName);
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_MSG, 
                                        PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_CODE);
        }    

        return $data;
    }
    
    private function doWrite($dataService, $addUpdateVar, $targetObj, $entityName)
    {
        try {
            $confirmationObject = $dataService->$addUpdateVar($entityName, $targetObj);
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_MSG . ' - ' . $e->getMessage(), 
                                        PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_CODE);
        }
        
        if (!isset($confirmationObject->ID)) {
            throw new PlugintoException(PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_MSG, 
                                        PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_CODE);
        }
        
        return $confirmationObject;
    }
    
    public function writeInvoice($transactionArray, $update = false, \Application\Entity\Service $service = null)
    {
        $dataService = $this->getDataService($transactionArray['user_id']);
        
        $this->setCurrentDataService($dataService);
        
        if ($update) {
            if (!$service) {
                throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                            PlugintoException::INTERNAL_SERVER_ERROR_CODE);
            }

            $targetObj = $this->prepareIPPInvoice($transactionArray, $service->getVendorServiceId());
            $addUpdateVar = 'Update';
        } else {
            $targetObj = $this->prepareIPPInvoice($transactionArray);
            $addUpdateVar = 'Add';
        }

        $confirmationObject = $this->doWrite($dataService, $addUpdateVar, $targetObj, 'TaxInvoice');
        
        $lines = array();
        foreach ($confirmationObject->Lines as $line) {
            $lines[] = array(
                'line_num' => $line->ID,
                // Need to separate item and account
                'account_id' => $line->SelectionId,
                'description' => $line->Description,
                'quantity' => $line->Quantity,
                'unit_price' => $line->UnitPriceExclusive,
                'tax_percentage' => $line->TaxPercentage,
                'tax' => $line->Tax,
                'discount_percentage' => $line->DiscountPercentage,
                'discount' => $line->Discount,
                'amount' => $line->Total,
                'detail_type' => $line->LineType
            );
        }

        return array(	
	    'vendor_service_id' => $confirmationObject->ID,
            'transaction_id' => $transactionArray['id'],
            'user_id' => $transactionArray['user_id'],
            'customer_id' => $confirmationObject->CustomerId,
            'supplier_id' => '',
            'bank_account_id' => '',
            'tax_id' => '',
            'doc_number' => $confirmationObject->DocumentNumber,
            'service_type' => 'TaxInvoice',
            'tax' => $confirmationObject->Tax,
            'discount_percentage' => $confirmationObject->DiscountPercentage,
            'discount' => $confirmationObject->Discount,
            'total_amt' => $confirmationObject->Total,
            'due_date' => date("Y-m-d H:i:s", strtotime($confirmationObject->DueDate)),
            'txn_date' => date("Y-m-d H:i:s", strtotime($confirmationObject->Created)),
	    'lines' => $lines
        );
    }
    
    protected function prepareIPPInvoice($transactionArray, $id = null)
    {
        if (empty($transactionArray['vendor_customer_id'])) {
            throw new PlugintoException(PlugintoException::MISSING_REQUIRED_FIELD_CUSTOMER_REF_ERROR_MSG, 
                                        PlugintoException::MISSING_REQUIRED_FIELD_CUSTOMER_REF_ERROR_CODE);
        }
        if (empty($transactionArray['vendor_account_id']) && empty($transactionArray['vendor_item_id'])) {
            throw new PlugintoException(PlugintoException::MISSING_REQUIRED_FIELD_ACCOUNT_ID_ERROR_MSG, 
                                        PlugintoException::MISSING_REQUIRED_FIELD_ACCOUNT_ID_ERROR_CODE);
        }
        
        if ($id) {
            $jsonArr['ID'] = $id;
        }
        $jsonArr['DueDate'] = date("Y-m-d", strtotime("+1 month")); //"2015-02-28";
        $jsonArr['AllowOnlinePayment'] = true;
        $jsonArr['Paid'] = false;
        $jsonArr['CustomerId'] = $transactionArray['vendor_customer_id']; // 2663585; 
        $jsonArr['Date'] = date("Y-m-d");
        $jsonArr['Inclusive'] = false;
        if (!empty($transactionArray['discount_percent_total'])) {
            $jsonArr['DiscountPercentage'] = $transactionArray['discount_percent_total'];
        } else {
            $jsonArr['DiscountPercentage'] = 0.0;
        }
        //$jsonArr['TaxReference'] = "";
        //$jsonArr['Reference'] = "";
        //$jsonArr['Message'] = "";
        if (!empty($transactionArray['discount_amount_total'])) {
            $jsonArr['Discount'] = $transactionArray['discount_amount_total'];
        } else {
            $jsonArr['Discount'] = 0.0;
        }
        //$jsonArr['Exclusive'] = 9.9000; //(int)$transactionArray['amount'];
        if (!empty($transactionArray['tax_amount_total'])) {
            $jsonArr['Tax'] = $transactionArray['tax_amount_total'];
        } else {
            $jsonArr['Tax'] = 0.0;
        }
        //$jsonArr['Rounding'] = 0.0000;
        //$jsonArr['Total'] = 9.9000; //$transactionArray['amount'];
        //$jsonArr['AmountDue'] = 9.9000; //$transactionArray['amount'];
        //$jsonArr['Printed'] = false;
        //$jsonArr['Editable'] = true;
        
        
        $lineArr = array();
        $count = max(count($transactionArray['vendor_item_id']), count($transactionArray['vendor_account_id']));
        $totalLineAmount = 0;
        for($k = 1; $k <= $count; $k++) {
            if (!empty($transactionArray['vendor_item_id'][$k])) {
                $eachLine['SelectionId'] =  $transactionArray['vendor_item_id'][$k]; //4180691;
                $eachLine['LineType'] = 0;
            } else if (!empty($transactionArray['vendor_account_id'][$k])) {
                $eachLine['SelectionId'] =  $transactionArray['vendor_account_id'][$k]; 
                $eachLine['LineType'] = 1;
            }
            //$eachLine['TaxTypeId'] = 0;
            if (!empty($transactionArray['description'][$k])) {
                $eachLine['Description'] = $transactionArray['description'][$k];
            } else {
                $eachLine['Description'] = '';
            }
            if (!empty($transactionArray['quantity'][$k])) {
                $eachLine['Quantity'] = $transactionArray['quantity'][$k];
            } else {
                $eachLine['Quantity'] = 1;
            }
            if (!empty($transactionArray['unit_price'][$k])) {
                $eachLine['UnitPriceExclusive'] = $transactionArray['unit_price'][$k];
            } else {
                $eachLine['UnitPriceExclusive'] = 0;
            }
            //$eachLine['Unit'] = "1";
            //$eachLine['UnitPriceInclusive'] = 0;
            if (!empty($transactionArray['tax_percentage'][$k])) {
                $eachLine['TaxPercentage'] = $transactionArray['tax_percentage'][$k];
            } else {
                $eachLine['TaxPercentage'] = 0;
            }
            if (!empty($transactionArray['discount_percentage'][$k])) {
                $eachLine['DiscountPercentage'] = $transactionArray['discount_percentage'][$k];
            } else {
                $eachLine['DiscountPercentage'] = 0.0;
            }
            //$eachLine['Exclusive'] = 0;
            if (!empty($transactionArray['discount'][$k])) {
                $eachLine['Discount'] = $transactionArray['discount'][$k];
            } else {
                $eachLine['Discount'] = 0;
            }
            if (!empty($transactionArray['tax'][$k])) {
                $eachLine['Tax'] = $transactionArray['tax'][$k];
            } else {
                $eachLine['Tax'] = 0;
            }
            //$eachLine['Total'] = 0;
            //$eachLine['Comments'] = "";
            
            $lineArr[] = $eachLine;
        }
        
        $jsonArr['Lines'] = $lineArr;
        
        return json_encode($jsonArr);
    }
    
    public function writeSalesReceipt($transactionArray, $update = false, \Application\Entity\Service $service = null)
    {
        $dataService = $this->getDataService($transactionArray['user_id']);

        if ($update) {
            if (!$service) {
                throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                            PlugintoException::INTERNAL_SERVER_ERROR_CODE);
            }
            
            $targetObj = $this->prepareIPPSalesReceipt($transactionArray, $service->getVendorServiceId());
            $addUpdateVar = 'Update';
        } else {
            $targetObj = $this->prepareIPPSalesReceipt($transactionArray);
            $addUpdateVar = 'Add';
        }
        
//        echo '<pre>';
//        print_r($targetObj);
//        echo '</pre>';
        //die();
        
        $confirmationObject = $this->doWrite($dataService, $addUpdateVar, $targetObj, 'CustomerReceipt');    
        
        return array(	
	    'vendor_service_id' => $confirmationObject->ID,
            'transaction_id' => $transactionArray['id'],
            'user_id' => $transactionArray['user_id'],
            'customer_id' => $confirmationObject->CustomerId,
            'supplier_id' => '',
            'account_id' => '',
            'bank_account_id' => $confirmationObject->BankAccountId,
            'doc_number' => $confirmationObject->DocumentNumber,
            'service_type' => 'CustomerReceipt',
            'total_amt' => $confirmationObject->Total,
            'txn_date' => date("Y-m-d H:i:s", strtotime($confirmationObject->Created))
        );
    }
    
    protected function prepareIPPSalesReceipt($transactionArray, $id = null)
    {
        if (empty($transactionArray['vendor_customer_id'])) {
            throw new PlugintoException(PlugintoException::MISSING_REQUIRED_FIELD_CUSTOMER_REF_ERROR_MSG, 
                                        PlugintoException::MISSING_REQUIRED_FIELD_CUSTOMER_REF_ERROR_CODE);
        }
        if (empty($transactionArray['vendor_bank_account_id'])) {
            throw new PlugintoException(PlugintoException::MISSING_REQUIRED_FIELD_ACCOUNT_ID_ERROR_MSG, 
                                        PlugintoException::MISSING_REQUIRED_FIELD_ACCOUNT_ID_ERROR_CODE);
        }
        
        if ($id) {
            $jsonArr['ID'] = $id;
        }
        $jsonArr['CustomerId'] = (int)$transactionArray['vendor_customer_id'];
        //$jsonArr['Payee'] = "Sample Payee";//$transactionArray['payee'];
        if (!empty($transactionArray['doc_number'])) {
            $jsonArr['DocumentNumber'] = $transactionArray['doc_number'];
        }
        //$jsonArr['Reference'] = "SampleReference";//$transactionArray['reference'];
        if (!empty($transactionArray['transaction_description'])) {
            $jsonArr['description'] = $transactionArray['transaction_description'];
        }
        if (!empty($transactionArray['amount'])) {
            $jsonArr['Total'] = (float)$transactionArray['amount'];
        } else {
            $jsonArr['Total'] = 0;
        }
        if (!empty($transactionArray['discount_amount_total'])) {
            $jsonArr['Discount'] = $transactionArray['discount_amount_total'];
        }        
        $jsonArr['BankAccountId'] = $transactionArray['vendor_bank_account_id']; // 84893;
        if (isset($transactionArray['payment_type'])) {
            $jsonArr['PaymentMethod'] = $this->getPaymentMethod($transactionArray['payment_type']);
        } else {
            $jsonArr['PaymentMethod'] = 1;
        }
        $jsonArr['Accepted'] = true;
        $jsonArr['Reconciled'] = true;
        $jsonArr['Date'] = date("Y-m-d");
        
        return json_encode($jsonArr);
    }

    private function getPaymentMethod($paymentType)
    {
        $arr = array('Cash' => 1, 'Check' => 2, 'CreditCard' => 3, 'EFT' => 4);
        
        if (isset($arr[$paymentType])) {
            return $arr[$paymentType];
        }
        
        return 0;
    }
    
    public function writeBill($transactionArray, $update = false, \Application\Entity\Service $service = null)
    {
        $dataService = $this->getDataService($transactionArray['user_id']);
        
        $this->setCurrentDataService($dataService);
        
        if ($update) {
            if (!$service) {
                throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                            PlugintoException::INTERNAL_SERVER_ERROR_CODE);
            }

            $targetObj = $this->prepareIPPBill($transactionArray, $service->getVendorServiceId());
            $addUpdateVar = 'Update';
        } else {
            $targetObj = $this->prepareIPPBill($transactionArray);
            $addUpdateVar = 'Add';
        }

        $confirmationObject = $this->doWrite($dataService, $addUpdateVar, $targetObj, 'SupplierInvoice');
        
        $lines = array();
        foreach ($confirmationObject->Lines as $line) {
            $lines[] = array(
                'line_num' => $line->ID,
                'account_id' => $line->SelectionId,
                'description' => $line->Description,
                'quantity' => $line->Quantity,
                'unit_price' => $line->UnitPriceExclusive,
                'tax_percentage' => $line->TaxPercentage,
                'tax' => $line->Tax,
                'discount_percentage' => $line->DiscountPercentage,
                'discount' => $line->Discount,
                'amount' => $line->Total,
                'detail_type' => $line->LineType
            );
        }

        return array(	
	    'vendor_service_id' => $confirmationObject->ID,
            'transaction_id' => $transactionArray['id'],
            'user_id' => $transactionArray['user_id'],
            'customer_id' => '',
            'supplier_id' => $confirmationObject->SupplierId,
            'account_id' => '',
            'bank_account_id' => '',
            'tax_id' => '',
            'doc_number' => $confirmationObject->DocumentNumber,
            'service_type' => 'SupplierInvoice',
            'tax' => $confirmationObject->Tax,
            'discount_percentage' => $confirmationObject->DiscountPercentage,
            'discount' => $confirmationObject->Discount,
            'total_amt' => $confirmationObject->Total,
            'due_date' => date("Y-m-d H:i:s", strtotime($confirmationObject->DueDate)),
            'txn_date' => date("Y-m-d H:i:s", strtotime($confirmationObject->Created)),
	    'lines' => $lines
        );
    }
    
    protected function prepareIPPBill($transactionArray, $id = null)
    {
        if (empty($transactionArray['vendor_supplier_id'])) {
            throw new PlugintoException(PlugintoException::MISSING_REQUIRED_FIELD_CUSTOMER_REF_ERROR_MSG, 
                                        PlugintoException::MISSING_REQUIRED_FIELD_CUSTOMER_REF_ERROR_CODE);
        }
        if (empty($transactionArray['vendor_account_id']) && empty($transactionArray['vendor_item_id'])) {
            throw new PlugintoException(PlugintoException::MISSING_REQUIRED_FIELD_ACCOUNT_ID_ERROR_MSG, 
                                        PlugintoException::MISSING_REQUIRED_FIELD_ACCOUNT_ID_ERROR_CODE);
        }
        
        if ($id) {
            $jsonArr['ID'] = $id;
        }
        $jsonArr['DueDate'] = date("Y-m-d", strtotime("+1 month")); //"2015-02-28";
        $jsonArr['AllowOnlinePayment'] = true;
        $jsonArr['Paid'] = false;
        $jsonArr['SupplierId'] = $transactionArray['vendor_supplier_id']; // 2663585; 
        $jsonArr['Date'] = date("Y-m-d");
        $jsonArr['Inclusive'] = false;
        if (!empty($transactionArray['discount_percent_total'][1])) {
            $jsonArr['DiscountPercentage'] = $transactionArray['discount_percent_total'][1];
        } else {
            $jsonArr['DiscountPercentage'] = 0.0;
        }
        //$jsonArr['TaxReference'] = "";
        //$jsonArr['Reference'] = "";
        //$jsonArr['Message'] = "";
        if (!empty($transactionArray['discount_amount_total'])) {
            $jsonArr['Discount'] = $transactionArray['discount_amount_total'];
        } else {
            $jsonArr['Discount'] = 0.0;
        }
        //$jsonArr['Exclusive'] = 9.9000; //(int)$transactionArray['amount'];
        if (!empty($transactionArray['tax_amount_total'])) {
            $jsonArr['Tax'] = $transactionArray['tax_amount_total'];
        } else {
            $jsonArr['Tax'] = 0.0;
        }
        //$jsonArr['Rounding'] = 0.0000;
        //$jsonArr['Total'] = 9.9000; //$transactionArray['amount'];
        //$jsonArr['AmountDue'] = 9.9000; //$transactionArray['amount'];
        //$jsonArr['Printed'] = false;
        //$jsonArr['Editable'] = true;
        
        
        $lineArr = array();
        $count = max(count($transactionArray['vendor_item_id']), count($transactionArray['vendor_account_id']));
        $totalLineAmount = 0;
        for($k = 1; $k <= $count; $k++) {
            if (!empty($transactionArray['vendor_item_id'][$k])) {
                $eachLine['SelectionId'] =  $transactionArray['vendor_item_id'][$k]; //4180691;
                $eachLine['LineType'] = 0;
            } else if (!empty($transactionArray['vendor_account_id'][$k])) {
                $eachLine['SelectionId'] =  $transactionArray['vendor_account_id'][$k]; 
                $eachLine['LineType'] = 1;
            }
            //$eachLine['TaxTypeId'] = 0;
            if (!empty($transactionArray['description'][$k])) {
                $eachLine['Description'] = $transactionArray['description'][$k];
            } else {
                $eachLine['Description'] = '';
            }
            if (!empty($transactionArray['quantity'][$k])) {
                $eachLine['Quantity'] = $transactionArray['quantity'][$k];
            } else {
                $eachLine['Quantity'] = 1;
            }
            if (!empty($transactionArray['unit_price'][$k])) {
                $eachLine['UnitPriceExclusive'] = $transactionArray['unit_price'][$k];
            } else {
                $eachLine['UnitPriceExclusive'] = 0;
            }
            //$eachLine['Unit'] = "1";
            //$eachLine['UnitPriceInclusive'] = 0;
            if (!empty($transactionArray['tax_percentage'][$k])) {
                $eachLine['TaxPercentage'] = $transactionArray['tax_percentage'][$k];
            } else {
                $eachLine['TaxPercentage'] = 0;
            }
            if (!empty($transactionArray['discount_percentage'][$k])) {
                $eachLine['DiscountPercentage'] = $transactionArray['discount_percentage'][$k];
            } else {
                $eachLine['DiscountPercentage'] = 0.0;
            }
            //$eachLine['Exclusive'] = 0;
            if (!empty($transactionArray['discount'][$k])) {
                $eachLine['Discount'] = $transactionArray['discount'][$k];
            } else {
                $eachLine['Discount'] = 0;
            }
            if (!empty($transactionArray['tax'][$k])) {
                $eachLine['Tax'] = $transactionArray['tax'][$k];
            } else {
                $eachLine['Tax'] = 0;
            }
            //$eachLine['Total'] = 0;
            //$eachLine['Comments'] = "";
            
            $lineArr[] = $eachLine;
        }
        
        $jsonArr['Lines'] = $lineArr;
        
        return json_encode($jsonArr);
    }
    
    // TODO: Purchase services are created successfully to QBO but we do not get response object with values like id.
    public function writePurchase($transactionArray, $update = false, \Application\Entity\Service $service = null)
    {
        $dataService = $this->getDataService($transactionArray['user_id']);

        if ($update) {
            if (!$service) {
                throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                            PlugintoException::INTERNAL_SERVER_ERROR_CODE);
            }
            
            $targetObj = $this->prepareIPPPurchase($transactionArray, $service->getVendorServiceId());
            $addUpdateVar = 'Update';
        } else {
            $targetObj = $this->prepareIPPPurchase($transactionArray);
            $addUpdateVar = 'Add';
        }
        
        $confirmationObject = $this->doWrite($dataService, $addUpdateVar, $targetObj, 'SupplierPayment');    
        
        return array(	
	    'vendor_service_id' => $confirmationObject->ID,
            'transaction_id' => $transactionArray['id'],
            'user_id' => $transactionArray['user_id'],
            'customer_id' => '',
            'supplier_id' => $confirmationObject->SupplierId,
            'account_id' => '',
            'bank_account_id' => $confirmationObject->BankAccountId,
            'doc_number' => $confirmationObject->DocumentNumber,
            'service_type' => 'SupplierPayment',
            'total_amt' => $confirmationObject->Total,
            'txn_date' => date("Y-m-d H:i:s", strtotime($confirmationObject->Created))
        );
    }
    
    protected function prepareIPPPurchase($transactionArray, $id = null)
    {
        if (empty($transactionArray['vendor_supplier_id'])) {
            throw new PlugintoException(PlugintoException::MISSING_REQUIRED_FIELD_CUSTOMER_REF_ERROR_MSG, 
                                        PlugintoException::MISSING_REQUIRED_FIELD_CUSTOMER_REF_ERROR_CODE);
        }
        if (empty($transactionArray['vendor_bank_account_id'])) {
            throw new PlugintoException(PlugintoException::MISSING_REQUIRED_FIELD_ACCOUNT_ID_ERROR_MSG, 
                                        PlugintoException::MISSING_REQUIRED_FIELD_ACCOUNT_ID_ERROR_CODE);
        }
        
        if ($id) {
            $jsonArr['ID'] = $id;
        }
        $jsonArr['SupplierId'] = (int)$transactionArray['vendor_supplier_id'];
        //$jsonArr['Payee'] = "Sample Payee";//$transactionArray['payee'];
        if (!empty($transactionArray['doc_number'])) {
            $jsonArr['DocumentNumber'] = $transactionArray['doc_number'];
        }
        //$jsonArr['Reference'] = "SampleReference";//$transactionArray['reference'];
        if (!empty($transactionArray['transaction_description'])) {
            $jsonArr['Description'] = $transactionArray['transaction_description'];
        }
        if (!empty($transactionArray['amount'])) {
            $jsonArr['Total'] = (float)$transactionArray['amount'];
        } else {
            $jsonArr['Total'] = 0;
        }
        if (!empty($transactionArray['discount_amount_total'])) {
            $jsonArr['Discount'] = $transactionArray['discount_amount_total'];
        }        
        $jsonArr['BankAccountId'] = $transactionArray['vendor_bank_account_id']; // 84893;
        if (isset($transactionArray['payment_type'])) {
            $jsonArr['PaymentMethod'] = $this->getPaymentMethod($transactionArray['payment_type']);
        } else {
            $jsonArr['PaymentMethod'] = 1;
        }
        $jsonArr['Accepted'] = true;
        $jsonArr['Reconciled'] = true;
        $jsonArr['Date'] = date("Y-m-d");
        
        return json_encode($jsonArr);
    }
    
    protected function prepareIPPPayment($transactionArray, $id = null)
    {
        if (empty($transactionArray['vendor_customer_id'])) {
            throw new PlugintoException(PlugintoException::MISSING_REQUIRED_FIELD_CUSTOMER_REF_ERROR_MSG, 
                                        PlugintoException::MISSING_REQUIRED_FIELD_CUSTOMER_REF_ERROR_CODE);
        }
        
        $targetObj = new \IPPPayment();
        if ($id) {
            $targetObj->Id = $id;
            $targetObj->SyncToken = $syncToken;
        }
        $targetObj->TxnDate = $transactionArray['date'];
        $targetObj->CustomerRef = new \IPPReferenceType(array('value' => $transactionArray['vendor_customer_id']));
        $targetObj->TotalAmt = \abs($transactionArray['amount']);
        
        return $targetObj;
    }
    
    private function doDelete(\Application\Entity\Service $service, $dataService, $targetObj, $lineArray)
    {
        $targetObj->Id = $service->getVendorServiceId();
        $targetObj->Line = $lineArray;
        
        try {
            $confirmationObject = $dataService->Delete($targetObj);
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_MSG . ' - ' . $e->getMessage(), 
                                        PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_CODE);
        }
        
        if (!isset($confirmationObject->Id)) {
            throw new PlugintoException(PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_MSG, 
                                        PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_CODE);
        }
        
        return true;
    }

    public function deleteInvoice(\Application\Entity\Service $service, $transactionArray)
    {
        $dataService = $this->getDataService($transactionArray['user_id']);
        
        $targetObj = new \IPPInvoice();
        $ippLine = $this->prepareSalesItemIPPLine($transactionArray['description'], 
                                  \abs($transactionArray['amount']),
                                  $transactionArray['vendor_account_id']);
        $lineArray[] = $ippLine;
        
        return $this->doDelete($service, $dataService, $targetObj, $lineArray);
    }
    
    public function deleteSalesReceipt(\Application\Entity\Service $service, $transactionArray)
    {
        $dataService = $this->getDataService($transactionArray['user_id']);
        
        $targetObj = new \IPPSalesReceipt();
        $ippLine = $this->prepareSalesItemIPPLine($transactionArray['description'], 
                                  \abs($transactionArray['amount']),
                                  $transactionArray['vendor_account_id']);
        $lineArray[] = $ippLine;
        
        return $this->doDelete($service, $dataService, $targetObj, $lineArray);
    }
    
    public function deleteBill(\Application\Entity\Service $service, $transactionArray)
    {
        $dataService = $this->getDataService($transactionArray['user_id']);
        
        $targetObj = new \IPPBill();
        $ippLine = $this->prepareAccountBasedExpenseIPPLine($transactionArray['description'], 
                                            \abs($transactionArray['amount']),
                                            $transactionArray['vendor_account_id']);
        $lineArray[] = $ippLine;
        
        return $this->doDelete($service, $dataService, $targetObj, $lineArray);
    }
    
    public function deletePurchase(\Application\Entity\Service $service, $transactionArray)
    {
        $dataService = $this->getDataService($transactionArray['user_id']);
        
        $targetObj = new \IPPPurchase();
        $ippLine = $this->prepareAccountBasedExpenseIPPLine($transactionArray['description'], 
                                            \abs($transactionArray['amount']),
                                            $transactionArray['vendor_account_id']);
        $lineArray[] = $ippLine;
        
        return $this->doDelete($service, $dataService, $targetObj, $lineArray);
    }
    
    public function dbVendorFieldFilterTransaction($dbFields)
    {
        $fields = array('id', 'user_id', 'customer_id', 'supplier_id', 'account_id', 'bank_account_id', 'tax_amount_total', 'discount_percent_total', 'discount_amount_total', 'amount', 'currency', 'payment_made', 'payment_type', 'date');
        
        foreach($dbFields as $key => $value) {
            if (!in_array($key, $fields)) {
                unset($dbFields[$key]);
            }
        }
        
        return $dbFields;
    }
    
    public function dbVendorFieldFilterTransactionLine($dbFields)
    {
        $fields = array('id', 'line_num', 'account_id', 'item_id', 'description', 'quantity', 'unit_price', 'tax_percentage', 'tax', 'discount_percentage', 'discount', 'amount');
        
        foreach($dbFields as $key => $value) {
            if (!in_array($key, $fields)) {
                unset($dbFields[$key]);
            }
        }
        
        return $dbFields;
    }

    public function dbVendorFieldFilterService($dbFields)
    {
        $fields = array('id', 'vendor_service_id', 'transaction_id', 'user_id', 'customer_id', 'supplier_id', 'description', 'account_id', 'bank_account_id', 'item_id', 'doc_number', 'service_type', 'tax_percentage', 'tax', 'discount_percentage', 'discount', 'balance', 'total_amt', 'due_date', 'payment_type', 'txn_date');
        
        foreach($dbFields as $key => $value) {
            if (!in_array($key, $fields)) {
                unset($dbFields[$key]);
            }
        }
        
        return $dbFields;
    }
    
    
}