<?php
namespace Accounting\Model\Impl;

use Accounting\Model\AccountingModel;
use lib\exception\PlugintoException;

class AccountingQbModel extends AccountingModel
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
        require_once(__DIR__ . '/../../../../../../lib/qb-v3-php-sdk-2.0.5/config.php');  // Default V3 PHP SDK (v2.0.4) from IPP
        require_once(PATH_SDK_ROOT . 'Core/ServiceContext.php');
        require_once(PATH_SDK_ROOT . 'DataService/DataService.php');
        require_once(PATH_SDK_ROOT . 'PlatformService/PlatformService.php');
        require_once(PATH_SDK_ROOT . 'Utility/Configuration/ConfigurationManager.php');

        $userModel = $this->getServiceLocator()->get("UserModel");
        //$userModel->setUserId($id);
        $userArr = $userModel->getEntityById($id);
        $userPropertyArr = $userArr['property_list'];
        
        if($this->isAuthenticated($userArr)) {
            $token = unserialize($userPropertyArr['token']);

            $requestValidator = new \OAuthRequestValidator($token['oauth_token'], 
                                                           $token['oauth_token_secret'], 
                                                           $this->getConsumerKey(), 
                                                           $this->getConsumerSecret());

            $serviceContext = new \ServiceContext($userPropertyArr['realm_id'], 
                                                  $userPropertyArr['data_source'], 
                                                  $requestValidator);

            $dataService = new \DataService($serviceContext);
            
            if (!$dataService) {
                throw new PlugintoException(PlugintoException::DATA_SERVICE_RENDER_FAILED_ERROR_MSG, 
                                            PlugintoException::DATA_SERVICE_RENDER_FAILED_ERROR_CODE);
            }
            
            return $dataService;
        } else {
            throw new PlugintoException(PlugintoException::USER_AUTHENTICATION_FAILED_ERROR_MSG, 
                                        PlugintoException::USER_AUTHENTICATION_FAILED_ERROR_CODE);
        }
        
        return false;
    }
    
    public function getAuthUri()
    {
	return '/qb/oauth';
    }

    public function isAuthenticated($userArr) {
	$userPropertyArr = $userArr['property_list'];
	if (empty($userPropertyArr['token'])) {
            return false;
        }
        
        $date_diff = (strtotime($userPropertyArr['token_exp_date']) - strtotime("now"))/(3600*24);
        if ($date_diff < 0) {
            return false;
        }
	//TODO - check if we can check at QB that we are really authenticated (call some method?)
        return true;

    }
    
    public function notAuthenticated($userModel, $userArr, $id, $returnUrl)
    {
        $token = md5(rand());
        $userArr['property_list']['_pi_auth_token'] = $token;
        $userArr = $userModel->updateEntity($userArr['id'], $userArr);

        $settings = $this->getServiceLocator()->get('GlobalSettings');
        //$result = array('data'=>false,'auth_url'=> $settings->getBaseUrl() . $accounting->getAuthUri() . "/$id/$returnUrl/$token");
        throw new PlugintoException(PlugintoException::USER_NOT_AUTHENTICATED_ERROR_MSG . $settings->getBaseUrl() . $this->getAuthUri() . "/$id/$returnUrl/$token", 
                            PlugintoException::USER_NOT_AUTHENTICATED_ERROR_CODE);
    }
    
    private function getQbSyncToken($vendorServiceId, $dataService, $serviceName)
    {
        switch($serviceName) {
            case 'Invoice':
                $service = new \IPPInvoice();
                break;
            case 'SalesReceipt':
                $service = new \IPPSalesReceipt();
                break;
            case 'Bill':
                $service = new \IPPBill();
                break;
            case 'Purchase':
                $service = new \IPPPurchase();
                break;
        }
        $service->Id = $vendorServiceId;
        
        $data = $dataService->FindById($service);

        return $data->SyncToken;
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
    
    private function prepareSalesItemIPPLine($description, $itemRef, $unitPrice = 0, $qty = 1, $taxCodeRef = '')
    {
        $ippSalesItemLineDetail['ItemRef'] = new \IPPReferenceType(array('value' => $itemRef));
        if ($taxCodeRef) {
            $ippSalesItemLineDetail['TaxCodeRef'] = $taxCodeRef;
        }
        if ($unitPrice) {
            $ippSalesItemLineDetail['UnitPrice'] = $unitPrice;
        }
        if ($qty) {
            $ippSalesItemLineDetail['Qty'] = $qty;
            $amount = $unitPrice * $qty;
        } else {
            $amount = $unitPrice;
        }
        
        $ippLine = new \IPPLine(array(
            'Description' => $description,
            'Amount' => $amount,
            'DetailType' => 'SalesItemLineDetail',
            'SalesItemLineDetail' =>
                new \IPPSalesItemLineDetail(
                    $ippSalesItemLineDetail
                ),
            )
        );
        
        return $ippLine;
    }
    
    private function prepareDiscountLine($percent, $amount = null, $percentBased = true, $accountRef = '')
    {
//        if ($accountRef) {
//            $ippDiscountLineDetail['DiscountAccountRef'] = new \IPPReferenceType(array('value' => $accountRef));
//        }
        
        $ippDiscountLineDetail['PercentBased'] = true;
        $ippDiscountLineDetail['DiscountPercent'] = $percent;
        $ippLine = new \IPPLine(array(
            'DetailType' => 'DiscountLineDetail',
            'DiscountLineDetail' =>
                new \IPPDiscountLineDetail(
                    $ippDiscountLineDetail
                ),
            )
        );
        
        return $ippLine;
    }
    
    private function prepareAccountBasedExpenseIPPLine($description, $amount, $accountRef, $taxCodeRef = '')
    {
        $ippLine = new \IPPLine(array(
            'Description' => $description,
            'Amount' => $amount,
            'DetailType' => 'AccountBasedExpenseLineDetail',
            'AccountBasedExpenseLineDetail' =>
                new \IPPAccountBasedExpenseLineDetail(
                    array(
                        'AccountRef' => new \IPPReferenceType(array('value' => $accountRef)),
                        'TaxCodeRef' => $taxCodeRef
                    )
                ),
            )
        );
        
        return $ippLine;
    }
    
    private function prepareItemBasedExpenseIPPLine($description, $amount, $itemRef)
    {
        $ippLine = new \IPPLine(array(
            'Description' => $description,
            'Amount' => $amount,
            'DetailType' => 'ItemBasedExpenseLineDetail',
            'ItemBasedExpenseLineDetail' =>
                new \IPPItemBasedExpenseLineDetail(
                    array('ItemRef' => 
                        new \IPPReferenceType(array('value' => $itemRef))
                    )
                ),
            )
        );
        
        return $ippLine;
    }
    
    private function prepareIPPTxnTaxDetail($taxRateArr, $taxCodeId)
    {
        $totalTax = 0;
        $taxLines = array();
        foreach($taxRateArr as $taxRate) {
            $amount = $taxRate['NetAmountTaxable'] * $taxRate['TaxPercent'] / 100;
            $totalTax = $totalTax + $amount;
            $taxLines = new \IPPLine(array(
                'Amount' => $amount,
                'DetailType' => 'TaxLineDetail',
                'TaxLineDetail' =>
                    new \IPPTaxLineDetail(
                        array(
                            'TaxRateRef' => new \IPPReferenceType(array('value' => $taxRate['TaxRateRef'])),
                            'PercentBased' => true,
                            'TaxPercent' => $taxRate['TaxPercent'],
                            'NetAmountTaxable' => $taxRate['NetAmountTaxable']
                        )
                    ),
                )
            );
            break;
        }

        
        $ippTxnTaxDetail = new \IPPTxnTaxDetail(array(
            'TxnTaxCodeRef' => $taxCodeId,
            'TotalTax' => $totalTax,
            'TaxLine' => $taxLines
        ));
        
        return $ippTxnTaxDetail;
    }
    
    private function prepareIPPTxnTaxDetailIntl($taxRateArr)
    {
        $totalTax = 0;
        $taxLines = array();
        foreach($taxRateArr as $taxRate) {
            $amount = $taxRate['NetAmountTaxable'] * $taxRate['TaxPercent'] / 100;
            $totalTax = $totalTax + $amount;
            $taxLines[] = new \IPPLine(array(
                'Amount' => $amount,
                'DetailType' => 'TaxLineDetail',
                'TaxLineDetail' =>
                    new \IPPTaxLineDetail(
                        array(
                            'TaxRateRef' => new \IPPReferenceType(array('value' => $taxRate['TaxRateRef'])),
                            'PercentBased' => true,
                            'TaxPercent' => $taxRate['TaxPercent'],
                            'NetAmountTaxable' => $taxRate['NetAmountTaxable']
                        )
                    ),
                )
            );
        }

        
        $ippTxnTaxDetail = new \IPPTxnTaxDetail(array(
            'TotalTax' => $totalTax,
            'TaxLine' => $taxLines
        ));
        
        return $ippTxnTaxDetail;
    }
    
    private function doWrite($dataService, $addUpdateVar, $targetObj)
    {
        try {
            $confirmationObject = $dataService->$addUpdateVar($targetObj);
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_MSG . ' - ' . $e->getMessage(), 
                                        PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_CODE);
        }
        
        if (!isset($confirmationObject->Id)) {
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

            $syncToken = $this->getQbSyncToken($service->getVendorServiceId(), $dataService, 'Invoice');
            $service->setSyncToken($syncToken);
            
            $targetObj = $this->prepareIPPInvoice($transactionArray, $service->getVendorServiceId(), $service->getSyncToken());
            $addUpdateVar = 'Update';
        } else {
            $targetObj = $this->prepareIPPInvoice($transactionArray);
            $addUpdateVar = 'Add';
        }

        $confirmationObject = $this->doWrite($dataService, $addUpdateVar, $targetObj);
//        echo '<pre>';
//        print_r($confirmationObject);
//        echo '</pre>';

        $lines = array();
        if (isset($confirmationObject->Line)) {
            foreach ($confirmationObject->Line as $line) {
                $itemRef = isset($line->SalesItemLineDetail->ItemRef) ? $line->SalesItemLineDetail->ItemRef : '';
                $description = $line->Description;
                if (empty($description)) {
                    switch($line->DetailType) {
                        case 'SubTotalLineDetail':
                                $description = 'Sub Total';
                            break;
                        case 'DiscountLineDetail':
                                $description = 'Discount';
                            break;
                    }
                }
                $lines[] = array(
                    'line_num' => $line->LineNum,
                    'item_id' => $itemRef,
                    'account_id' => '',
                    'description' =>  $description,
                    'quantity' => $line->SalesItemLineDetail->Qty,
                    'unit_price' => $line->SalesItemLineDetail->UnitPrice,
                    'amount' => $line->Amount,
                    'detail_type' => $line->DetailType
                );
            }
        }
        
        $taxLines = array();
        if (isset($confirmationObject->TxnTaxDetail->TaxLine)) {
            foreach ($confirmationObject->TxnTaxDetail->TaxLine as $taxLine) {
                $itemRef = isset($line->SalesItemLineDetail->ItemRef) ? $line->SalesItemLineDetail->ItemRef : '';
                $taxLines[] = array(
                    'description' => 'Tax',
                    'amount' => $taxLine->Amount,
                    'detail_type' => $taxLine->DetailType
                );
            }
        }
        return array(	
	    'vendor_service_id' => $confirmationObject->Id,
            'transaction_id' => $transactionArray['id'],
            'user_id' => $transactionArray['user_id'],
            'customer_id' => $confirmationObject->CustomerRef,
            'supplier_id' => '',
            'account_id_financial' => '',
            //'item_id' => '',
            'tax_id' => '',
            'doc_number' => $confirmationObject->DocNumber,
            'service_type' => 'Invoice',
            'balance' => $confirmationObject->Balance,
            'total_amt' => $confirmationObject->TotalAmt,
            'due_date' => $confirmationObject->DueDate,
            'txn_date' => $confirmationObject->TxnDate,
            'sync_token' => $confirmationObject->SyncToken,
	    'lines' => $lines,
            'tax_lines' => $taxLines
        );
    }
    
    protected function prepareIPPInvoice($transactionArray, $id = null, $syncToken = null)
    {
        if (empty($transactionArray['vendor_customer_id'])) {
            throw new PlugintoException(PlugintoException::MISSING_REQUIRED_FIELD_CUSTOMER_REF_ERROR_MSG, 
                                        PlugintoException::MISSING_REQUIRED_FIELD_CUSTOMER_REF_ERROR_CODE);
        }
        if (empty($transactionArray['vendor_item_id'])) {
            throw new PlugintoException(PlugintoException::MISSING_REQUIRED_FIELD_ACCOUNT_ID_ERROR_MSG, 
                                        PlugintoException::MISSING_REQUIRED_FIELD_ACCOUNT_ID_ERROR_CODE);
        }
        
        $dataService = $this->getCurrentDataService();
        $data = $dataService->Query("SELECT * FROM CompanyInfo");
        $taxVersionUS = isset($data[0]->Country) && $data[0]->Country == 'US' ? true : false;
        
        $taxCodeRef = '';
        if ($taxVersionUS) {
            if (!empty($transactionArray['tax_code_id'])) {
                $taxCodeRef = 'TAX';
            } else {
                $taxCodeRef = 'NON';
            }
        } else {
            if (!empty($transactionArray['tax_code_id'])) {
                $taxCodeRef = $transactionArray['tax_code_id'];
            }
        }
        
        $count = count($transactionArray['vendor_item_id']); 
        $totalLineAmount = 0;
        for($k = 1; $k <= $count; $k++) {
            $ippLine = $this->prepareSalesItemIPPLine($transactionArray['description'][$k], 
                                                  $transactionArray['vendor_item_id'][$k],
                                                  \abs($transactionArray['unit_price'][$k]), 
                                                  $transactionArray['quantity'][$k], 
                                                  $taxCodeRef);
            $lineArray[] = $ippLine;
            $totalLineAmount = $totalLineAmount + $transactionArray['unit_price'][$k];
        }
        
        $percentBased = false;
        $discountAmount = 0;
        $discountPercent = 0;
        if (!empty($transactionArray['discount_amount_total'])) {
            $discountAmount = $transactionArray['discount_amount_total'];
        } else if (!empty($transactionArray['discount_percent_total'])) {
            $discountPercent = $transactionArray['discount_percent_total'];
            $percentBased = true;
        }
        
        if ($discountPercent) {
            $lineArray[] = $this->prepareDiscountLine($discountPercent);
        }
        
        if (!empty($transactionArray['tax_rate_arr']) && !empty($transactionArray['tax_code_id'])) {
            if ($taxVersionUS) {
                $txnTaxDetail = $this->prepareIPPTxnTaxDetail($transactionArray['tax_rate_arr'],
                                                              $transactionArray['tax_code_id']);
            } else {
                $txnTaxDetail = $this->prepareIPPTxnTaxDetailIntl($transactionArray['tax_rate_arr']);
            }
        }
        
        $targetObj = new \IPPInvoice();
        if ($id) {
            $targetObj->Id = $id;
            $targetObj->SyncToken = $syncToken;
        }
        $targetObj->DueDate = $transactionArray['due_date'];
        $targetObj->TxnDate = $transactionArray['txn_date'];
        $targetObj->CustomerRef = new \IPPReferenceType(array('value' => $transactionArray['vendor_customer_id']));
        $targetObj->Line = $lineArray;
        if (isset($txnTaxDetail)) {
            $targetObj->TxnTaxDetail = $txnTaxDetail;
        }
        
        return $targetObj;
    }
    
    public function writeSalesReceipt($transactionArray, $update = false, \Application\Entity\Service $service = null)
    {
        $dataService = $this->getDataService($transactionArray['user_id']);
        
        $this->setCurrentDataService($dataService);
        
        if ($update) {
            if (!$service) {
                throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                            PlugintoException::INTERNAL_SERVER_ERROR_CODE);
            }
            
            $syncToken = $this->getQbSyncToken($service->getVendorServiceId(), $dataService, 'SalesReceipt');
            $service->setSyncToken($syncToken);
            
            $targetObj = $this->prepareIPPSalesReceipt($transactionArray, $service->getVendorServiceId(), $service->getSyncToken());
            $addUpdateVar = 'Update';
        } else {
            $targetObj = $this->prepareIPPSalesReceipt($transactionArray);
            $addUpdateVar = 'Add';
        }
        
        $confirmationObject = $this->doWrite($dataService, $addUpdateVar, $targetObj);    

        $lines = array();
        if (isset($confirmationObject->Line)) {
            foreach ($confirmationObject->Line as $line) {
                $itemRef = isset($line->SalesItemLineDetail->ItemRef) ? $line->SalesItemLineDetail->ItemRef : '';
                $description = $line->Description;
                if (empty($description)) {
                    switch($line->DetailType) {
                        case 'SubTotalLineDetail':
                                $description = 'Sub Total';
                            break;
                        case 'DiscountLineDetail':
                                $description = 'Discount';
                            break;
                    }
                }
                $lines[] = array(
                    'line_num' => $line->LineNum,
                    'item_id' => $itemRef,
                    'account_id' => '',
                    'description' =>  $description,
                    'quantity' => $line->SalesItemLineDetail->Qty,
                    'unit_price' => $line->SalesItemLineDetail->UnitPrice,
                    'amount' => $line->Amount,
                    'detail_type' => $line->DetailType
                );
            }
        }
        
        $taxLines = array();
        if (isset($confirmationObject->TxnTaxDetail->TaxLine)) {
            foreach ($confirmationObject->TxnTaxDetail->TaxLine as $taxLine) {
                $itemRef = isset($line->SalesItemLineDetail->ItemRef) ? $line->SalesItemLineDetail->ItemRef : '';
                $taxLines[] = array(
                    'description' => 'Tax',
                    'amount' => $taxLine->Amount,
                    'detail_type' => $taxLine->DetailType
                );
            }
        }

        return array(	
	    'vendor_service_id' => $confirmationObject->Id,
            'transaction_id' => $transactionArray['id'],
            'user_id' => $transactionArray['user_id'],
            'customer_id' => $confirmationObject->CustomerRef,
            'supplier_id' => '',
            'account_id' => '',
            'account_id_financial' => '',
            //'item_id' => '',
            'tax_id' => '',
            'doc_number' => $confirmationObject->DocNumber,
            'service_type' => 'SalesReceipt',
            'balance' => $confirmationObject->Balance,
            'total_amt' => $confirmationObject->TotalAmt,
            'due_date' => $confirmationObject->DueDate,
            'txn_date' => $confirmationObject->TxnDate,
            'sync_token' => $confirmationObject->SyncToken,
	    'lines' => $lines,
            'tax_lines' => $taxLines
        );
    }
    
    protected function prepareIPPSalesReceipt($transactionArray, $id = null, $syncToken = null)
    {
        if (empty($transactionArray['vendor_customer_id'])) {
            throw new PlugintoException(PlugintoException::MISSING_REQUIRED_FIELD_CUSTOMER_REF_ERROR_MSG, 
                                        PlugintoException::MISSING_REQUIRED_FIELD_CUSTOMER_REF_ERROR_CODE);
        }
        if (empty($transactionArray['vendor_item_id'])) {
            throw new PlugintoException(PlugintoException::MISSING_REQUIRED_FIELD_ACCOUNT_ID_ERROR_MSG, 
                                        PlugintoException::MISSING_REQUIRED_FIELD_ACCOUNT_ID_ERROR_CODE);
        }
        
        $dataService = $this->getCurrentDataService();
        $data = $dataService->Query("SELECT * FROM CompanyInfo");
        $taxVersionUS = isset($data[0]->Country) && $data[0]->Country == 'US' ? true : false;
        
        $taxCodeRef = '';
        if ($taxVersionUS) {
            if (!empty($transactionArray['tax_code_id'])) {
                $taxCodeRef = 'TAX';
            } else {
                $taxCodeRef = 'NON';
            }
        } else {
            if (!empty($transactionArray['tax_code_id'])) {
                $taxCodeRef = $transactionArray['tax_code_id'];
            }
        }
        
        $count = count($transactionArray['vendor_item_id']);
        $totalLineAmount = 0;
        for($k = 1; $k <= $count; $k++) {
            $ippLine = $this->prepareSalesItemIPPLine($transactionArray['description'][$k], 
                                                  $transactionArray['vendor_item_id'][$k],
                                                  \abs($transactionArray['unit_price'][$k]), 
                                                  $transactionArray['quantity'][$k], 
                                                  $taxCodeRef);
            $lineArray[] = $ippLine;
            
            $totalLineAmount = $totalLineAmount + $transactionArray['unit_price'][$k];
        }
        
        $percentBased = false;
        $discountAmount = 0;
        $discountPercent = 0;
        if (!empty($transactionArray['discount_amount_total'])) {
            $discountAmount = $transactionArray['discount_amount_total'];
        } else if (!empty($transactionArray['discount_percent_total'])) {
            $discountPercent = $transactionArray['discount_percent_total'];
            $percentBased = true;
        }
        if ($discountPercent) {
            $lineArray[] = $this->prepareDiscountLine($discountPercent);
        }
        
        if (!empty($transactionArray['tax_rate_arr']) && !empty($transactionArray['tax_code_id'])) {
            if ($taxVersionUS) {
                $txnTaxDetail = $this->prepareIPPTxnTaxDetail($transactionArray['tax_rate_arr'],
                                                              $transactionArray['tax_code_id']);
            } else {
                $txnTaxDetail = $this->prepareIPPTxnTaxDetailIntl($transactionArray['tax_rate_arr']);
            }
        }
        
        $targetObj = new \IPPSalesReceipt();
        if ($id) {
            $targetObj->Id = $id;
            $targetObj->SyncToken = $syncToken;
        }
        $targetObj->DueDate = $transactionArray['due_date'];
        $targetObj->TxnDate = $transactionArray['txn_date'];
        $targetObj->CustomerRef = new \IPPReferenceType(array('value' => $transactionArray['vendor_customer_id']));
        $targetObj->Line = $lineArray;
        if (isset($txnTaxDetail)) {
            $targetObj->TxnTaxDetail = $txnTaxDetail;
        }
        
        return $targetObj;
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
            
            $syncToken = $this->getQbSyncToken($service->getVendorServiceId(), $dataService, 'Bill');
            $service->setSyncToken($syncToken);
            
            $targetObj = $this->prepareIPPBill($transactionArray, $service->getVendorServiceId(), $service->getSyncToken());
            $addUpdateVar = 'Update';
        } else {
            $targetObj = $this->prepareIPPBill($transactionArray);
            $addUpdateVar = 'Add';
        }
        
