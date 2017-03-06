<?php
namespace Apiclient\Controller;

use Apiclient\Controller\ApiclientActionController;
use Zend\View\Model\ViewModel;
use Zend\Http\Request;
use Zend\Session\Container;

class UserController extends ApiclientActionController
{
    public function indexAction()
    {
        $request    = new Request();
        $request->setMethod(Request::METHOD_GET);

        $apiController = $this->prepareApi($request);
        $result = $apiController->dispatch($request);
        
        if ($result->data) {
            $params['users'] = $result->data;
        } else {
            $params['error_code'] = $result->error_code;
            $params['error_msg'] = $result->error_msg;
        }
        
        return new ViewModel($params);
    }
    
    public function setLoginAction() {
        $userId = $this->getEvent()->getRouteMatch()->getParam('id');
        $accessToken = $this->getRequest()->getQuery()->get('access_token', null);
        
        $login = new Container('login');
        $login->userId = $userId;
        $login->accessToken = $accessToken;
        
        $this->redirect()->toRoute(null, array('controller' => 'user', 'action'=>'index'));
    }
    
    public function setLogoutAction() {
        $login = new Container('login');
        $login->userId = null;
        $login->accessToken = null;
        
        $this->redirect()->toRoute(null, array('controller' => 'user', 'action'=>'index'));
    }
    
    private function createAccessToken($user_login, $password)
    {
        $settings = $this->getServiceLocator()->get("GlobalSettings");
        $baseUrl = $settings->getBaseUrl();
        $url = $baseUrl . "/oauth";
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
        //curl_setopt($ch, CURLOPT_USERPWD, $user_login.':'.$password);
        // or
        $encodedAuth = base64_encode($user_login.':'.$password); //shoeb00032@gmail.com:testpass
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization : Basic ".$encodedAuth));

//        curl_setopt($ch, CURLOPT_HEADER, 1);
//        curl_setopt($ch, CURLINFO_HEADER_OUT, 1);


        $result = curl_exec($ch);
        
        
        ///////////////
//        $getinfo = curl_getinfo($ch);
//        echo '<pre>';
//        print_r($_SERVER);
//        print_r($getinfo['request_header']);
//        echo "\r\n\r\n";
//        print_r($result);
//        echo '</pre>';
//        die();
        /////////////
        
        return $result;
    }
    
    public function loginAction()
    {
        $params = array();
        
        if ($this->request->isPost()) {
            $userId = $this->getEvent()->getRouteMatch()->getParam('id');
            $user_login = $this->getRequest()->getPost('user_login');
            $password = $this->getRequest()->getPost('user_password');
            
            $result = json_decode($this->createAccessToken($user_login, $password), true);

            if (empty($result['access_token'])) {
                $params['error_code'] = $result['status'];
                $params['error_msg'] = $result['detail'];
                return new ViewModel($params);
            }
            $accessToken = $result['access_token'];

            $login = new Container('login');
            $login->userId = $userId;
            $login->accessToken = $accessToken;

            $this->redirect()->toRoute(null, array('controller' => 'user', 'action'=>'index'));
        }

        return new ViewModel($params);
    }    

    public function addAction()
    {
        $params = array();
        
        if ($this->request->isPost()) {
            $createData['accounting_vendor'] = $this->getRequest()->getPost('accounting_vendor');
            $createData['user_login'] = $this->getRequest()->getPost('user_login');
            $createData['user_password'] = $this->getRequest()->getPost('user_password');
            $createData['property_list']['vendor_api_key'] = $this->getRequest()->getPost('vendor_api_key');
            $createData['property_list']['vendor_password'] = $this->getRequest()->getPost('vendor_password');
            $createData['property_list']['vendor_company_id'] = $this->getRequest()->getPost('vendor_company_id');
            
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
                return $this->redirect()->toRoute(null, array('controller' => 'user', 'action'=>'index'));
            } else {
                $params['error_code'] = $result->error_code;
                $params['error_msg'] = $result->error_msg;
            }
        }

        return new ViewModel($params);
    }

    public function editAction()
    {
        $params = array();

        if ($this->request->isPost()) {
            $updatedData['accounting_vendor'] = $this->getRequest()->getPost('accounting_vendor');
            $updatedData['user_login'] = $this->getRequest()->getPost('user_login');
            $updatedData['user_password'] = $this->getRequest()->getPost('user_password');
            $updatedData['property_list']['vendor_api_key'] = $this->getRequest()->getPost('vendor_api_key');
            $updatedData['property_list']['vendor_password'] = $this->getRequest()->getPost('vendor_password');
            $updatedData['property_list']['vendor_company_id'] = $this->getRequest()->getPost('vendor_company_id');
            
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
                return $this->redirect()->toRoute(null, array('controller' => 'user', 'action'=>'index'));
            } else {
                $params['error_code'] = $result->error_code;
                $params['error_msg'] = $result->error_msg;
            }
        }

        $request    = new Request();
        $request->setMethod(Request::METHOD_GET);
        
        $apiController = $this->prepareApi($request);
        $result = $apiController->dispatch($request);
        if ($result->data) {
            $params['user'] = $result->data;
        } else {
            $params['user'] = null;
            $params['error_code'] = $result->error_code;
            $params['error_msg'] = $result->error_msg;
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
            return $this->redirect()->toRoute(null, array('controller' => 'user', 'action'=>'index'));
        } else {
            $params['error_code'] = $result->error_code;
            $params['error_msg'] = $result->error_msg;
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

        // we get user_id from session which was taken from session 
        $routeMatch       = new \Zend\Mvc\Router\RouteMatch(array('controller' => 'input', 'input-type' => 'user'));

        $event      = new \Zend\Mvc\MvcEvent();
        $event->setRouteMatch($routeMatch);
        $event->setRequest($request);

        $apiController->setEvent($event);

        return $apiController;
    }
    
    public function checkAuthenticationAction()
    {
        $params = array();
        
        $request    = new Request();
        $request->setMethod(Request::METHOD_GET);

        $apiController = $this->prepareAuthenticationApi($request);
        $result = $apiController->dispatch($request);
        $response = $apiController->getResponse();

        if ($response->getStatusCode() == 200 && $result->data) {
            $params['data'] = $result->data;
        } else {   
            $params['error_code'] = $result->error_code;
            $params['error_msg'] = $result->error_msg;
        }

        return new ViewModel($params);
    }
    
    private function prepareAuthenticationApi(Request $request)
    {
        $accessToken = $this->getAccessToken();
        $request->setQuery(new \Zend\Stdlib\Parameters(array('access_token' => $accessToken)));
        
        $apiController = new \Input\Controller\AccessController();
        $apiController->setServiceLocator($this->getServiceLocator());

        // This method is for checking authentication and do not need user to be set logged in and hence we take user_id from route only
        $routeMatch       = new \Zend\Mvc\Router\RouteMatch(array('controller' => 'access', 
                                                                  'action' => 'vendor-authentication'));

        $event      = new \Zend\Mvc\MvcEvent();
        $event->setRouteMatch($routeMatch);
        $event->setRequest($request);

        $apiController->setEvent($event);

        return $apiController;
    }

}
