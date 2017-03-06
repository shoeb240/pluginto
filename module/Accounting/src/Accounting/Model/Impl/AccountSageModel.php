<?php
namespace Accounting\Model\Impl;

use Accounting\Model\AccountModel;
use lib\exception\PlugintoException;

class AccountSageModel extends AccountModel
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
    
    private function prepareAccount($accountArray, $id = null) // 3807458
    {
        if ($id) {
            $jsonArr['ID'] = $id;
        }
        if (isset($accountArray['name'])) {
            $jsonArr['Name'] = $accountArray['name'];
        }
        if (!empty($accountArray['category_id'])) {
            $jsonArr['Category']['ID'] = $accountArray['category_id'];
        }
        if (!empty($accountArray['category_description'])) {
            $jsonArr['Category']['Description'] = $accountArray['category_description'];
        }
        $jsonArr['Active'] = true;
        $jsonArr['Balance'] = 0;
        if (isset($accountArray['description'])) {
            $jsonArr['Description'] = $accountArray['description'];
        }
        $jsonArr['UnallocatedAccount'] = '';
        
        return json_encode($jsonArr);
    }
    
    public function prepareReturnArray($confirmationObject)
    {
        return array(	
	    'vendor_account_id' => $confirmationObject->ID,
            'name' => $confirmationObject->Name,
            'category_id' => $confirmationObject->Category->ID,
            'category_description' => $confirmationObject->Category->Description,
            'description' => $confirmationObject->Description
        );
    }

    public function createAccount($accountArray)
    {
        if (empty($accountArray['name']) || empty($accountArray['category_id'])) {
            throw new PlugintoException(PlugintoException::MISSING_REQUIRED_FIELD_DISPLAY_NAME_SURNAME_ERROR_MSG, 
                                        PlugintoException::MISSING_REQUIRED_FIELD_DISPLAY_NAME_SURNAME_ERROR_CODE);
        }
        
        $dataService = $this->getDataService($accountArray['user_id']);
        
        $targetObj = $this->prepareAccount($accountArray);
        try {
            $confirmationObject = $dataService->Add('Account', $targetObj);
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
    
    public function updateAccount(\Application\Entity\Account $account, $accountArray)
    {
        $dataService = $this->getDataService($accountArray['user_id']);
        
        $targetObj = $this->prepareAccount($accountArray, $account->getVendorAccountId());
        echo '<pre>';
        print_r($targetObj);
        echo '</pre>';
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
    
    public function dbVendorFieldFilter($dbFields)
    {
        $fields = array('id','vendor_account_id','user_id','name','category_id','category_description','description');
        
        foreach($dbFields as $key => $value) {
            if (!in_array($key, $fields)) {
                unset($dbFields[$key]);
            }
        }
        
        return $dbFields;
    }
    
//    private function prepareReturnArrayFromArray($confirmationObject)
//    {
//        return array(	
//	    'vendor_account_id' => $confirmationObject->Id,
//            'name' => $confirmationObject->Name,
//            'category_id' => $confirmationObject->AccountType,
//            'account_sub_type' => $confirmationObject->AccountSubType,
//            'sync_token' => $confirmationObject->SyncToken
//        );
//    }
    
}