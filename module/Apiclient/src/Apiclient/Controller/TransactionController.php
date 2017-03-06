<?php
namespace Apiclient\Controller;

use Apiclient\Controller\ApiclientActionController;
use Zend\View\Model\ViewModel;
use Zend\Http\Request;

class TransactionController extends ApiclientActionController
{
    public function indexAction()
    {
        $request    = new Request();
        $request->setMethod(Request::METHOD_GET);
        
        $apiController = $this->prepareApi($request);
        $result = $apiController->dispatch($request);
        
        if ($result->data) {
//            echo '<pre>';
//            print_r(json_encode($result->getVariables()));
//            echo '</pre>';
            $params['transactions'] = $result->data;
        } else {
            $params['error_code'] = $result->error_code;
            $params['error_msg'] = $result->error_msg;
        }

        return new ViewModel($params);
    }

    public function addAction()
    {
        $params = array();
        
        if ($this->request->isPost()) {
            $createData = array('customer_id' => $this->getRequest()->getPost('customer_id'),
                                'supplier_id' => $this->getRequest()->getPost('supplier_id'),
                                'account_id_financial' => $this->getRequest()->getPost('account_id_financial'), // for Qb
                                'bank_account_id' => $this->getRequest()->getPost('bank_account_id'), // for sage
                                'tax_id' => $this->getRequest()->getPost('tax_id'),
                                'discount_amount_total' => $this->getRequest()->getPost('discount_amount_total'),
                                'discount_percent_total' => $this->getRequest()->getPost('discount_percent_total'),
                                'tax_amount_total' => $this->getRequest()->getPost('tax_amount_total'),
                                'tax_percent_total' => $this->getRequest()->getPost('tax_percent_total'),
                                'amount' => $this->getRequest()->getPost('amount'), // for Sage SalesReceipt
                                'transaction_description' => $this->getRequest()->getPost('transaction_description'), // for Sage SalesReceipt
                                'currency' => $this->getRequest()->getPost('currency'),
                                'payment_made' => $this->getRequest()->getPost('payment_made'),
                                'payment_type' => $this->getRequest()->getPost('payment_type'),
                                'item_id' => $this->getRequest()->getPost('item_id'), // array
                                'account_id' => $this->getRequest()->getPost('account_id'), // array
                                'description' => $this->getRequest()->getPost('description'), // array
                                'quantity' => $this->getRequest()->getPost('quantity'), // array
                                'unit_price' => $this->getRequest()->getPost('unit_price'), // array
                                'tax_percentage' => $this->getRequest()->getPost('tax_percentage'), // array
                                'discount_percentage' => $this->getRequest()->getPost('discount_percentage'), // array
                                'tax' => $this->getRequest()->getPost('tax'), // array
                                'discount' => $this->getRequest()->getPost('discount'), // array
                );

            $request    = new Request();
            $request->setMethod(Request::METHOD_POST)
                    ->setContent(json_encode($createData));
            $request->getHeaders()->addHeaders(array(
                'Content-type' => 'application/json',
                'Accept' => 'application/json'
            ));
            
            $apiController = $this->prepareApi($request);
            $result = $apiController->dispatch($request);
            $response = $apiController->getResponse();

            if ($response->getStatusCode() == 200 && $result->data) {
                return $this->redirect()->toRoute(null, array('controller' => 'transaction', 'action'=>'index'));
            } else {
                $params['error_code'] = $result->error_code;
                $params['error_msg'] = $result->error_msg;
            }
        }

        try {
            $customers = $this->getObjectManager()->getRepository('\Application\Entity\Customer')->findBy(array('user_id' => $this->getUserId()));
            $params['customers'] = $customers;
            
            $suppliers = $this->getObjectManager()->getRepository('\Application\Entity\Supplier')->findBy(array('user_id' => $this->getUserId()));
            $params['suppliers'] = $suppliers;
            
            $accounts = $this->getObjectManager()->getRepository('\Application\Entity\Account')->findBy(array('user_id' => $this->getUserId()));
            $params['accounts'] = $accounts;
            
            $items = $this->getObjectManager()->getRepository('\Application\Entity\Item')->findBy(array('user_id' => $this->getUserId()));
            $params['items'] = $items;

            $taxes = $this->getObjectManager()->getRepository('\Application\Entity\TaxCode')->findBy(array('user_id' => $this->getUserId()));
            $params['taxes'] = $taxes;
            
            $userInfo = $this->getObjectManager()->find('\Application\Entity\User', $this->getUserId());
            $params['accountingVendor'] = $userInfo->getAccountingVendor();
            if ($userInfo->getAccountingVendor() == 'Qb') {
                $accountIdFinancials = $this->getObjectManager()->getRepository('\Application\Entity\Account')->findBy(array('user_id' => $this->getUserId(), 'account_type' => 'Bank'));
                $params['accountIdFinancials'] = $accountIdFinancials;
            } else {
                $bankAccounts = $this->getObjectManager()->getRepository('\Application\Entity\BankAccount')->findBy(array('user_id' => $this->getUserId()));
                $params['bankAccounts'] = $bankAccounts;
            }
        } catch(\Exception $e) {
            $params['error_code'] = $e->getCode();
            $params['error_msg'] = $e->getMessage();
        }

        return new ViewModel($params);
    }

