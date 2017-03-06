<?php
namespace Apiclient\Controller;

use Apiclient\Controller\ApiclientActionController;
use Zend\View\Model\ViewModel;
use Zend\Http\Request;
error_reporting(9);
class DefaultController extends ApiclientActionController
{
    private $_error_msg = '';
    private $_error_code = '';
    private $_success_msg = '';
    private $_default_entity_list = array();
    
    public function indexAction()
    {
        $request    = new Request();
        $request->setMethod(Request::METHOD_GET);
        
        $apiController = $this->prepareUserApi($request);
        $result = $apiController->dispatch($request);
        
        if ($result->data) {
//            echo '<pre>';
//            print_r(json_encode($result->getVariables()));
//            echo '</pre>';
            $params['user'] = $result->data;
        } else {
            $params['error_code'] = $result->error_code;
            $params['error_msg'] = $result->error_msg;
        }

        return new ViewModel($params);
    }
    
    public function addAction()
    {
        $params = array();
        $user = null;
        $updated = false;
        
        if ($this->request->isPost()) {
            $request    = new Request();
            $request->setMethod(Request::METHOD_GET);
            $apiController = $this->prepareUserApi($request);
            $result = $apiController->dispatch($request);
            //die('==');
            
            if ($result->data) {
                $user = $result->data;
            } else {
                $params['error_code'] = $result->error_code;
                $params['error_msg'] = $result->error_msg;
            }
        
            if ($user) {
                if (!$this->isDefaultEntity('customer', $user['default_entity_list'])) {
                    $this->addCustomer();
                    $updated = true;
                }
                if (!$this->isDefaultEntity('supplier', $user['default_entity_list'])) {
                    $this->addSupplier();
                    $updated = true;
                }
                if (!$this->isDefaultEntity('account', $user['default_entity_list'])) {
                    $this->addAccount();
                    $updated = true;
                }
                if (!$this->isDefaultEntity('financial_account', $user['default_entity_list']) 
                        && $user['accounting_vendor'] == 'Qb') {
                    $this->addFinancialAccount();
                    $updated = true;
                }
                if (!$this->isDefaultEntity('item', $user['default_entity_list'])) {
                    $this->addItem();
                    $updated = true;
                }
                if (!$this->isDefaultEntity('bank-account', $user['default_entity_list'])
                        && $user['accounting_vendor'] == 'Sage') {
                    $this->addBankAccount();
                    $updated = true;
                }                
                if ($updated) {
                    $this->updateUserDefaultEntity();
                }
                
                $params['success_msg'] = $this->_success_msg;
                $params['error_msg'] = $this->_error_msg;
            }
            
        }

        return new ViewModel($params);
    }

    /*public function editAction()
    {
        $params = array();

        if ($this->request->isPost()) {
            $updatedData = array('customer_name' => $this->getRequest()->getPost('customer_name'),
                                'display_name' => $this->getRequest()->getPost('display_name'),
                                'name' => $this->getRequest()->getPost('name'),
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
            
            $apiController = $this->prepareUserApi($request);
            $result = $apiController->dispatch($request);
            $response = $apiController->getResponse();
            
            if ($response->getStatusCode() == 200 && $result->data) {
                return $this->redirect()->toRoute(null, array('controller' => 'default', 'action'=>'index'));
            } else {
                $params['error_code'] = $result->error_code;
                $params['error_msg'] = $result->error_msg;
            }
        }

        $request    = new Request();
        $request->setMethod(Request::METHOD_GET);
        
        $apiController = $this->prepareUserApi($request);
        $params['customer'] = $apiController->dispatch($request)->data;
        
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
        
        $apiController = $this->prepareUserApi($request);
        $result = $apiController->dispatch($request);
        $response = $apiController->getResponse();

        if ($response->getStatusCode() == 200 && $result->data) {
            return $this->redirect()->toRoute(null, array('controller' => 'default', 'action'=>'index'));
        } else {
            $params['error_code'] = $result->error_code;
            $params['error_msg'] = $result->error_msg;
            echo '<pre>';
            print_r($params);
            echo '</pre>';
            die();
        }
    }*/

