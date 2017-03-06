<?php
namespace Apiclient\Controller;

use Apiclient\Controller\ApiclientActionController;
use Zend\View\Model\ViewModel;
use Zend\Http\Request;

class TaxController extends ApiclientActionController
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
            $params['taxes'] = $result->data;
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
            //$createData['user_id'] = $this->getRequest()->getPost('user_id');
            $createData['tax_code'] = $this->getRequest()->getPost('tax_code');
            for($i = 0; $i < count($this->getRequest()->getPost('tax_rate_id')); $i++) {
                $createData['tax_rate_id'][$i] = $this->getRequest()->getPost('tax_rate_id')[$i];
                $createData['tax_rate_name'][$i] = $this->getRequest()->getPost('tax_rate_name')[$i];
                $createData['rate_value'][$i] = $this->getRequest()->getPost('rate_value')[$i];
                $createData['tax_agency_id'][$i] = $this->getRequest()->getPost('tax_agency_id')[$i];
                $createData['tax_applicable_on'][$i] = $this->getRequest()->getPost('tax_applicable_on')[$i];
            }
            
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
                return $this->redirect()->toRoute(null, array('controller' => 'tax', 'action'=>'index'));
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
        
        try {
            $taxAgencies = $this->getObjectManager()->getRepository('\Application\Entity\TaxAgency')->findAll();
            $params['taxAgencies'] = $taxAgencies;
            $taxRates = $this->getObjectManager()->getRepository('\Application\Entity\TaxRate')->findAll();
            $params['taxRates'] = $taxRates;
        } catch(\Exception $e) {
            $params['error_code'] = $e->getCode();
            $params['error_msg'] = $e->getMessage();
        }
        
        return new ViewModel($params);
    }

    /*public function deleteAction()
    {
        $request = new Request();
        $request->setMethod(Request::METHOD_DELETE);
                //->setQuery(new \Zend\Stdlib\Parameters(array('id' => $id)));
        $request->getHeaders()->addHeaders(array(
                'Content-type' => 'application/json',
                'Accept' => 'application/json'
            ));
        
        $apiController = $this->prepareApi($request);
        $result = $apiController->dispatch($request);
        $response = $apiController->getResponse();

        if ($response->getStatusCode() == 200 && $result->data) {
            return $this->redirect()->toRoute(null, array('controller' => 'tax', 'action'=>'index'));
        } else {
            $params['error_code'] = $result->error_code;
            $params['error_msg'] = $result->error_msg;
            echo '<pre>';
            print_r($params);
            echo '</pre>';
            die();
        }
    }*/

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
        $routeMatch       = new \Zend\Mvc\Router\RouteMatch(array('controller' => 'input', 'input-type' => 'tax'));

        $event      = new \Zend\Mvc\MvcEvent();
        $event->setRouteMatch($routeMatch);
        $event->setRequest($request);
        
        $apiController->setEvent($event);

        return $apiController;
    }

}
