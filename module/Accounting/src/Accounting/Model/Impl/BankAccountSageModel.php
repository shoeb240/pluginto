<?php
namespace Accounting\Model\Impl;

use Accounting\Model\BankAccountModel;
use lib\exception\PlugintoException;

class BankAccountSageModel extends BankAccountModel
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

    public function getBankAccount($userId)
    {
        $dataService = $this->getDataService($userId);
        try {
            $data = $dataService->FindAll('BankAccount');
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_MSG, 
                                        PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_CODE);
        }    

        return $data;
    }
    
    private function getQbSyncToken($vendorBankAccountId, $dataService)
    {
        $account = new \IPPBankAccount();
        $account->Id = $vendorBankAccountId;
        
        $data = $dataService->FindById($account);

        return $data->SyncToken;
    }
    
    private function prepareBankAccount($accountArray, $id = null) // 3807458
    {
        if ($id) {
            $jsonArr['ID'] = $id;
        }
        if (isset($accountArray['account_name'])) {
            $jsonArr['Name'] = $accountArray['account_name'];
        }
        if (isset($accountArray['account_number'])) {
            $jsonArr['AccountNumber'] = $accountArray['account_number'];
        }
        if (isset($accountArray['bank_name'])) {
            $jsonArr['BankName'] = $accountArray['bank_name'];
        }
        if (isset($accountArray['branch_name'])) {
            $jsonArr['BranchName'] = $accountArray['branch_name'];
        }
        $jsonArr['Active'] = true;
        $jsonArr['Balance'] = 0;
        
        return json_encode($jsonArr);
    }
    
    public function prepareReturnArray($confirmationObject)
    {
        return array(	
	    'vendor_bank_account_id' => $confirmationObject->ID,
            'account_name' => $confirmationObject->Name,
            'bank_name' => $confirmationObject->BankName,
            'account_number' => $confirmationObject->AccountNumber,
            'branch_name' => $confirmationObject->BranchName
        );
    }

    public function createBankAccount($accountArray)
    {
        if (empty($accountArray['account_name'])) {
            throw new PlugintoException(PlugintoException::MISSING_REQUIRED_FIELD_DISPLAY_NAME_SURNAME_ERROR_MSG, 
                                        PlugintoException::MISSING_REQUIRED_FIELD_DISPLAY_NAME_SURNAME_ERROR_CODE);
        }
        
        $dataService = $this->getDataService($accountArray['user_id']);
        
        $targetObj = $this->prepareBankAccount($accountArray);
        try {
            $confirmationObject = $dataService->Add('BankAccount', $targetObj);
            echo '<pre>';
            print_r($confirmationObject);
            echo '</pre>';
            //die();
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
    
    public function updateBankAccount(\Application\Entity\BankAccount $account, $accountArray)
    {
        $dataService = $this->getDataService($accountArray['user_id']);
        
        $targetObj = $this->prepareBankAccount($accountArray, $account->getVendorBankAccountId());
        
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
    
    public function deleteBankAccount(\Application\Entity\BankAccount $account, $accountArray)
    {
        $dataService = $this->getDataService($accountArray['user_id']);
        
        $targetObj = $this->prepareBankAccount($accountArray, $account->getVendorBankAccountId(), $account->getSyncToken());
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
	    'vendor_bank_account_id' => $confirmationObject->ID,
            'account_name' => $confirmationObject->Name,
            'account_number' => $confirmationObject->AccountNumber,
            'bank_name' => $confirmationObject->BankName,
            'branch_name' => $confirmationObject->BranchName
        );
    }
    
    public function dbVendorFieldFilter($dbFields)
    {
        $fields = array('id', 'vendor_bank_account_id', 'user_id', 'account_name', 'bank_name', 'account_number', 'branch_name');
        
        foreach($dbFields as $key => $value) {
            if (!in_array($key, $fields)) {
                unset($dbFields[$key]);
            }
        }
        
        return $dbFields;
    }
    
}