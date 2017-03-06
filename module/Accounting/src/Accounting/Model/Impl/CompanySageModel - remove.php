<?php
namespace Accounting\Model\Impl;

use Accounting\Model\CompanyModel;
use lib\exception\PlugintoException;

class CompanySageModel extends CompanyModel
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

        $apiKey = '{28FBCB45-262C-4F55-9035-CCD4847AB4BF}'; //$userPropertyArr['api_key']; 
        $userId = 'shoeb240@gmail.com';
        $userPass = 'Shoeb123#';
        $companyId = '112320';
        //$customerId = '';
        
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

    public function getCustomer($companyArray, $startPosition = 1, $maxResults = 10)
    {
        $dataService = $this->getDataService($companyArray['user_id']);
        $dataService->setCompanyId(112320); // New Sage
        try {
            $data = $dataService->FindAll('Customer', $startPosition, $maxResults);
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_MSG, 
                                        PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_CODE);
        }    

        return $data;
    }
    
    private function getQbSyncToken($vendorCompanyId, $dataService)
    {
        $company = new \IPPCustomer();
        $company->Id = $vendorCompanyId;
        
        $data = $dataService->FindById($company);

        return $data->SyncToken;
    }
    
    private function prepareCompany($companyType, $companyArray, $id = null)
    {
        if ($id) {
            $jsonArr['ID'] = $id;
        }
        
        if (isset($companyArray['company_name'])) {
            $jsonArr['Name'] = $companyArray['company_name'];
        }
        if (isset($companyArray['name'])) {
            $jsonArr['ContactName'] = $companyArray['name'];
        }
        if (isset($companyArray['address1'])) {
            $jsonArr['PostalAddress01'] = $companyArray['address1'];
            $jsonArr['DeliveryAddress01'] = $companyArray['address1'];
        }
        if (isset($companyArray['address2'])) {
            $jsonArr['PostalAddress02'] = $companyArray['address2'];
            $jsonArr['DeliveryAddress02'] = $companyArray['address2'];
        }
        if (isset($companyArray['city'])) {
            $jsonArr['PostalAddress03'] = $companyArray['city'];
            $jsonArr['DeliveryAddress03'] = $companyArray['city'];
        }
        if (isset($companyArray['postcode'])) {
            $jsonArr['PostalAddress04'] = $companyArray['postcode'];
            $jsonArr['DeliveryAddress04'] = $companyArray['postcode'];
        }
        if (isset($companyArray['country'])) {
            $jsonArr['PostalAddress05'] = $companyArray['country'];
            $jsonArr['DeliveryAddress05'] = $companyArray['country'];
        }
        
        $jsonArr['CommunicationMethod'] = 0;
        
        return json_encode($jsonArr);
    }
    
    public function prepareReturnArray($confirmationObject)
    {
        return array(	
	    'vendor_company_id' => $confirmationObject->ID,
            'company_name' => $confirmationObject->Name,
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
    
    public function createCustomer($companyArray)
    {
        if (empty($companyArray['company_name'])) {
            throw new PlugintoException(PlugintoException::MISSING_REQUIRED_FIELD_DISPLAY_NAME_SURNAME_ERROR_MSG, 
                                        PlugintoException::MISSING_REQUIRED_FIELD_DISPLAY_NAME_SURNAME_ERROR_CODE);
        }
        
        $dataService = $this->getDataService($companyArray['user_id']);
        $dataService->setCompanyId(112320); // New Sage
        
        $targetJsonObj = $this->prepareCompany('customer', $companyArray);
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
    
    public function updateCustomer(\Application\Entity\Company $company, $companyArray)
    {
        $dataService = $this->getDataService($companyArray['user_id']);
        
        $syncToken = $this->getQbSyncToken($company->getVendorCompanyId(), $dataService);
        $company->setSyncToken($syncToken);
        
        $targetObj = $this->prepareCompany('customer', $companyArray, $company->getVendorCompanyId(), $company->getSyncToken());
        
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
    
    public function deleteCustomer(\Application\Entity\Company $company, $companyArray)
    {
        $dataService = $this->getDataService($companyArray['user_id']);
        
        $targetObj = $this->prepareCompany('customer', $companyArray, $company->getVendorCompanyId(), $company->getSyncToken());
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
        $fields = array('id', 'user_id', 'vendor_company_id', 'company_name', 'name', 'address1', 'address2', 'city', 'postcode', 'country');
        
        foreach($dbFields as $key => $value) {
            if (!in_array($key, $fields)) {
                unset($dbFields[$key]);
            }
        }
        
        return $dbFields;
    }
    
}