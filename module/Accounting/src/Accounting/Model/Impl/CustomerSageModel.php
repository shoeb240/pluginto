<?php
namespace Accounting\Model\Impl;

use Accounting\Model\CustomerModel;
use lib\exception\PlugintoException;

class CustomerSageModel extends CustomerModel
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
        require_once(__DIR__ . '/../../../../../../lib/sage/DataService.php');
        
        $userModel = $this->getServiceLocator()->get("UserModel");
        $userArr = $userModel->getEntityById($id);
        $userPropertyArr = $userArr['property_list'];

        $apiKey = $userPropertyArr['vendor_api_key'];
        $userId = $userArr['user_login'];
        $userPass = $userPropertyArr['vendor_password'];
        $companyId = $userPropertyArr['vendor_company_id'];
        
        $dataService = new \DataService($userId, $userPass, $apiKey, $companyId);
        
        if (!$dataService) {
            throw new PlugintoException(PlugintoException::DATA_SERVICE_RENDER_FAILED_ERROR_MSG, 
                                        PlugintoException::DATA_SERVICE_RENDER_FAILED_ERROR_CODE);
        }

        return $dataService;
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
    
    private function prepareCustomer($customerType, $customerArray, $id = null)
    {
        if ($id) {
            $jsonArr['ID'] = $id;
        }
        
        if (isset($customerArray['customer_name'])) {
            $jsonArr['Name'] = $customerArray['customer_name'];
        }
        if (isset($customerArray['name'])) {
            $jsonArr['ContactName'] = $customerArray['name'];
        }
        if (isset($customerArray['address1'])) {
            $jsonArr['PostalAddress01'] = $customerArray['address1'];
            $jsonArr['DeliveryAddress01'] = $customerArray['address1'];
        }
        if (isset($customerArray['address2'])) {
            $jsonArr['PostalAddress02'] = $customerArray['address2'];
            $jsonArr['DeliveryAddress02'] = $customerArray['address2'];
        }
        if (isset($customerArray['city'])) {
            $jsonArr['PostalAddress03'] = $customerArray['city'];
            $jsonArr['DeliveryAddress03'] = $customerArray['city'];
        }
        if (isset($customerArray['postcode'])) {
            $jsonArr['PostalAddress04'] = $customerArray['postcode'];
            $jsonArr['DeliveryAddress04'] = $customerArray['postcode'];
        }
        if (isset($customerArray['country'])) {
            $jsonArr['PostalAddress05'] = $customerArray['country'];
            $jsonArr['DeliveryAddress05'] = $customerArray['country'];
        }
        
        $jsonArr['CommunicationMethod'] = 0;
        $jsonArr['Active'] = true;
        
        return json_encode($jsonArr);
    }
    
    public function prepareReturnArray($confirmationObject)
    {
        return array(	
	    'vendor_customer_id' => $confirmationObject->ID,
            'customer_name' => $confirmationObject->Name,
            'name' => $confirmationObject->ContactName,
            'address1' => $confirmationObject->PostalAddress01,
            'address2' => $confirmationObject->PostalAddress02,
            'city' => $confirmationObject->PostalAddress03,
            'postcode' => $confirmationObject->PostalAddress04,
            'country' => $confirmationObject->PostalAddress05,
            //'print_on_check_name' => $confirmationObject->PrintOnCheckName,
            //'status' => $confirmationObject->Active,
            //'tax_status' => $confirmationObject->Taxable,
            //'balance' => $confirmationObject->Balance,
            //'preferred_delivery_method' => $confirmationObject->PreferredDeliveryMethod,
            //'create_time' => $confirmationObject->MetaData->CreateTime,
            //'update_time' => $confirmationObject->MetaData->LastUpdatedTime,
        );
    }
    
    public function createCustomer($customerArray)
    {
        if (empty($customerArray['customer_name'])) {
            throw new PlugintoException(PlugintoException::MISSING_REQUIRED_FIELD_DISPLAY_NAME_SURNAME_ERROR_MSG, 
                                        PlugintoException::MISSING_REQUIRED_FIELD_DISPLAY_NAME_SURNAME_ERROR_CODE);
        }
        
        $dataService = $this->getDataService($customerArray['user_id']);
        
        $targetJsonObj = $this->prepareCustomer('customer', $customerArray);
        print_r($targetJsonObj);
        try {
            $confirmationObject = $dataService->Add('Customer', $targetJsonObj);
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_MSG . ' - ' . $e->getMessage(), 
                                        PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_CODE);
        }
        
        if (!isset($confirmationObject->ID)) { // New Sage changed
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
        
        if (!isset($confirmationObject->ID)) {
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
        
        if (!isset($confirmationObject->ID)) {
            throw new PlugintoException(PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_MSG, 
                                        PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_CODE);
        }

        return true;
    }
    
    public function dbVendorFieldFilter($dbFields)
    {
        $fields = array('id', 'user_id', 'vendor_customer_id', 'customer_name', 'name', 'address1', 'address2', 'city', 'postcode', 'country');
        
        foreach($dbFields as $key => $value) {
            if (!in_array($key, $fields)) {
                unset($dbFields[$key]);
            }
        }
        
        return $dbFields;
    }
    
}