//        echo '<pre>';
//        print_r($targetObj);
//        echo '</pre>';
        
        $confirmationObject = $this->doWrite($dataService, $addUpdateVar, $targetObj);
        
//        echo '<pre>';
//        print_r($confirmationObject);
//        echo '</pre>';
        
        $lines = array();
        if (isset($confirmationObject->Line)) {
            if (is_array($confirmationObject->Line)) {
                foreach ($confirmationObject->Line as $line) {
                    $lines[] = $this->processBillConfirmationLine($line);
                }
            } else {
                $lines[] = $this->processBillConfirmationLine($confirmationObject->Line);
            }
            
        }
        
        $taxLines = array();
        if (isset($confirmationObject->TxnTaxDetail->TaxLine)) {
            foreach ($confirmationObject->TxnTaxDetail->TaxLine as $taxLine) {
                $itemRef = isset($line->SalesItemLineDetail->ItemRef) ? $line->SalesItemLineDetail->ItemRef : '';
                $taxLines[] = array(
                    'description' => 'Tax',
                    'amount' => $taxLine->Amount,
                    'detail_type' => $taxLine->DetailType
                );
            }
        }
        
        return array(	
	    'vendor_service_id' => $confirmationObject->Id,
            'transaction_id' => $transactionArray['id'],
            'user_id' => $transactionArray['user_id'],
            'customer_id' => '',
            'supplier_id' => $confirmationObject->VendorRef,
            'account_id' => '',
            'account_id_financial' => '',
            //'item_id' => '',
            'tax_id' => '',
            'doc_number' => $confirmationObject->DocNumber,
            'service_type' => 'Bill',
            'balance' => $confirmationObject->Balance,
            'total_amt' => $confirmationObject->TotalAmt,
            'due_date' => $confirmationObject->DueDate,
            'txn_date' => $confirmationObject->TxnDate,
            'sync_token' => $confirmationObject->SyncToken,
	    'lines' => $lines,
            'tax_lines' => $taxLines    
        );
    }
    
    private function processBillConfirmationLine($line) 
    {
        $itemRef = isset($line->SalesItemLineDetail->ItemRef) ? $line->SalesItemLineDetail->ItemRef : '';
        $accountRef = isset($line->AccountBasedExpenseLineDetail) ? $line->AccountBasedExpenseLineDetail->AccountRef : '';
        $description = $line->Description;
        if (empty($description)) {
            switch($line->DetailType) {
                case 'SubTotalLineDetail':
                        $description = 'Sub Total';
                    break;
                case 'DiscountLineDetail':
                        $description = 'Discount';
                    break;
            }
        }
        
        return array(
            'line_num' => $line->LineNum,
            'item_id' => $itemRef,
            'account_id' => $accountRef,
            'description' =>  $description,
            'quantity' => $line->SalesItemLineDetail->Qty,
            'unit_price' => $line->SalesItemLineDetail->UnitPrice,
            'amount' => $line->Amount,
            'detail_type' => $line->DetailType
        );
        
    }
    
    protected function prepareIPPBill($transactionArray, $id = null, $syncToken = null)
    {
        if (empty($transactionArray['vendor_supplier_id'])) {
            throw new PlugintoException(PlugintoException::MISSING_REQUIRED_FIELD_VENDOR_REF_ERROR_MSG, 
                                        PlugintoException::MISSING_REQUIRED_FIELD_VENDOR_REF_ERROR_CODE);
        }
        if (empty($transactionArray['vendor_account_id'])) {
            throw new PlugintoException(PlugintoException::MISSING_REQUIRED_FIELD_ACCOUNT_ID_ERROR_MSG, 
                                        PlugintoException::MISSING_REQUIRED_FIELD_ACCOUNT_ID_ERROR_CODE);
        }
        
        $count = count($transactionArray['vendor_account_id']);
        $totalLineAmount = 0;
        for($k = 1; $k <= $count; $k++) {
            $ippLine = $this->prepareAccountBasedExpenseIPPLine($transactionArray['description'][$k], 
                                                        \abs($transactionArray['unit_price'][$k]),
                                                        $transactionArray['vendor_account_id'][$k]);
                    
            $lineArray[] = $ippLine;
            $totalLineAmount = $totalLineAmount + $transactionArray['unit_price'][$k];
        }
        
        $targetObj = new \IPPBill();
        if ($id) {
            $targetObj->Id = $id;
            $targetObj->SyncToken = $syncToken;
        }
        
        $targetObj->DueDate = $transactionArray['due_date'];
        $targetObj->TxnDate = $transactionArray['txn_date'];
        $targetObj->VendorRef = new \IPPReferenceType(array('value' => $transactionArray['vendor_supplier_id']));
        $targetObj->Line = $lineArray;
        
        return $targetObj;
    }
    
    public function writePurchase($transactionArray, $update = false, \Application\Entity\Service $service = null)
    {
        $dataService = $this->getDataService($transactionArray['user_id']);
        
        $this->setCurrentDataService($dataService);
        
        if ($update) {
            if (!$service) {
                throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                            PlugintoException::INTERNAL_SERVER_ERROR_CODE);
            }
            
            $syncToken = $this->getQbSyncToken($service->getVendorServiceId(), $dataService, 'Purchase');
            $service->setSyncToken($syncToken);
            
            $targetObj = $this->prepareIPPPurchase($transactionArray, $service->getVendorServiceId(), $service->getSyncToken());
            $addUpdateVar = 'Update';
        } else {
            $targetObj = $this->prepareIPPPurchase($transactionArray);
            $addUpdateVar = 'Add';
        }
        
        $confirmationObject = $this->doWrite($dataService, $addUpdateVar, $targetObj);
        
