<?php
namespace Apiclient\Controller;

use Apiclient\Controller\ApiclientActionController;
use Zend\View\Model\ViewModel;
use Zend\Http\Request;

class BankAccountController extends ApiclientActionController
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
            $params['bankAccounts'] = $result->data;
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
            $createData = array('account_name' => $this->getRequest()->getPost('account_name'),
                                'account_number' => $this->getRequest()->getPost('account_number'),
                                'bank_name' => $this->getRequest()->getPost('bank_name'),
                                'branch_name' => $this->getRequest()->getPost('branch_name'));
            
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
                return $this->redirect()->toRoute(null, array('controller' => 'bank-account', 'action'=>'index'));
            } else {
                $params['error_code'] = $result->error_code;
                $params['error_msg'] = $result->error_msg;
            }
        }

        try {
            $users = $this->getObjectManager()->getRepository('\Application\Entity\User')->findAll();
            $params['users'] = $users;
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
            $updatedData = array('account_name' => $this->getRequest()->getPost('account_name'),
                                'account_number' => $this->getRequest()->getPost('account_number'),
                                'branch_name' => $this->getRequest()->getPost('branch_name'),
                                'surname' => $this->getRequest()->getPost('surname'),
                                'address1' => $this->getRequest()->getPost('address1'),
                                'address2' => $this->getRequest()->getPost('address2'),
                                'city' => $this->getRequest()->getPost('city'),
                                'postcode' => $this->getRequest()->getPost('postcode'),
                                'country' => $this->getRequest()->getPost('country'));
            
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
                return $this->redirect()->toRoute(null, array('controller' => 'bank-account', 'action'=>'index'));
            } else {
                $params['error_code'] = $result->error_code;
                $params['error_msg'] = $result->error_msg;
            }
        }

        $request    = new Request();
        $request->setMethod(Request::METHOD_GET);
        
        $apiController = $this->prepareApi($request);
        $params['bank_account'] = $apiController->dispatch($request)->data;
        
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
            return $this->redirect()->toRoute(null, array('controller' => 'bank-account', 'action'=>'index'));
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
        $routeMatch       = new \Zend\Mvc\Router\RouteMatch(array('controller' => 'input', 'input-type' => 'bank-account'));

        $event      = new \Zend\Mvc\MvcEvent();
        $event->setRouteMatch($routeMatch);
        $event->setRequest($request);

        $apiController->setEvent($event);

        return $apiController;
    }

}
