<?php
namespace Accounting\Model\Impl;

use Accounting\Model\TaxAgencyModel;
use lib\exception\PlugintoException;

class TaxAgencyQbModel extends TaxAgencyModel
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

    public function getTaxAgency($userId, $startPosition = 1, $maxResults = 10)
    {
        $dataService = $this->getDataService($userId);
        
        try {
            $data = $dataService->FindAll('TaxAgency', $startPosition, $maxResults);
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_MSG, 
                                        PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_CODE);
        }    

        return $data;
    }
    
    private function prepareTaxAgency($taxAgencyArray, $id = null, $syncToken = null)
    {
        $targetObj = new \IPPTaxAgency();
        
        if ($id) {
            $targetObj->Id = $id;
            $targetObj->SyncToken = $syncToken;
        }
        if (isset($taxAgencyArray['display_name'])) {
            $targetObj->DisplayName = $taxAgencyArray['display_name'];
        }
        
        $targetObj->domain = 'QBO';
        $targetObj->sparse = false;
        
        return $targetObj;
    }
    
    private function prepareReturnArray($confirmationObject)
    {
        return array(	
	    'tax_agency_id' => $confirmationObject->Id,
            'display_name' => $confirmationObject->DisplayName,
            'sync_token' => $confirmationObject->SyncToken
        );
    }

    public function createTaxAgency($taxAgencyArray)
    {
        $dataService = $this->getDataService($taxAgencyArray['user_id']);
        
        if (empty($taxAgencyArray['display_name'])) {
            throw new PlugintoException(PlugintoException::MISSING_REQUIRED_FIELD_DISPLAY_NAME_SURNAME_ERROR_MSG, 
                                            PlugintoException::MISSING_REQUIRED_FIELD_DISPLAY_NAME_SURNAME_ERROR_CODE);
        }

        $targetObj = $this->prepareTaxAgency($taxAgencyArray);
        
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
    
}