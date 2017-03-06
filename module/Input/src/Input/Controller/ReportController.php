<?php
namespace Input\Controller;

use Zend\View\Model\JsonModel;
use lib\exception\PlugintoException;
use Zend\Db\Sql\Sql;
        
error_reporting(9);
require_once(__DIR__ . '/../../../../../lib/exception/PlugintoException.php');

class ReportController extends BaseController
{
    public function getList()
    {
        $reportType = $this->getEvent()->getRouteMatch()->getParam('report-type');
        
        $userId = $this->validateUser();
        $accountingVendor = $this->identifyAccountingVendor($userId);
        
        $serviceType = $this->getRequest()->getQuery()->get('service_type', null);
        $customerId = $this->getRequest()->getQuery()->get('customer_id', null);
        $supplierId = $this->getRequest()->getQuery()->get('supplier_id', null);
        $itemId = $this->getRequest()->getQuery()->get('item_id', null);
        $accountId = $this->getRequest()->getQuery()->get('account_id', null);
        
        $model = new \Input\Model\Report($this->getServiceLocator());
        $model->setUserId($userId);
        $model->setUserAccountingVendor($accountingVendor);
        $modelName = "fetch{$reportType}Report";

        if ($reportType == 'Sales') {
            $result['data'] = $model->$modelName($serviceType, $customerId, $supplierId, $itemId, $accountId);
        } else {
            $result['data'] = $model->$modelName();
        }
        
        //return new JsonModel($result);
        return $result;
    }
    
    private function identifyAccountingVendor($userId)
    {
        $userModel = $this->getServiceLocator()->get("UserModel");
        $userArr = $userModel->getEntityById($userId);
        
        return $userArr['accounting_vendor'];
    }
    
}