    private function addCustomer()
    {
        $createData = array('customer_name' => 'DefaultCustomer',
                            'display_name' => 'Default Customer',
                            'name' => 'Default Customer',
                            'surname' => '',
                            'address1' => '',
                            'address2' => '',
                            'city' => '',
                            'postcode' => '',
                            'country' => '');

        $request = $this->preparePostRequest($createData);
        $apiController = $this->prepareAnyApi($request, 'customer');
        $result = $apiController->dispatch($request);
        $response = $apiController->getResponse();

        if ($response->getStatusCode() == 200 && $result->data) {
        //if (1) {
            $this->_default_entity_list[] = 'customer';
            $this->_success_msg .= 'Default customer created successfully<br />';
        } else {
            $this->_error_msg .= $result->error_msg . '<br />';
        }
    }
    
    private function addSupplier()
    {
        $createData = array('supplier_name' => 'DefaultSupplier',
                            'display_name' => 'Default Supplier',
                            'name' => 'Default Supplier',
                            'surname' => '',
                            'address1' => '',
                            'address2' => '',
                            'city' => '',
                            'postcode' => '',
                            'country' => '');

        $request = $this->preparePostRequest($createData);
        $apiController = $this->prepareAnyApi($request, 'supplier');
        $result = $apiController->dispatch($request);
        $response = $apiController->getResponse();

        if ($response->getStatusCode() == 200 && $result->data) {
            $this->_default_entity_list[] = 'supplier';
            $this->_success_msg .= 'Default supplier created successfully<br />';
        } else {
            $this->_error_msg .= $result->error_msg . '<br />';
        }
    }
    
    private function addAccount()
    {
        $createData = array('name' => 'Default Expense Account',
                            'account_type' => 'Expense',
                            'account_sub_type' => '',
                            'category_id' => '2',
                            'category_description' => 'Cost of Sales');

        $request = $this->preparePostRequest($createData);
        $apiController = $this->prepareAnyApi($request, 'account');
        $result = $apiController->dispatch($request);
        $response = $apiController->getResponse();

        if ($response->getStatusCode() == 200 && $result->data) {
            $this->_default_entity_list[] = 'account';
            $this->_success_msg .= 'Default account created successfully<br />';
        } else {
            $this->_error_msg .= $result->error_msg . '<br />';
        }
    }
    
    private function addFinancialAccount()
    {
        $createData = array('name' => 'Default Financial Account',
                            'account_type' => 'Bank',
                            'account_sub_type' => '',
                            'category_id' => '',
                            'category_description' => '');

        $request = $this->preparePostRequest($createData);
        $apiController = $this->prepareAnyApi($request, 'account');
        $result = $apiController->dispatch($request);
        $response = $apiController->getResponse();

        if ($response->getStatusCode() == 200 && $result->data) {
            $this->_default_entity_list[] = 'financial_account';
            $this->_success_msg .= 'Default financial account created successfully<br />';
        } else {
            $this->_error_msg .= $result->error_msg . '<br />';
        }
    }
    
    private function addBankAccount()
    {
        $createData = array('account_name' => 'Default Bank Account',
                            'account_number' => '',
                            'bank_name' => '',
                            'branch_name' => '');

        $request = $this->preparePostRequest($createData);
        $apiController = $this->prepareAnyApi($request, 'bank-account');
        $result = $apiController->dispatch($request);
        $response = $apiController->getResponse();

        if ($response->getStatusCode() == 200 && $result->data) {
            $this->_default_entity_list[] = 'bank-account';
            $this->_success_msg .= 'Default bank-account created successfully<br />';
        } else {
            $this->_error_msg .= $result->error_msg . '<br />';
        }
    }
    
