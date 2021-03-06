<?php
namespace Accounting\Model\Impl;

use Accounting\Model\SupplierModel;
use lib\exception\PlugintoException;

class SupplierSageModel extends SupplierModel
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
    
    private function prepareSupplier($supplierType, $supplierArray, $id = null)
    {
        if ($id) {
            $jsonArr['ID'] = $id;
        }
        
        if (isset($supplierArray['supplier_name'])) {
            $jsonArr['Name'] = $supplierArray['supplier_name'];
        }
        if (isset($supplierArray['name'])) {
            $jsonArr['ContactName'] = $supplierArray['name'];
        }
        if (isset($supplierArray['address1'])) {
            $jsonArr['PostalAddress01'] = $supplierArray['address1'];
            $jsonArr['DeliveryAddress01'] = $supplierArray['address1'];
        }
        if (isset($supplierArray['address2'])) {
            $jsonArr['PostalAddress02'] = $supplierArray['address2'];
            $jsonArr['DeliveryAddress02'] = $supplierArray['address2'];
        }
        if (isset($supplierArray['city'])) {
            $jsonArr['PostalAddress03'] = $supplierArray['city'];
            $jsonArr['DeliveryAddress03'] = $supplierArray['city'];
        }
        if (isset($supplierArray['postcode'])) {
            $jsonArr['PostalAddress04'] = $supplierArray['postcode'];
            $jsonArr['DeliveryAddress04'] = $supplierArray['postcode'];
        }
        if (isset($supplierArray['country'])) {
            $jsonArr['PostalAddress05'] = $supplierArray['country'];
            $jsonArr['DeliveryAddress05'] = $supplierArray['country'];
        }
        
        $jsonArr['CommunicationMethod'] = 0;
        
        return json_encode($jsonArr);
    }
    
    public function prepareReturnArray($confirmationObject)
    {
        return array(	
	    'vendor_supplier_id' => $confirmationObject->ID,
            'supplier_name' => $confirmationObject->Name,
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
    
    public function getSupplier($supplierArray, $startPosition = 1, $maxResults = 10)
    {
        $dataService = $this->getDataService($supplierArray['user_id']);
        try {
            $data = $dataService->FindAll('Supplier', $startPosition, $maxResults);
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_MSG, 
                                        PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_CODE);
        }    

        return $data;
    }
    
    public function createSupplier($supplierArray)
    {
//        if (empty($supplierArray['supplier_name'])) {
//            throw new PlugintoException(PlugintoException::MISSING_REQUIRED_FIELD_DISPLAY_NAME_SURNAME_ERROR_MSG, 
//                                        PlugintoException::MISSING_REQUIRED_FIELD_DISPLAY_NAME_SURNAME_ERROR_CODE);
//        }
        
        $dataService = $this->getDataService($supplierArray['user_id']);
        
        $targetJsonObj = $this->prepareSupplier('supplier', $supplierArray);
        print_r($targetJsonObj);//die();
        try {
            $confirmationObject = $dataService->Add('Supplier', $targetJsonObj);
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_MSG . ' - ' . $e->getMessage(), 
                                        PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_CODE);
        }
        
        if (!isset($confirmationObject->ID)) { // New Sage cganged
            throw new PlugintoException(PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_MSG, 
                                        PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_CODE);
        }

        return $this->prepareReturnArray($confirmationObject);
    }
    
    public function updateSupplier(\Application\Entity\Supplier $supplier, $supplierArray)
    {
        $dataService = $this->getDataService($supplierArray['user_id']);
        
        $targetObj = $this->prepareSupplier('vendor', $supplierArray, $supplier->getSupplierId(), $supplier->getSyncToken());

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
    
    public function deleteSupplier(\Application\Entity\Supplier $supplier, $supplierArray)
    {
        $dataService = $this->getDataService($supplierArray['user_id']);
        
        $targetObj = $this->prepareSupplier('vendor', $supplierArray, $supplier->getVendorSupplierId(), $supplier->getSyncToken());
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
        $fields = array('id', 'vendor_supplier_id', 'user_id', 'supplier_name', 'name', 'address1', 'address2', 'city', 'postcode', 'country');
        
        foreach($dbFields as $key => $value) {
            if (!in_array($key, $fields)) {
                unset($dbFields[$key]);
            }
        }
        
        return $dbFields;
    }
    
}