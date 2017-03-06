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
            echo '<pre>';
            print_r(json_encode($result->getVariables()));
            echo '</pre>';
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
            $createData = array('user_id' => $this->getRequest()->getPost('user_id'),
                                'company_id' => $this->getRequest()->getPost('company_id'),
                                'description' => $this->getRequest()->getPost('description'),
                                'account_id' => $this->getRequest()->getPost('account_id'),
                                'amount' => $this->getRequest()->getPost('amount'),
                                'currency' => $this->getRequest()->getPost('currency'),
                                'tax_id' => $this->getRequest()->getPost('tax_id'),
                                'payment_made' => $this->getRequest()->getPost('payment_made'),
                                'payment_type' => $this->getRequest()->getPost('payment_type'),);
            
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
            $companies = $this->getObjectManager()->getRepository('\Application\Entity\Company')->findBy(array('user_id' => $this->getUserId()));
            $params['companies'] = $companies;
            
            $users = $this->getObjectManager()->getRepository('\Application\Entity\User')->findAll();
            $params['users'] = $users;

            $accounts = $this->getObjectManager()->getRepository('\Application\Entity\Account')->findBy(array('user_id' => $this->getUserId()));
            $params['accounts'] = $accounts;

            $taxes = $this->getObjectManager()->getRepository('\Application\Entity\TaxCode')->findBy(array('user_id' => $this->getUserId()));
            $params['taxes'] = $taxes;
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
            $updatedData = array('company_id' => $this->getRequest()->getPost('company_id'),
                                 'description' => $this->getRequest()->getPost('description'),
                                 'account_id' => $this->getRequest()->getPost('account_id'),
                                 'amount' => $this->getRequest()->getPost('amount'),
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
            $companies = $this->getObjectManager()->getRepository('\Application\Entity\Company')->findAll();
            $params['companies'] = $companies;

            $accounts = $this->getObjectManager()->getRepository('\Application\Entity\Account')->findAll();
            $params['accounts'] = $accounts;

            $taxes = $this->getObjectManager()->getRepository('\Application\Entity\TaxCode')->findAll();
            $params['taxes'] = $taxes;
        } catch(\Exception $e) {
            $params['error_code'] = $e->getCode();
            $params['error_msg'] = $e->getMessage();
        }
        
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
        $routeMatch       = new \Zend\Mvc\Router\RouteMatch(array('controller' => 'input', 'input-type' => 'transaction', 'user_id' => $this->getUserId()));

        $event      = new \Zend\Mvc\MvcEvent();
        $event->setRouteMatch($routeMatch);
        $event->setRequest($request);

        $apiController->setEvent($event);

        return $apiController;
    }

}
