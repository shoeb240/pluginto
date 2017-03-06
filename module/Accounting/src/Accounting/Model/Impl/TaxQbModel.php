<?php
namespace Accounting\Model\Impl;

use Accounting\Model\TaxModel;
use lib\exception\PlugintoException;

class TaxQbModel extends TaxModel
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

    public function getTax($taxArray)
    {
        $dataService = $this->getDataService($taxArray['user_id']);
        
        try {
            $data = $dataService->FindAll('TaxCode');
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_MSG, 
                                        PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_CODE);
        }    

        return $data;
    }
    
    public function getTaxRate($taxRateArray)
    {
        $dataService = $this->getDataService($taxRateArray['user_id']);
        
        try {
            $data = $dataService->FindAll('TaxRate');
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_MSG, 
                                        PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_CODE);
        }    

        return $data;
    }
    
    /*private function prepareTax($taxArray, $id = null, $syncToken = null)
    {
        $targetObj = new \IPPTaxCode();
        
        if ($id) {
            $targetObj->Id = $id;
            $targetObj->SyncToken = $syncToken;
        }
        if (isset($taxArray['tax_code'])) {
            $targetObj->Name = $taxArray['tax_code'];
        }
        
        return $targetObj;
    }*/
    
    /*private function prepareTax($taxArray, $id = null, $syncToken = null)
    {
        $targetObj = array();
        
        if ($id) {
            $targetObj['Id'] = $id;
            $targetObj['SyncToken'] = $syncToken;
        }
        if (isset($taxArray['tax_code'])) {
            $targetObj['Name'] = $taxArray['tax_code'];
        }
        
        return $targetObj;
    }*/
    
    private function prepareTaxArray($taxArray, $id = null, $syncToken = null)
    {
        $targetObj = array();
        
        if (isset($taxArray['tax_code'])) {
            $targetObj['TaxCode'] = $taxArray['tax_code'];
        }
        
        for($i = 0; $i < count($taxArray['tax_rate_id']); $i++) {
            $rateDetail = array();
            if (!empty($taxArray['tax_rate_id'][$i])) {
                $rateDetail['TaxRateId'] = $taxArray['tax_rate_id'][$i];
                $targetObj['TaxRateDetails'][] = $rateDetail;
            }
        }
        
        for($i = 0; $i < count($taxArray['tax_rate_name']); $i++) {
            $rateDetail = array();
            if (!empty($taxArray['tax_rate_name'][$i]) 
                && !empty($taxArray['rate_value'][$i]) 
                && !empty($taxArray['tax_agency_id'][$i])
            ) {
                $rateDetail['TaxRateName'] = $taxArray['tax_rate_name'][$i];
                $rateDetail['RateValue'] = $taxArray['rate_value'][$i];
                $rateDetail['TaxAgencyId'] = $taxArray['tax_agency_id'][$i];
                if (isset($taxArray['tax_applicable_on'][$i])) {
                    $rateDetail['TaxApplicableOn'] = $taxArray['tax_applicable_on'][$i];
                }
                $targetObj['TaxRateDetails'][] = $rateDetail;
            }
        }
        
        return $targetObj;
    }
    
    private function prepareReturnArray($confirmationObject, $targetObj)
    {
        $return['tax_code'] = $confirmationObject->TaxCode;
        $return['tax_code_id'] = $confirmationObject->TaxCodeId;
        $return['tax_rate_list'] = array();
        foreach($confirmationObject->TaxRateDetails as $taxRateDetails)
        {
            $existingRate = false;
            foreach($targetObj['TaxRateDetails'] as $targetObjTaxRateDetails) {
                if (!empty($targetObjTaxRateDetails['TaxRateId']) && $targetObjTaxRateDetails['TaxRateId'] == $taxRateDetails->TaxRateId) {
                    $existingRate = true;
                    break;
                } 
            }
            
            if ($existingRate) {
                $return['tax_rate_list'][] = array(	
                    'tax_rate_id' => $taxRateDetails->TaxRateId,
                );
            } else {
                $return['tax_rate_list'][] = array(	
                    'tax_rate_name' => $taxRateDetails->TaxRateName,
                    'tax_rate_id' => $taxRateDetails->TaxRateId,
                    'rate_value' => $taxRateDetails->RateValue,
                    'tax_agency_id' => $taxRateDetails->TaxAgencyId,
                    'tax_applicable_on' => $taxRateDetails->TaxApplicableOn,
                );
            }
        }
        
        return $return;
    }

    public function createTax($taxArray)
    {
        $dataService = $this->getDataService($taxArray['user_id']);
        
        if (empty($taxArray['tax_code']) || ((empty($taxArray['tax_rate_name']) || empty($taxArray['rate_value']) || empty($taxArray['tax_agency_id'])) 
                                             && empty($taxArray['tax_rate_id']))
        ) {
            throw new PlugintoException(PlugintoException::MISSING_REQUIRED_FIELD_DISPLAY_NAME_SURNAME_ERROR_MSG, 
                                        PlugintoException::MISSING_REQUIRED_FIELD_DISPLAY_NAME_SURNAME_ERROR_CODE);
        }

        $targetObj = $this->prepareTaxArray($taxArray);
        
        try {
            $confirmationObject = $dataService->AddTaxService($targetObj);
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_MSG . ' - ' . $e->getMessage(), 
                                        PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_CODE);
        }
        
        if (!isset($confirmationObject->TaxCodeId)) {
            throw new PlugintoException(PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_MSG, 
                                        PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_CODE);
        }
        
        return $this->prepareReturnArray($confirmationObject, $targetObj);
    }
    
    public function deleteTax(\Application\Entity\TaxCode $tax, $taxArray)
    {
        /*$dataService = $this->getDataService($taxArray['user_id']);
        
        $targetObj = $this->prepareTax($taxArray, $tax->getTaxCodeId(), 0);
        $targetObj['Active'] = 'false';
        
        try {
            $confirmationObject = $dataService->UpdateTax($targetObj);
            echo '<pre>';
            print_r($confirmationObject);
            echo '</pre>';
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_MSG . ' - ' . $e->getMessage(), 
                                        PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_CODE);
        }
        
        if (!isset($confirmationObject->Id)) {
            throw new PlugintoException(PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_MSG, 
                                        PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_CODE);
        }

        return true;*/
    }
    
}