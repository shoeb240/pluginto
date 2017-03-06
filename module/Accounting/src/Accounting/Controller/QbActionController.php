<?php
namespace Accounting\Controller;

class QbActionController extends QbBaseController
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
    
    public function createInvoice($transactionData)
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
    }

    public function getAccount($startPosition = 1, $maxResults = 10)
    {
        $data = array();
        
        if($this->getContainer()->token) {
            $dataService = $this->getDataService();
            $data = $dataService->FindAll('Account', $startPosition, $maxResults);
        }

        return $data;
    }
    
}