    private function addItem()
    {
        $createData = array('item_name' => 'Default Item',
                            'item_type' => '',
                            'income_account_id' => '1',
                            'expense_account_id' => '',
                            'description' => '',
                            'price' => 0);

        $request = $this->preparePostRequest($createData);
        $apiController = $this->prepareAnyApi($request, 'item');
        $result = $apiController->dispatch($request);
        $response = $apiController->getResponse();

        if ($response->getStatusCode() == 200 && $result->data) {
            $this->_default_entity_list[] = 'item';
            $this->_success_msg .= 'Default item created successfully<br />';
        } else {
            $this->_error_msg .= $result->error_msg . '<br />';
        }
    }
    
    private function isDefaultEntity($value, $defaultEntityArr)
    {
        if (in_array($value, $defaultEntityArr)) {
            return true;
        }
        
        return false;
    }

    private function updateUserDefaultEntity()
    {
        if (!$this->_default_entity_list) return false;
        
        $updatedData['default_entity_list'] = $this->_default_entity_list;
        
        $request    = new Request();
        $request->setMethod(Request::METHOD_PUT)
                ->setContent(\Zend\Json\Json::encode($updatedData));
        $request->getHeaders()->addHeaders(array(
            'Content-type' => 'application/json',
            'Accept' => 'application/json'
        ));

        $apiController = $this->prepareUserApi($request);
        $result = $apiController->dispatch($request);
        $response = $apiController->getResponse();

        if ($response->getStatusCode() == 200 && $result->data) {
            $this->_success_msg .= 'User default entity updated successfully<br />';
        } else {
            $this->_error_msg .= $result->error_msg . '<br />';
        }
    }
    
    private function preparePostRequest($createData)
    {
        $params = array();
        
        $request    = new Request();
        $request->setMethod(Request::METHOD_POST)
                ->setContent(json_encode($createData));
        $request->getHeaders()->addHeaders(array(
            'Content-type' => 'application/json',
            'Accept' => 'application/json'
        ));

        return $request;
    }
    
    private function prepareAnyApi(Request $request, $inputType, $id = 0)
    {
        $accessToken = $this->getAccessToken();
        //$id = (int) $this->params()->fromRoute('id', 0);
        if ($id) {
            $request->setQuery(new \Zend\Stdlib\Parameters(array('access_token' => $accessToken, 'id' => $id)));
        } else {
            $request->setQuery(new \Zend\Stdlib\Parameters(array('access_token' => $accessToken)));
        }
        
        $apiController = new \Input\Controller\InputController();
        $apiController->setServiceLocator($this->getServiceLocator());

        // Need to send user_id or token
        $routeMatch       = new \Zend\Mvc\Router\RouteMatch(array('controller' => 'input', 'input-type' => $inputType));

        $event      = new \Zend\Mvc\MvcEvent();
        $event->setRouteMatch($routeMatch);
        $event->setRequest($request);

        $apiController->setEvent($event);

        return $apiController;
    }

    private function prepareUserApi(Request $request)
    {
        $accessToken = $this->getAccessToken();
        $userId = $this->getUserId(); // if empty may be we can throw an exception
        //$id = (int) $this->params()->fromRoute('id', 0);
        $id = $userId;
        if ($id) {
            $request->setQuery(new \Zend\Stdlib\Parameters(array('access_token' => $accessToken, 'id' => $id)));
        } else {
            $request->setQuery(new \Zend\Stdlib\Parameters(array('access_token' => $accessToken)));
        }
        
        $apiController = new \Input\Controller\InputController();
        $apiController->setServiceLocator($this->getServiceLocator());

        // Need to send user_id or token
        $routeMatch       = new \Zend\Mvc\Router\RouteMatch(array('controller' => 'input', 'input-type' => 'user'));

        $event      = new \Zend\Mvc\MvcEvent();
        $event->setRouteMatch($routeMatch);
        $event->setRequest($request);

        $apiController->setEvent($event);

        return $apiController;
    }
    
}
