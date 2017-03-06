<?php
namespace Accounting\Model\Impl;

use Accounting\Model\SupplierModel;
use lib\exception\PlugintoException;

class SupplierQbModel extends SupplierModel
{
    protected function getConsumerKey()
    {
        return $this->getServiceLocator()->get('GlobalSettings')->getConsumerKey();
    }
    
    protected function getConsumerSecret()
    {
        return $this->getServiceLocator()->get('GlobalSettings')->getConsumerSecret();
    }
    
    protected function getDataService($id)
    {
        require_once(__DIR__ . '/../../../../../../lib/qb-v3-php-sdk-2.0.5/config.php');  // Default V3 PHP SDK (v2.0.4) from IPP
        require_once(PATH_SDK_ROOT . 'Core/ServiceContext.php');
        require_once(PATH_SDK_ROOT . 'DataService/DataService.php');
        require_once(PATH_SDK_ROOT . 'PlatformService/PlatformService.php');
        require_once(PATH_SDK_ROOT . 'Utility/Configuration/ConfigurationManager.php');

        $userModel = $this->getServiceLocator()->get("UserModel");
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

    private function getQbSyncToken($vendorSupplierId, $dataService)
    {
        $supplier = new \IPPVendor();
        $supplier->Id = $vendorSupplierId;
        
        $data = $dataService->FindById($supplier);

        return $data->SyncToken;
    }
    
    private function prepareSupplier($supplierType, $supplierArray, $id = null, $syncToken = null)
    {
        $targetObj = new \IPPVendor();
        
        if ($id) {
            $targetObj->Id = $id;
            $targetObj->SyncToken = $syncToken;
        }
        if (isset($supplierArray['supplier_name'])) {
            $targetObj->CompanyName = $supplierArray['supplier_name'];
        }
        if (isset($supplierArray['display_name'])) {
            $targetObj->DisplayName = $supplierArray['display_name'];
        }
        if (isset($supplierArray['name'])) {
            $targetObj->GivenName = $supplierArray['name'];
        }
        if (isset($supplierArray['surname'])) {
            $targetObj->FamilyName = $supplierArray['surname'];
        }
        
        if (isset($supplierArray['address1'])) {
            $billAddr['Line1'] = $supplierArray['address1'];
        }
        if (isset($supplierArray['address2'])) {
            $billAddr['Line2'] = $supplierArray['address2'];
        }
        if (isset($supplierArray['city'])) {
            $billAddr['City'] = $supplierArray['city'];
        }
        if (isset($supplierArray['postcode'])) {
            $billAddr['PostalCode'] = $supplierArray['postcode'];
        }
        if (isset($supplierArray['country'])) {
            $billAddr['Country'] = $supplierArray['country'];
        }
        
        if ($billAddr) {
            $targetObj->BillAddr = new \IPPPhysicalAddress($billAddr);
        }
        
        return $targetObj;
    }
    
    private function prepareReturnArray($confirmationObject)
    {
        return array(	
	    'vendor_supplier_id' => $confirmationObject->Id,
            'supplier_name' => $confirmationObject->CompanyName,
            'display_name' => $confirmationObject->DisplayName,
            'name' => $confirmationObject->GivenName,
            'surname' => $confirmationObject->FamilyName,
            'address1' => $confirmationObject->BillAddr->Line1,
            'address2' => $confirmationObject->BillAddr->Line2,
            'city' => $confirmationObject->BillAddr->City,
            'postcode' => $confirmationObject->BillAddr->PostalCode,
            'country' => $confirmationObject->BillAddr->Country,
            //'print_on_check_name' => $confirmationObject->PrintOnCheckName,
            //'status' => $confirmationObject->Active,
            //'tax_status' => $confirmationObject->Taxable,
            //'balance' => $confirmationObject->Balance,
            //'preferred_delivery_method' => $confirmationObject->PreferredDeliveryMethod,
            //'create_time' => $confirmationObject->MetaData->CreateTime,
            //'update_time' => $confirmationObject->MetaData->LastUpdatedTime,
            'sync_token' => $confirmationObject->SyncToken
        );
    }

    public function getSupplier($supplierArray, $startPosition = 1, $maxResults = 10)
    {
        $dataService = $this->getDataService($supplierArray['user_id']);
        
        try {
            $data = $dataService->FindAll('Supplier', $startPosition, $maxResults);
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_MSG, 
                                        PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_CODE);
        }    

        return $data;
    }
    
    public function createSupplier($supplierArray)
    {
        if (empty($supplierArray['display_name']) && empty($supplierArray['name']) && empty($supplierArray['surname'])) {
            throw new PlugintoException(PlugintoException::MISSING_REQUIRED_FIELD_DISPLAY_NAME_SURNAME_ERROR_MSG, 
                                        PlugintoException::MISSING_REQUIRED_FIELD_DISPLAY_NAME_SURNAME_ERROR_CODE);
        }
        
        $dataService = $this->getDataService($supplierArray['user_id']);
        
        $targetObj = $this->prepareSupplier('vendor', $supplierArray);
        
        try {
            $confirmationObject = $dataService->Add($targetObj);
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_MSG . ' - ' . $e->getMessage(), 
                                        PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_CODE);
        }
        
        if (!isset($confirmationObject->Id)) {
            throw new PlugintoException(PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_MSG, 
                                        PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_CODE);
        }

        return $this->prepareReturnArray($confirmationObject);
    }
    
    public function updateSupplier(\Application\Entity\Supplier $supplier, $supplierArray)
    {
        $dataService = $this->getDataService($supplierArray['user_id']);
        
        $targetObj = $this->prepareSupplier('vendor', $supplierArray, $supplier->getVendorSupplierId(), $supplier->getSyncToken());

        try {
            $confirmationObject = $dataService->Update($targetObj);
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_MSG . ' - ' . $e->getMessage(), 
                                        PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_CODE);
        }
        
        if (!isset($confirmationObject->Id)) {
            throw new PlugintoException(PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_MSG, 
                                        PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_CODE);
        }

        return $this->prepareReturnArray($confirmationObject);
    }
    
    public function deleteSupplier(\Application\Entity\Supplier $supplier, $supplierArray)
    {
        $dataService = $this->getDataService($supplierArray['user_id']);
        
        $targetObj = $this->prepareSupplier('vendor', $supplierArray, $supplier->getVendorSupplierId(), $supplier->getSyncToken());
        $targetObj->Active = 'false';

        try {
            $confirmationObject = $dataService->Update($targetObj);
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
    
    public function dbVendorFieldFilter($dbFields)
    {
        $fields = array('id', 'vendor_supplier_id', 'user_id', 'supplier_name', 'display_name', 'name', 'surname', 'address1', 'address2', 'city', 'postcode', 'country', 'sync_token');
        
        foreach($dbFields as $key => $value) {
            if (!in_array($key, $fields)) {
                unset($dbFields[$key]);
            }
        }
        
        return $dbFields;
    }
    
}