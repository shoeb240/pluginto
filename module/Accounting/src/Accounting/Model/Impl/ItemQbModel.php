<?php
namespace Accounting\Model\Impl;

use Accounting\Model\ItemModel;
use lib\exception\PlugintoException;

class ItemQbModel extends ItemModel
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

    public function getItem($userId)
    {
        $dataService = $this->getDataService($userId);
        
        try {
            $data = $dataService->FindAll('Item');
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_MSG, 
                                        PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_CODE);
        }    

        return $data;
    }
    
    private function getQbSyncToken($vendorItemId, $dataService)
    {
        $item = new \IPPItem();
        $item->Id = $vendorItemId;
        
        $data = $dataService->FindById($item);

        return $data->SyncToken;
    }
    
    private function prepareItem($itemArray, $id = null, $syncToken = null)
    {
        $targetObj = new \IPPItem();
        
        if ($id) {
            $targetObj->Id = $id;
            $targetObj->SyncToken = $syncToken;
        }
        if (isset($itemArray['item_name'])) {
            $targetObj->Name = $itemArray['item_name'];
        }
        if (isset($itemArray['item_type'])) {
            $targetObj->Type = $itemArray['item_type'];
        }
        if (isset($itemArray['income_account_id'])) {
            $targetObj->IncomeAccountRef = $itemArray['income_account_id'];
        }
        if (isset($itemArray['expense_account_id'])) {
            $targetObj->ExpenseAccountRef = $itemArray['expense_account_id'];
        }
        if (isset($itemArray['price'])) {
            $targetObj->UnitPrice = $itemArray['price'];
        }
        $targetObj->Active = true;
        
        return $targetObj;
    }
    
    private function prepareReturnArray($confirmationObject)
    {
        return array(	
	    'vendor_item_id' => $confirmationObject->Id,
            'item_name' => $confirmationObject->Name,
            'item_type' => $confirmationObject->Type,
            'income_account_id' => $confirmationObject->IncomeAccountRef,
            'expense_account_id' => $confirmationObject->ExpenseAccountRef,
            'price' => $confirmationObject->UnitPrice,
            'sync_token' => $confirmationObject->SyncToken
        );
    }

    public function createItem($itemArray)
    {
        if (empty($itemArray['item_name'])) { // || (empty($itemArray['income_account_id']) && empty($itemArray['expense_account_id']))
            throw new PlugintoException(PlugintoException::MISSING_REQUIRED_FIELD_DISPLAY_NAME_SURNAME_ERROR_MSG, 
                                        PlugintoException::MISSING_REQUIRED_FIELD_DISPLAY_NAME_SURNAME_ERROR_CODE);
        }
        
        $dataService = $this->getDataService($itemArray['user_id']);
        
        $targetObj = $this->prepareItem($itemArray);
        
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
    
    public function updateItem(\Application\Entity\Item $item, $itemArray)
    {
        $dataService = $this->getDataService($itemArray['user_id']);

        $syncToken = $this->getQbSyncToken($item->getVendorItemId(), $dataService);
        $item->setSyncToken($syncToken);
        
        $targetObj = $this->prepareItem($itemArray, $item->getVendorItemId(), $item->getSyncToken());
        
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
    
    public function deleteItem(\Application\Entity\Item $item, $itemArray)
    {
        $dataService = $this->getDataService($itemArray['user_id']);
        
        $targetObj = $this->prepareItem($itemArray, $item->getVendorItemId(), $item->getSyncToken());
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
    
//    private function prepareReturnArrayFromArray($confirmationObject)
//    {
//        return array(	
//	    'vendor_item_id' => $confirmationObject->Id,
//            'name' => $confirmationObject->Name,
//            'item_type' => $confirmationObject->ItemType,
//            'item_sub_type' => $confirmationObject->ItemSubType,
//            'sync_token' => $confirmationObject->SyncToken
//        );
//    }
    
    public function dbVendorFieldFilter($dbFields)
    {
        $fields = array('id','vendor_item_id','user_id','item_name','item_type','income_account_id','expense_account_id','price','sync_token');
        
        foreach($dbFields as $key => $value) {
            if (!in_array($key, $fields)) {
                unset($dbFields[$key]);
            }
        }
        
        return $dbFields;
    }
    
}