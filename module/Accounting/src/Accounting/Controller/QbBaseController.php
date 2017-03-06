<?php
namespace Accounting\Controller;

use Zend\Mvc\Controller\AbstractActionController;

class QbBaseController extends AbstractActionController
{
    public function getCompanyInfo($startPosition = 1, $maxResults = 10)
    {
        $data = array();
        
        if($this->getContainer()->token) {
            $dataService = $this->getDataService();
            $data = $dataService->FindAll('CompanyInfo', $startPosition, $maxResults);
        }

        return $data;
    }
    
    public function getCustomer($startPosition = 1, $maxResults = 10)
    {
        $data = array();
        
        if($this->getContainer()->token) {
            $dataService = $this->getDataService();
            $data = $dataService->FindAll('Customer', $startPosition, $maxResults);
        }

        return $data;
    }
    
    public function getInvoice($startPosition = 1, $maxResults = 10)
    {
        $data = array();
        
        if($this->getContainer()->token) {
            $dataService = $this->getDataService();
            $data = $dataService->FindAll('Invoice', $startPosition, $maxResults);
        }

        return $data;
    }
    
    /*public function createInvoice($transactionData)
    {
        if($this->getContainer()->token) {
            $dataService = $this->getDataService();

            $oneLine = $this->getIPPLine('Services line item ' . $transactionData['account_id'], 
                                         $transactionData['amount']);
            
            $targetObj = $this->getIPPInvoice(array($oneLine, $oneLine), $transactionData['company_id']);
            
            // Create a new Invoice Object
            $invoiceObjConfirmation = $dataService->Add($targetObj);
            
            return $invoiceObjConfirmation;
        }
        
        return false;
    }*/

    public function getAccount($startPosition = 1, $maxResults = 10)
    {
        $data = array();
        
        if($this->getContainer()->token) {
            $dataService = $this->getDataService();
            $data = $dataService->FindAll('Account', $startPosition, $maxResults);
        }

        return $data;
    }
    
//    protected function getDataService()
//    {
//        require_once(__DIR__ . '/../../../../../lib/qb-v3-php-sdk-2.0.5/config.php');  // Default V3 PHP SDK (v2.0.4) from IPP
//        require_once(PATH_SDK_ROOT . 'Core/ServiceContext.php');
//        require_once(PATH_SDK_ROOT . 'DataService/DataService.php');
//        require_once(PATH_SDK_ROOT . 'PlatformService/PlatformService.php');
//        require_once(PATH_SDK_ROOT . 'Utility/Configuration/ConfigurationManager.php');
//
//        // After the oauth process the oauth token and secret 
//        // are storred in session variables.
//        if($this->getContainer()->token) {
//            $token = unserialize($this->getContainer()->token);
//
//            $requestValidator = new \OAuthRequestValidator($token['oauth_token'], 
//                                                           $token['oauth_token_secret'], 
//                                                           $this->getConsumerKey(), 
//                                                           $this->getConsumerSecret());
//
//            $serviceContext = new \ServiceContext($this->getContainer()->realmId, 
//                                                  $this->getContainer()->dataSource, 
//                                                  $requestValidator);
//
//            $dataService = new \DataService($serviceContext);
//            
//            return $dataService;
//        }
//        
//        return false;
//    }
    
    /*protected function getIPPLine($description, $amount)
    {
        // ItemRef(1) = Services
        // check Read method here (use entityId = 1): https://developer.intuit.com/apiexplorer?apiname=V3QBO#Item
        $oneLine = new \IPPLine(array(
            'Description' => $description,
            'Amount' => $amount,
            'DetailType' => 'SalesItemLineDetail',
            'SalesItemLineDetail' =>
                new \IPPSalesItemLineDetail(
                    array('ItemRef' => 
                        new \IPPReferenceType(array('value' => 1)),
                        'DetailType' => 'SalesItemLineDetail'
                    )
                ),
            )
        );
        
        return $oneLine;
    }
    
    protected function getIPPInvoice($lineArray, $customerId)
    {
        $targetObj = new \IPPInvoice();
        $targetObj->Name = 'Some Name '.rand();
        $targetObj->TotalAmt = '10.00';
        $targetObj->CustomerRef = new \IPPReferenceType(array('value' => $customerId)); // CustomerRef = customer id
        $targetObj->Line = $lineArray;
        
        return $targetObj;
    }*/
    
}