<?php
namespace Apiclient\Controller;

use Apiclient\Controller\ApiclientActionController;
use Zend\View\Model\ViewModel;
use Zend\Http\Request;

error_reporting(0);

class ReportController extends ApiclientActionController
{
    private function getAccountingVendor()
    {
        $userInfo = $this->getObjectManager()->find('\Application\Entity\User', $this->getUserId());
        return strtolower($userInfo->getAccountingVendor());
    }
    
    public function salesReportAction()
    {
        $request    = new Request();
        $request->setMethod(Request::METHOD_GET);
        
        $filterData = array();
        if ($this->request->isPost()) {
            $filterData['service_type'] = $this->getRequest()->getPost('service_type');
            $filterData['customer_id'] = $this->getRequest()->getPost('customer_id');
            $filterData['supplier_id'] = $this->getRequest()->getPost('supplier_id');
            $filterData['item_id'] = $this->getRequest()->getPost('item_id');
            $filterData['account_id'] = $this->getRequest()->getPost('account_id');
        }
        
        $apiController = $this->prepareApi($request, 'Sales', $filterData);
        $result = $apiController->dispatch($request);
        //$result = json_decode($result, true);
        
        if (is_array($result->data)) {
            /*echo '<pre>';
            print_r($result->data);
            echo '</pre>';*/

            $params['reports'] = $result->data;
        } else {
            $params['error_code'] = 401;
            $params['error_msg'] = "Accounting vendor failed";
        }

        $customers = $this->getObjectManager()->getRepository('\Application\Entity\Customer')->findBy(array('user_id' => $this->getUserId()));
        $params['customers'] = $customers;

        $suppliers = $this->getObjectManager()->getRepository('\Application\Entity\Supplier')->findBy(array('user_id' => $this->getUserId()));
        $params['suppliers'] = $suppliers;

        $accounts = $this->getObjectManager()->getRepository('\Application\Entity\Account')->findBy(array('user_id' => $this->getUserId()));
        $params['accounts'] = $accounts;

        $items = $this->getObjectManager()->getRepository('\Application\Entity\Item')->findBy(array('user_id' => $this->getUserId()));
        $params['items'] = $items;

        //return new ViewModel($params);
        $view = new ViewModel($params);
        $view->setTemplate("apiclient/report/sales-report.phtml");
        return $view;
    }
    
    public function balanceSheetAction()
    {
        $request    = new Request();
        $request->setMethod(Request::METHOD_GET);
        
        $apiController = $this->prepareApi($request, 'BalanceSheet');
        $result = $apiController->dispatch($request);
        $result = json_decode($result->data, true);
        if (is_array($result)) {
            /*echo '<pre>';
            print_r($result);
            echo '</pre>';*/
            $params['reports'] = $result;
        } else {
            $params['error_code'] = 401;
            $params['error_msg'] = "Accounting vendor failed";
        }

        $accountingVendor = $this->getAccountingVendor();
        
        //return new ViewModel($params);
        $view = new ViewModel($params);
        $view->setTemplate("apiclient/report/balance-sheet-{$accountingVendor}.phtml"); // path to phtml file under view folder
        return $view;
    }
    
    public function profitAndLossAction()
    {
        $request    = new Request();
        $request->setMethod(Request::METHOD_GET);
        
        $apiController = $this->prepareApi($request, 'ProfitAndLoss');
        $result = $apiController->dispatch($request);
        $result = json_decode($result->data, true);
        
//        echo '<pre>';
//        print_r($result);
//        echo '</pre>';
//        die();

        if (isset($result['Rows']) || isset($result['Columns'])) {
            $params['reports'] = $result;
        } else {
            $params['error_code'] = 401;
            $params['error_msg'] = "Accounting vendor failed";
        }

        return new ViewModel($params);
    }

    private function prepareApi(Request $request, $reportType, $filterArr = array())
    {
        $accessToken = $this->getAccessToken();
        $id = (int) $this->params()->fromRoute('id', 0);
        if ($id) {
            $request->setQuery(new \Zend\Stdlib\Parameters(array_merge(array('access_token' => $accessToken, 'id' => $id), $filterArr)));
        } else {
            $request->setQuery(new \Zend\Stdlib\Parameters(array_merge(array('access_token' => $accessToken), $filterArr)));
        }
        
        $apiController = new \Input\Controller\ReportController();
        $apiController->setServiceLocator($this->getServiceLocator());

        // Need to send user_id or token
        $routeMatch       = new \Zend\Mvc\Router\RouteMatch(array('controller' => 'report', 'report-type' => $reportType));
        
        $event      = new \Zend\Mvc\MvcEvent();
        $event->setRouteMatch($routeMatch);
        $event->setRequest($request);

        $apiController->setEvent($event);

        return $apiController;
    }

}
