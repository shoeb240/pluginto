<?php
namespace Accounting\Model\Impl;

use Accounting\Model\CustomerModel;
use lib\exception\PlugintoException;

class CustomerQbModel extends CustomerModel
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

    public function getCustomer($customerArray, $startPosition = 1, $maxResults = 10)
    {
        $dataService = $this->getDataService($customerArray['user_id']);
        
        try {
            $data = $dataService->FindAll('Customer', $startPosition, $maxResults);
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_MSG, 
                                        PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_CODE);
        }    

        return $data;
    }
    
    private function getQbSyncToken($vendorCustomerId, $dataService)
    {
        $customer = new \IPPCustomer();
        $customer->Id = $vendorCustomerId;
        
        $data = $dataService->FindById($customer);

        return $data->SyncToken;
    }
    
    private function prepareCustomer($customerType, $customerArray, $id = null, $syncToken = null)
    {
        $targetObj = new \IPPCustomer();
        
        if ($id) {
            $targetObj->Id = $id;
            $targetObj->SyncToken = $syncToken;
        }
        if (isset($customerArray['customer_name'])) {
            $targetObj->CompanyName = $customerArray['customer_name'];
        }
        if (isset($customerArray['display_name'])) {
            $targetObj->DisplayName = $customerArray['display_name'];
        }
        if (isset($customerArray['name'])) {
            $targetObj->GivenName = $customerArray['name'];
        }
        if (isset($customerArray['surname'])) {
            $targetObj->FamilyName = $customerArray['surname'];
        }
        
        if (isset($customerArray['address1'])) {
            $billAddr['Line1'] = $customerArray['address1'];
        }
        if (isset($customerArray['address2'])) {
            $billAddr['Line2'] = $customerArray['address2'];
        }
        if (isset($customerArray['city'])) {
            $billAddr['City'] = $customerArray['city'];
        }
        if (isset($customerArray['postcode'])) {
            $billAddr['PostalCode'] = $customerArray['postcode'];
        }
        if (isset($customerArray['country'])) {
            $billAddr['Country'] = $customerArray['country'];
        }
        
        if ($billAddr) {
            $targetObj->BillAddr = new \IPPPhysicalAddress($billAddr);
        }
        
        return $targetObj;
    }
    
    private function prepareReturnArray($confirmationObject)
    {
        return array(	
	    'vendor_customer_id' => $confirmationObject->Id,
            'customer_name' => $confirmationObject->CompanyName,
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

    public function createCustomer($customerArray)
    {
        if (empty($customerArray['display_name']) && empty($customerArray['name']) && empty($customerArray['surname'])) {
            throw new PlugintoException(PlugintoException::MISSING_REQUIRED_FIELD_DISPLAY_NAME_SURNAME_ERROR_MSG, 
                                        PlugintoException::MISSING_REQUIRED_FIELD_DISPLAY_NAME_SURNAME_ERROR_CODE);
        }
        
        $dataService = $this->getDataService($customerArray['user_id']);
        
        $targetObj = $this->prepareCustomer('customer', $customerArray);
        
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
    
    public function updateCustomer(\Application\Entity\Customer $customer, $customerArray)
    {
        $dataService = $this->getDataService($customerArray['user_id']);
        
        $syncToken = $this->getQbSyncToken($customer->getVendorCustomerId(), $dataService);
        $customer->setSyncToken($syncToken);
        
        $targetObj = $this->prepareCustomer('customer', $customerArray, $customer->getVendorCustomerId(), $customer->getSyncToken());
        
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
    
    public function deleteCustomer(\Application\Entity\Customer $customer, $customerArray)
    {
        $dataService = $this->getDataService($customerArray['user_id']);
        
        $targetObj = $this->prepareCustomer('customer', $customerArray, $customer->getVendorCustomerId(), $customer->getSyncToken());
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
    
    /*public function getVendor($customerArray, $startPosition = 1, $maxResults = 10)
    {
        $dataService = $this->getDataService($customerArray['user_id']);
        
        try {
            $data = $dataService->FindAll('Vendor', $startPosition, $maxResults);
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_MSG, 
                                        PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_CODE);
        }    

        return $data;
    }
    
    public function createVendor($customerArray)
    {
        if (empty($customerArray['display_name']) && empty($customerArray['name']) && empty($customerArray['surname'])) {
            throw new PlugintoException(PlugintoException::MISSING_REQUIRED_FIELD_DISPLAY_NAME_SURNAME_ERROR_MSG, 
                                        PlugintoException::MISSING_REQUIRED_FIELD_DISPLAY_NAME_SURNAME_ERROR_CODE);
        }
        
        $dataService = $this->getDataService($customerArray['user_id']);
        
        $targetObj = $this->prepareCustomer('vendor', $customerArray);
        
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
    
    public function updateVendor(\Application\Entity\Customer $customer, $customerArray)
    {
        $dataService = $this->getDataService($customerArray['user_id']);
        
        $targetObj = $this->prepareCustomer('vendor', $customerArray, $customer->getCustomerId(), $customer->getSyncToken());

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
    
    public function deleteVendor(\Application\Entity\Customer $customer, $customerArray)
    {
        $dataService = $this->getDataService($customerArray['user_id']);
        
        $targetObj = $this->prepareCustomer('vendor', $customerArray, $customer->getVendorCustomerId(), $customer->getSyncToken());
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
    }*/
    
    public function dbVendorFieldFilter($dbFields)
    {
        $fields = array('id', 'user_id', 'vendor_customer_id', 'customer_name', 'display_name', 'name', 'surname', 'address1', 'address2', 'city', 'postcode', 'country', 'sync_token');
        
        foreach($dbFields as $key => $value) {
            if (!in_array($key, $fields)) {
                unset($dbFields[$key]);
            }
        }
        
        return $dbFields;
    }
    
}