//        echo '<pre>';
//        print_r($confirmationObject);
//        echo '</pre>';

        $lines = array();
        if (is_array($confirmationObject->Line)) {
            foreach ($confirmationObject->Line as $line) {
                $itemRef = isset($line->SalesItemLineDetail->ItemRef) ? $line->SalesItemLineDetail->ItemRef : '';
                $accountRef = isset($line->AccountBasedExpenseLineDetail) ? $line->AccountBasedExpenseLineDetail->AccountRef : '';
                $lines[] = array(
                    'line_num' => $line->LineNum,
                    'account_id' => $accountRef,
                    'description' => $line->Description,
                    'amount' => $line->Amount,
                    'detail_type' => $line->DetailType
                );
            }
        } else {
            $line = $confirmationObject->Line;
            $accountRef = isset($line->AccountBasedExpenseLineDetail) ? $line->AccountBasedExpenseLineDetail->AccountRef : '';
            $lines[] = array(
                'line_num' => $line->LineNum,
                'account_id' => $accountRef,
                'description' => $line->Description,
                'amount' => $line->Amount,
                'detail_type' => $line->DetailType
            );
        }
        
        $taxLines = array();
        if (isset($confirmationObject->TxnTaxDetail->TaxLine)) {
            foreach ($confirmationObject->TxnTaxDetail->TaxLine as $taxLine) {
                $itemRef = isset($line->SalesItemLineDetail->ItemRef) ? $line->SalesItemLineDetail->ItemRef : '';
                $taxLines[] = array(
                    'description' => 'Tax',
                    'amount' => $taxLine->Amount,
                    'detail_type' => $taxLine->DetailType
                );
            }
        }
        
        return array(	
	    'vendor_service_id' => $confirmationObject->Id,
            'transaction_id' => $transactionArray['id'],
            'user_id' => $transactionArray['user_id'],
            'customer_id' => '',
            'supplier_id' => $confirmationObject->EntityRef,
            'account_id' => '',
            'account_id_financial' => '',
            //'item_id' => '',
            'tax_id' => '',
            'doc_number' => $confirmationObject->DocNumber,
            'service_type' => 'Purchase',
            'balance' => '',
            'total_amt' => $confirmationObject->TotalAmt,
            //'account_id' => $confirmationObject->AccountRef,
            'due_date' => $confirmationObject->DueDate,
            'txn_date' => $confirmationObject->TxnDate,
            'payment_type' => $confirmationObject->PaymentType,
            'sync_token' => $confirmationObject->SyncToken,
	    'lines' => $lines,
            'tax_lines' => $taxLines
        );
    }
    
    protected function prepareIPPPurchase($transactionArray, $id = null, $syncToken = null)
    {
        if (empty($transactionArray['vendor_supplier_id'])) {
            throw new PlugintoException(PlugintoException::MISSING_REQUIRED_FIELD_CUSTOMER_REF_ERROR_MSG, 
                                        PlugintoException::MISSING_REQUIRED_FIELD_CUSTOMER_REF_ERROR_CODE);
        }
        if (empty($transactionArray['vendor_account_id'])) {
            throw new PlugintoException(PlugintoException::MISSING_REQUIRED_FIELD_ACCOUNT_ID_ERROR_MSG, 
                                        PlugintoException::MISSING_REQUIRED_FIELD_ACCOUNT_ID_ERROR_CODE);
        }
        if (empty($transactionArray['vendor_account_id_financial'])) {
            throw new PlugintoException(PlugintoException::MISSING_REQUIRED_FIELD_FINANCIAL_ACCOUNT_ERROR_MSG, 
                                        PlugintoException::MISSING_REQUIRED_FIELD_FINANCIAL_ACCOUNT_ERROR_CODE);
        }
        if (empty($transactionArray['payment_type'])) {
            throw new PlugintoException(PlugintoException::MISSING_REQUIRED_FIELD_PAYMENT_TYPE_ERROR_MSG, 
                                        PlugintoException::MISSING_REQUIRED_FIELD_PAYMENT_TYPE_ERROR_CODE);
        }
        
        $count = count($transactionArray['vendor_account_id']);
        $totalLineAmount = 0;
        for($k = 1; $k <= $count; $k++) {
            $ippLine = $this->prepareAccountBasedExpenseIPPLine($transactionArray['description'][$k], 
                                                        \abs($transactionArray['unit_price'][$k]),
                                                        $transactionArray['vendor_account_id'][$k],
                                                        $taxCodeRef);
                    
            $lineArray[] = $ippLine;
            $totalLineAmount = $totalLineAmount + $transactionArray['unit_price'][$k];
        }
        
        $targetObj = new \IPPPurchase();
        if ($id) {
            $targetObj->Id = $id;
            $targetObj->SyncToken = $syncToken;
        }
        $targetObj->DueDate = $transactionArray['due_date'];
        $targetObj->TxnDate = $transactionArray['txn_date'];
        $targetObj->AccountRef = new \IPPReferenceType(array('value' => $transactionArray['vendor_account_id_financial']));
        $targetObj->EntityRef = new \IPPReferenceType(array('name' => 'Vendor', 'value' => $transactionArray['vendor_supplier_id']));
        $targetObj->PaymentType = $transactionArray['payment_type'];
        $targetObj->Line = $lineArray;
        if (isset($txnTaxDetail)) {
            $targetObj->TxnTaxDetail = $txnTaxDetail;
        }
        
        return $targetObj;
    }
    
    protected function prepareIPPPayment($transactionArray, $id = null, $syncToken = null)
    {
        if (empty($transactionArray['vendor_supplier_id'])) {
            throw new PlugintoException(PlugintoException::MISSING_REQUIRED_FIELD_CUSTOMER_REF_ERROR_MSG, 
                                        PlugintoException::MISSING_REQUIRED_FIELD_CUSTOMER_REF_ERROR_CODE);
        }
        
        $targetObj = new \IPPPayment();
        if ($id) {
            $targetObj->Id = $id;
            $targetObj->SyncToken = $syncToken;
        }
        $targetObj->DueDate = $transactionArray['due_date'];
        $targetObj->TxnDate = $transactionArray['txn_date'];
        //$targetObj->TxnDate = $transactionArray['date'];
        $targetObj->CustomerRef = new \IPPReferenceType(array('value' => $transactionArray['vendor_supplier_id']));
        $targetObj->TotalAmt = \abs($transactionArray['amount']);
        
        return $targetObj;
    }
    
    private function doDelete(\Application\Entity\Service $service, $dataService, $targetObj, $lineArray)
    {
        $targetObj->Id = $service->getVendorServiceId();
        $targetObj->SyncToken =  $service->getSyncToken();
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
                                  $transactionArray['vendor_item_id']);
        $lineArray[] = $ippLine;
        
        return $this->doDelete($service, $dataService, $targetObj, $lineArray);
    }
    
    public function deleteSalesReceipt(\Application\Entity\Service $service, $transactionArray)
    {
        $dataService = $this->getDataService($transactionArray['user_id']);
        
        $targetObj = new \IPPSalesReceipt();
        $ippLine = $this->prepareSalesItemIPPLine($transactionArray['description'], 
                                  \abs($transactionArray['amount']),
                                  $transactionArray['vendor_item_id']);
        $lineArray[] = $ippLine;
        
        return $this->doDelete($service, $dataService, $targetObj, $lineArray);
    }
    
    public function deleteBill(\Application\Entity\Service $service, $transactionArray)
    {
        $dataService = $this->getDataService($transactionArray['user_id']);
        
        $targetObj = new \IPPBill();
        $ippLine = $this->prepareAccountBasedExpenseIPPLine($transactionArray['description'], 
                                            \abs($transactionArray['amount']),
                                            $transactionArray['vendor_item_id']);
        $lineArray[] = $ippLine;
        
        return $this->doDelete($service, $dataService, $targetObj, $lineArray);
    }
    
    public function deletePurchase(\Application\Entity\Service $service, $transactionArray)
    {
        $dataService = $this->getDataService($transactionArray['user_id']);
        
        $targetObj = new \IPPPurchase();
        $ippLine = $this->prepareAccountBasedExpenseIPPLine($transactionArray['description'], 
                                            \abs($transactionArray['amount']),
                                            $transactionArray['vendor_item_id']);
        $lineArray[] = $ippLine;
        
        return $this->doDelete($service, $dataService, $targetObj, $lineArray);
    }
    
    public function dbVendorFieldFilterTransaction($dbFields)
    {
        $fields = array('id', 'user_id', 'customer_id', 'supplier_id', 'account_id', 'account_id_financial', 'tax_id', 'discount_percent_total', 'discount_amount_total', 'amount', 'currency', 'payment_made', 'payment_type', 'date');
        
        foreach($dbFields as $key => $value) {
            if (!in_array($key, $fields)) {
                unset($dbFields[$key]);
            }
        }
        
        return $dbFields;
    }
    
    public function dbVendorFieldFilterTransactionLine($dbFields)
    {
        $fields = array('id', 'line_num', 'account_id', 'item_id', 'description', 'quantity', 'unit_price', 'amount');
        
        foreach($dbFields as $key => $value) {
            if (!in_array($key, $fields)) {
                unset($dbFields[$key]);
            }
        }
        
        return $dbFields;
    }
    
    public function dbVendorFieldFilterService($dbFields)
    {
        $fields = array('id', 'vendor_service_id', 'transaction_id', 'user_id', 'customer_id', 'supplier_id', 'description', 'account_id', 'account_id_financial', 'item_id', 'tax_id', 'doc_number', 'service_type', 'tax_percentage', 'tax', 'discount_percentage', 'discount', 'balance', 'total_amt', 'due_date', 'payment_type', 'txn_date', 'sync_token');
        
        foreach($dbFields as $key => $value) {
            if (!in_array($key, $fields)) {
                unset($dbFields[$key]);
            }
        }
        
        return $dbFields;
    }
}