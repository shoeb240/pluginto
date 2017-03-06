<?php
namespace Accounting\Model\Impl;

use Accounting\Model\ReportModel;
use lib\exception\PlugintoException;

class ReportSageModel extends ReportModel
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

    public function getReport($userId, $reportName)
    {
        $reportNameArr = array('BalanceSheet' => 'ComparativeStatementOfAssetsAndLiabilities',
                               'ProfitAndLoss' => 'ComparativeProfitAndLossStatement');
        
        $dataService = $this->getDataService($userId);

        $targetObj = $this->prepareReport();
        try {
            $data = $dataService->FindReport($reportNameArr[$reportName], $targetObj);
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_MSG . ' - ' . $e->getMessage(), 
                                        PlugintoException::ACCOUNTING_VENDOR_FAILED_ERROR_CODE);
        }    

        return json_encode($data);
    }
    
    private function prepareReport() // 3807458
    {
        $jsonArr['UsePurchases'] = true;
        $jsonArr['PeriodGroupingLength'] = 2;
        $jsonArr['Comparative'] = true;
        //$jsonArr['BudgetId'] = 1;
        $jsonArr['ShowVariance'] = true;
        $jsonArr['FromDate'] = "2014-06-23";
        $jsonArr['ToDate'] = "2015-06-23";
        
        return json_encode($jsonArr);
    }
    
}