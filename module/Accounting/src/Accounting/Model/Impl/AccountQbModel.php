<?php
namespace Accounting\Model\Impl;

use Accounting\Model\AccountModel;
use lib\exception\PlugintoException;

class AccountQbModel extends AccountModel
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

    public function getAccount($userId)
    {
        $dataService = $this->getDataService($userId);
        
        try {
            $data = $dataService->FindAll('Account');
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_MSG, 
                                        PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_CODE);
        }    

        return $data;
    }
    
    private function getQbSyncToken($vendorAccountId, $dataService)
    {
        $account = new \IPPAccount();
        $account->Id = $vendorAccountId;
        
        $data = $dataService->FindById($account);

        return $data->SyncToken;
    }
    
    private function prepareAccount($accountArray, $id = null, $syncToken = null)
    {
        $targetObj = new \IPPAccount();
        
        if ($id) {
            $targetObj->Id = $id;
            $targetObj->SyncToken = $syncToken;
        }
        if (isset($accountArray['name'])) {
            $targetObj->Name = $accountArray['name'];
        }
        if (isset($accountArray['account_type'])) {
            $targetObj->AccountType = $accountArray['account_type'];
        }
        if (isset($accountArray['account_sub_type'])) {
            $targetObj->AccountSubType = $accountArray['account_sub_type'];
        }
        
        return $targetObj;
    }
    
    private function prepareReturnArray($confirmationObject)
    {
        return array(	
	    'vendor_account_id' => $confirmationObject->Id,
            'name' => $confirmationObject->Name,
            'account_type' => $confirmationObject->AccountType,
            'account_sub_type' => $confirmationObject->AccountSubType,
            'sync_token' => $confirmationObject->SyncToken
        );
    }

    public function createAccount($accountArray)
    {
        if (empty($accountArray['name']) || (empty($accountArray['account_type']) && empty($accountArray['account_sub_type']))) {
            throw new PlugintoException(PlugintoException::MISSING_REQUIRED_FIELD_DISPLAY_NAME_SURNAME_ERROR_MSG, 
                                        PlugintoException::MISSING_REQUIRED_FIELD_DISPLAY_NAME_SURNAME_ERROR_CODE);
        }
        
        $dataService = $this->getDataService($accountArray['user_id']);
        
        $targetObj = $this->prepareAccount($accountArray);
        
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
    
    public function updateAccount(\Application\Entity\Account $account, $accountArray)
    {
        $dataService = $this->getDataService($accountArray['user_id']);

        $syncToken = $this->getQbSyncToken($account->getVendorAccountId(), $dataService);
        $account->setSyncToken($syncToken);
        
        $targetObj = $this->prepareAccount($accountArray, $account->getVendorAccountId(), $account->getSyncToken());
        
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
    
    public function deleteAccount(\Application\Entity\Account $account, $accountArray)
    {
        $dataService = $this->getDataService($accountArray['user_id']);
        
        $targetObj = $this->prepareAccount($accountArray, $account->getVendorAccountId(), $account->getSyncToken());
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
    
    private function prepareReturnArrayFromArray($confirmationObject)
    {
        return array(	
	    'vendor_account_id' => $confirmationObject->Id,
            'name' => $confirmationObject->Name,
            'account_type' => $confirmationObject->AccountType,
            'account_sub_type' => $confirmationObject->AccountSubType,
            'sync_token' => $confirmationObject->SyncToken
        );
    }
    
    public function dbVendorFieldFilter($dbFields)
    {
        $fields = array('id','vendor_account_id','user_id','name','account_type','account_sub_type','sync_token');
        
        foreach($dbFields as $key => $value) {
            if (!in_array($key, $fields)) {
                unset($dbFields[$key]);
            }
        }
        
        return $dbFields;
    }
    
}