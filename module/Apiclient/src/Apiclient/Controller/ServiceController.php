<?php
namespace Apiclient\Controller;

use Apiclient\Controller\ApiclientActionController;
use Zend\View\Model\ViewModel;
use Zend\Http\Request;

class ServiceController extends ApiclientActionController
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
            $params['services'] = $result->data;
        } else {
            $params['error_code'] = $result->error_code;
            $params['error_msg'] = $result->error_msg;
        }

        return new ViewModel($params);
    }

    public function viewAction()
    {
        $params = array();

        $request    = new Request();
        $request->setMethod(Request::METHOD_GET);
        
        $apiController = $this->prepareApi($request);
        $params['service'] = $apiController->dispatch($request)->data;
        
//        echo '<pre>==';
//        print_r($params['service']);
//        echo '==</pre>';
        
        return new ViewModel($params);
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
        $routeMatch       = new \Zend\Mvc\Router\RouteMatch(array('controller' => 'input', 'input-type' => 'service'));

        $event      = new \Zend\Mvc\MvcEvent();
        $event->setRouteMatch($routeMatch);
        $event->setRequest($request);

        $apiController->setEvent($event);

        return $apiController;
    }

}
