<?php

namespace ApiclientTest\Controller;

use \Bootstrap;
use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use Apiclient\Controller\TransactionController;
use Zend\Http\Request;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\RouteMatch;

class TransactionControllerTest extends AbstractHttpControllerTestCase
{
    protected $controller;
    protected $request;
    protected $response;
    protected $routeMatch;
    protected $event;

    protected function setUp()
    {
        $serviceManager = Bootstrap::getServiceManager();
        
        $this->request    = new Request();
        $this->routeMatch = new RouteMatch(array('controller' => 'Transaction'));

        $this->event      = new MvcEvent();
        $this->event->setRouteMatch($this->routeMatch);
        
        $this->controller = new TransactionController();
        $this->controller->setEvent($this->event);
        $this->controller->setServiceLocator($serviceManager);
    }
    
    
    public function testIndex()
    {
        $this->routeMatch->setParam('action', 'index');
        $result   = $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
    }
    
}
