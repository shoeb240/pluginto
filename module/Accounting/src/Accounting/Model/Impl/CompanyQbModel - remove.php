<?php
namespace Accounting\Model\Impl;

use Accounting\Model\CompanyModel;
use lib\exception\PlugintoException;

class CompanyQbModel extends CompanyModel
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

    public function getCustomer($companyArray, $startPosition = 1, $maxResults = 10)
    {
        $dataService = $this->getDataService($companyArray['user_id']);
        
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
    
    private function prepareCompany($companyType, $companyArray, $id = null, $syncToken = null)
    {
        if ($companyType == 'vendor') {
            $targetObj = new \IPPVendor();
        } else {
            $targetObj = new \IPPCustomer();
        }
        
        if ($id) {
            $targetObj->Id = $id;
            $targetObj->SyncToken = $syncToken;
        }
        if (isset($companyArray['company_name'])) {
            $targetObj->CompanyName = $companyArray['company_name'];
        }
        if (isset($companyArray['display_name'])) {
            $targetObj->DisplayName = $companyArray['display_name'];
        }
        if (isset($companyArray['name'])) {
            $targetObj->GivenName = $companyArray['name'];
        }
        if (isset($companyArray['surname'])) {
            $targetObj->FamilyName = $companyArray['surname'];
        }
        
        if (isset($companyArray['address1'])) {
            $billAddr['Line1'] = $companyArray['address1'];
        }
        if (isset($companyArray['address2'])) {
            $billAddr['Line2'] = $companyArray['address2'];
        }
        if (isset($companyArray['city'])) {
            $billAddr['City'] = $companyArray['city'];
        }
        if (isset($companyArray['postcode'])) {
            $billAddr['PostalCode'] = $companyArray['postcode'];
        }
        if (isset($companyArray['country'])) {
            $billAddr['Country'] = $companyArray['country'];
        }
        
        if ($billAddr) {
            $targetObj->BillAddr = new \IPPPhysicalAddress($billAddr);
        }
        
        return $targetObj;
    }
    
    private function prepareReturnArray($confirmationObject)
    {
        return array(	
	    'vendor_company_id' => $confirmationObject->Id,
            'company_name' => $confirmationObject->CompanyName,
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

    public function createCustomer($companyArray)
    {
        if (empty($companyArray['display_name']) && empty($companyArray['name']) && empty($companyArray['surname'])) {
            throw new PlugintoException(PlugintoException::MISSING_REQUIRED_FIELD_DISPLAY_NAME_SURNAME_ERROR_MSG, 
                                        PlugintoException::MISSING_REQUIRED_FIELD_DISPLAY_NAME_SURNAME_ERROR_CODE);
        }
        
        $dataService = $this->getDataService($companyArray['user_id']);
        
        $targetObj = $this->prepareCompany('customer', $companyArray);
        
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
        
        if (!isset($confirmationObject->Id)) {
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
        
        if (!isset($confirmationObject->Id)) {
            throw new PlugintoException(PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_MSG, 
                                        PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_CODE);
        }

        return true;
    }
    
    public function getVendor($companyArray, $startPosition = 1, $maxResults = 10)
    {
        $dataService = $this->getDataService($companyArray['user_id']);
        
        try {
            $data = $dataService->FindAll('Vendor', $startPosition, $maxResults);
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_MSG, 
                                        PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_CODE);
        }    

        return $data;
    }
    
    public function createVendor($companyArray)
    {
        if (empty($companyArray['display_name']) && empty($companyArray['name']) && empty($companyArray['surname'])) {
            throw new PlugintoException(PlugintoException::MISSING_REQUIRED_FIELD_DISPLAY_NAME_SURNAME_ERROR_MSG, 
                                        PlugintoException::MISSING_REQUIRED_FIELD_DISPLAY_NAME_SURNAME_ERROR_CODE);
        }
        
        $dataService = $this->getDataService($companyArray['user_id']);
        
        $targetObj = $this->prepareCompany('vendor', $companyArray);
        
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
    
    public function updateVendor(\Application\Entity\Company $company, $companyArray)
    {
        $dataService = $this->getDataService($companyArray['user_id']);
        
        $targetObj = $this->prepareCompany('vendor', $companyArray, $company->getCompanyId(), $company->getSyncToken());

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
    
    public function deleteVendor(\Application\Entity\Company $company, $companyArray)
    {
        $dataService = $this->getDataService($companyArray['user_id']);
        
        $targetObj = $this->prepareCompany('vendor', $companyArray, $company->getVendorCompanyId(), $company->getSyncToken());
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
    
}