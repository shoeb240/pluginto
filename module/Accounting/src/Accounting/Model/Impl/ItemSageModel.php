<?php
namespace Accounting\Model\Impl;

use Accounting\Model\ItemModel;
use lib\exception\PlugintoException;

class ItemSageModel extends ItemModel
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

    public function getItem($itemArray, $startPosition = 1, $maxResults = 10)
    { 
        $dataService = $this->getDataService($itemArray['user_id']);
        try {
            $data = $dataService->FindAll('Item', $startPosition, $maxResults);
//            echo '<pre>';
//            print_r($data);
//            echo '</pre>';
//            die();
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
    
    private function prepareItem($itemType, $itemArray, $id = null)
    {
        if ($id) {
            $jsonArr['ID'] = $id;
        }
        
        if (isset($itemArray['item_name'])) {
            $jsonArr['Code'] = $itemArray['item_name'];
        }
        if (isset($itemArray['price'])) {
            $jsonArr['PriceInclusive'] = $itemArray['price'];
        }
        if (isset($itemArray['description'])) {
            $jsonArr['Description'] = $itemArray['description'];
        }
        
        $jsonArr['Active'] = true;
        
        return json_encode($jsonArr);
    }
    
    public function prepareReturnArray($confirmationObject)
    {
        return array(	
	    'vendor_item_id' => $confirmationObject->ID,
            //'category_id' => $confirmationObject->PostalAddress01,
            'item_name' => $confirmationObject->Code,
            'description' => $confirmationObject->Description,
            'price' => $confirmationObject->PriceInclusive
        );
    }
    
    public function createItem($itemArray)
    {
        if (empty($itemArray['item_name'])) {
            throw new PlugintoException(PlugintoException::MISSING_REQUIRED_FIELD_DISPLAY_NAME_SURNAME_ERROR_MSG, 
                                        PlugintoException::MISSING_REQUIRED_FIELD_DISPLAY_NAME_SURNAME_ERROR_CODE);
        }
        
        $dataService = $this->getDataService($itemArray['user_id']);
        
        $targetJsonObj = $this->prepareItem('item', $itemArray);
        print_r($targetJsonObj);
        try {
            $confirmationObject = $dataService->Add('Item', $targetJsonObj);
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
    
    public function updateItem(\Application\Entity\Item $item, $itemArray)
    {
        $dataService = $this->getDataService($itemArray['user_id']);
        
        $syncToken = $this->getQbSyncToken($item->getVendorItemId(), $dataService);
        $item->setSyncToken($syncToken);
        
        $targetObj = $this->prepareItem('item', $itemArray, $item->getVendorItemId(), $item->getSyncToken());
        
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
    
    public function deleteItem(\Application\Entity\Item $item, $itemArray)
    {
        $dataService = $this->getDataService($itemArray['user_id']);
        
        $targetObj = $this->prepareItem('item', $itemArray, $item->getVendorItemId(), $item->getSyncToken());
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
        $fields = array('id', 'vendor_item_id', 'user_id', 'category_id', 'item_name', 'description', 'price');
        
        foreach($dbFields as $key => $value) {
            if (!in_array($key, $fields)) {
                unset($dbFields[$key]);
            }
        }
        
        return $dbFields;
    }
    
}