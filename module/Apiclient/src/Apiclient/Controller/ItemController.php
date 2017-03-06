<?php
namespace Apiclient\Controller;

use Apiclient\Controller\ApiclientActionController;
use Zend\View\Model\ViewModel;
use Zend\Http\Request;

error_reporting(0);

class ItemController extends ApiclientActionController
{
    public function indexAction()
    {
        $request    = new Request();
        $request->setMethod(Request::METHOD_GET);
        
        $apiController = $this->prepareApi($request);
        $result = $apiController->dispatch($request);
        
        if ($result->data) {
            echo '<pre>';
            print_r(json_encode($result->getVariables()));
            echo '</pre>';
            $params['items'] = $result->data;
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
            $createData = array('item_name' => $this->getRequest()->getPost('item_name'),
                                'item_type' => $this->getRequest()->getPost('item_type'),
                                'income_account_id' => $this->getRequest()->getPost('income_account_id'),
                                'expense_account_id' => $this->getRequest()->getPost('expense_account_id'),
                                'description' => $this->getRequest()->getPost('description'),
                                'price' => $this->getRequest()->getPost('price'));
            
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
                return $this->redirect()->toRoute(null, array('controller' => 'item', 'action'=>'index'));
            } else {
                $params['error_code'] = $result->error_code;
                $params['error_msg'] = $result->error_msg;
            }
        }

        try {
            $users = $this->getObjectManager()->getRepository('\Application\Entity\User')->findAll();
            $params['users'] = $users;
            
            $userInfo = $this->getObjectManager()->find('\Application\Entity\User', $this->getUserId());
            $params['accountingVendor'] = $userInfo->getAccountingVendor();
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
            $updatedData = array('item_name' => $this->getRequest()->getPost('item_name'),
                                'item_type' => $this->getRequest()->getPost('item_type'),
                                'income_account_id' => $this->getRequest()->getPost('income_account_id'),
                                'expense_account_id' => $this->getRequest()->getPost('expense_account_id'),
                                'description' => $this->getRequest()->getPost('description'),
                                'price' => $this->getRequest()->getPost('price'));
            
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
                return $this->redirect()->toRoute(null, array('controller' => 'item', 'action'=>'index'));
            } else {
                $params['error_code'] = $result->error_code;
                $params['error_msg'] = $result->error_msg;
            }
        }

        $request    = new Request();
        $request->setMethod(Request::METHOD_GET);
        
        $apiController = $this->prepareApi($request);
        $params['item'] = $apiController->dispatch($request)->data;
        
        $userInfo = $this->getObjectManager()->find('\Application\Entity\User', $this->getUserId());
        $params['accountingVendor'] = $userInfo->getAccountingVendor();
        
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
            return $this->redirect()->toRoute(null, array('controller' => 'item', 'action'=>'index'));
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
        $routeMatch       = new \Zend\Mvc\Router\RouteMatch(array('controller' => 'input', 'input-type' => 'item'));

        $event      = new \Zend\Mvc\MvcEvent();
        $event->setRouteMatch($routeMatch);
        $event->setRequest($request);

        $apiController->setEvent($event);

        return $apiController;
    }

}