    public function editAction()
    {
        $params = array();

        if ($this->request->isPost()) {
            $updatedData = array('customer_id' => $this->getRequest()->getPost('customer_id'),
                                 'description' => $this->getRequest()->getPost('description'),
                                 'account_id' => $this->getRequest()->getPost('account_id'),
                                 'unit_price' => $this->getRequest()->getPost('unit_price'),
                                 'currency' => $this->getRequest()->getPost('currency'),
                                 'tax_id' => $this->getRequest()->getPost('tax_id'));
            
            $request    = new Request();
            $request->setMethod(Request::METHOD_PUT)
                    ->setContent(\Zend\Json\Json::encode($updatedData));
            $request->getHeaders()->addHeaders(array(
                'Content-type' => 'application/json',
                'Accept' => 'application/json'
            ));
            
            $apiController = $this->prepareApi($request);
            $result = $apiController->dispatch($request);
            $response = $apiController->getResponse();
            
            if ($response->getStatusCode() == 200 && $result->data) {
                return $this->redirect()->toRoute(null, array('controller' => 'transaction', 'action'=>'index'));
            } else {
                $params['error_code'] = $result->error_code;
                $params['error_msg'] = $result->error_msg;
            }
        }

        $request    = new Request();
        $request->setMethod(Request::METHOD_GET);
        
        $apiController = $this->prepareApi($request);
        $params['transaction'] = $apiController->dispatch($request)->data;
        
        try {
            $customers = $this->getObjectManager()->getRepository('\Application\Entity\Customer')->findAll();
            $params['customers'] = $customers;

            $accounts = $this->getObjectManager()->getRepository('\Application\Entity\Account')->findBy(array('user_id' => $this->getUserId()));
            $params['accounts'] = $accounts;
            
            $items = $this->getObjectManager()->getRepository('\Application\Entity\Item')->findBy(array('user_id' => $this->getUserId()));
            $params['items'] = $items;

            $taxes = $this->getObjectManager()->getRepository('\Application\Entity\TaxCode')->findBy(array('user_id' => $this->getUserId()));
            $params['taxes'] = $taxes;
        } catch(\Exception $e) {
            $params['error_code'] = $e->getCode();
            $params['error_msg'] = $e->getMessage();
        }
        
        return new ViewModel($params);
    }

    public function viewAction()
    {
        $params = array();

        $request    = new Request();
        $request->setMethod(Request::METHOD_GET);
        
        $apiController = $this->prepareApi($request);
        $params['transaction'] = $apiController->dispatch($request)->data;
        
        return new ViewModel($params);
    }
    
    public function deleteAction()
    {
        $request = new Request();
        $request->setMethod(Request::METHOD_DELETE);
        $request->getHeaders()->addHeaders(array(
                'Content-type' => 'application/json',
                'Accept' => 'application/json'
            ));
        
        $apiController = $this->prepareApi($request);
        $result = $apiController->dispatch($request);
        $response = $apiController->getResponse();

        if ($response->getStatusCode() == 200 && $result->data) {
            return $this->redirect()->toRoute(null, array('controller' => 'transaction', 'action'=>'index'));
        } else {
            $params['error_code'] = $result->error_code;
            $params['error_msg'] = $result->error_msg;
            echo '<pre>';
            print_r($params);
            echo '</pre>';
            die();
        }
    }

    private function prepareApi(Request $request)
    {
        $accessToken = $this->getAccessToken();
        $id = (int) $this->params()->fromRoute('id', 0);
        if ($id) {
            $request->setQuery(new \Zend\Stdlib\Parameters(array('access_token' => $accessToken, 'id' => $id)));
        } else {
            $request->setQuery(new \Zend\Stdlib\Parameters(array('access_token' => $accessToken)));
        }
        
        
        $apiController = new \Input\Controller\InputController();
        $apiController->setServiceLocator($this->getServiceLocator());
        
        // Need to send user_id or token
        $routeMatch       = new \Zend\Mvc\Router\RouteMatch(array('controller' => 'input', 'input-type' => 'transaction'));

        $event      = new \Zend\Mvc\MvcEvent();
        $event->setRouteMatch($routeMatch);
        $event->setRequest($request);

        $apiController->setEvent($event);

        return $apiController;
    }

}
