<?php

namespace InputTest\Controller;

use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use Input\Controller\InputController;

use Zend\Http\Request;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\RouteMatch;

class IndexControllerTransactionTest extends AbstractHttpControllerTestCase
{
    protected $traceError = true;
    protected $controller;
    protected $transaction;
    protected $request;
    protected $event;
    
    public function setUp()
    {
        $amount = $this->getMock('\Application\Entity\Amount', array('getValue', 'setValue', 'getCurrency', 'setCurrency'));
        $amount->expects($this->any())
                 ->method('getValue')
                 ->will($this->returnValue(100.4));
        $amount->expects($this->any())
                 ->method('setValue')
                 ->with($this->equalTo(100.4));
        $amount->expects($this->any())
                 ->method('getCurrency')
                 ->will($this->returnValue('GBP'));
        $amount->expects($this->any())
                 ->method('setCurrency')
                 ->with($this->equalTo('GBP'));

        $transaction = $this->getMock('\Application\Entity\Transaction', array('getId', 'getCompanyId', 'setCompanyId', 'getAccountId', 'setAccountId', 'getAmount', 'setAmount', 'getTaxId', 'setTaxId', 'getDatetime', 'setDatetime'));
        $transaction->expects($this->any())
                 ->method('getId')
                 ->will($this->returnValue(1));
        $transaction->expects($this->any())
                 ->method('getCompanyId')
                 ->will($this->returnValue('company-id-1'));
        $transaction->expects($this->any())
                 ->method('setCompanyId')
                 ->with($this->equalTo('company-id-1'));
        $transaction->expects($this->any())
                 ->method('getAccountId')
                 ->will($this->returnValue('account-id-1'));
        $transaction->expects($this->any())
                 ->method('setAccountId')
                 ->with($this->equalTo('account-id-1'));
        $transaction->expects($this->any())
                 ->method('getAmount')
                 ->will($this->returnValue($amount));
        $transaction->expects($this->any())
                 ->method('getTaxId')
                 ->will($this->returnValue('tax-id-1'));
        $transaction->expects($this->any())
                 ->method('setTaxId')
                 ->with($this->equalTo('tax-id-1'));
        $transaction->expects($this->any())
                 ->method('getDatetime')
                 ->will($this->returnValue('2014-01-04T23:11:00Z'));
        $transaction->expects($this->any())
                 ->method('setDatetime')
                 ->with($this->isInstanceOf('\DateTime'));

        // Now, mock the repository so it returns the mock of the employee
        $transactionRepository = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
                                      ->setMethods(array('findAll'))
                                      ->disableOriginalConstructor()
                                      ->getMock();
        $transactionRepository->expects($this->any())
                              ->method('findAll')
                              ->will($this->returnValue(array($transaction)));
        

        // Last, mock the EntityManager to return the mock of the repository
        $entityManager = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
                              ->setMethods(array('getRepository', 'find', 'persist', 'remove', 'flush'))
                              ->disableOriginalConstructor()
                              ->getMock();
        $entityManager->expects($this->any())
                      ->method('getRepository')
                      ->will($this->returnValue($transactionRepository));
        $entityManager->expects($this->any())
                      ->method('find')
                      ->will($this->returnValue($transaction));

        // Last, mock the EntityManager to return the mock of the repository
        $hydrator = $this->getMockBuilder('\DoctrineModule\Stdlib\Hydrator\DoctrineObject')
                              ->setMethods(array('extract'))
                              ->disableOriginalConstructor()
                              ->getMock();
        $hydrator->expects($this->any())
                 ->method('extract')
                 ->will($this->returnValue(array('id' => 1,
                                                 'company_id' => 'company-id-1',
                                                 'account_id' => 'account-id-1',
                                                 'amount' => 100.4,
                                                 'currency' => 'GBP',
                                                 'tax_id' => 'tax-id-1',
                                                 'date' => new \DateTime('2014-01-04T23:11:00Z'))));
        
        // validateCompany is removed, so need to remove from here also
        $validator = $this->getMock('\Input\Model\Validator', array('validateCompany'));
        $validator->expects($this->any())
                  ->method('validateCompany')
                  ->will($this->returnValue(true));

        $model = new \Input\Model\Transaction();
        $model->setObjectManager($entityManager);
        $model->setHydrator($hydrator);
        $model->setEntity($transaction);
        $model->setAmount($amount);
        $this->controller = new InputController($model, $validator);

        $this->request    = new Request();
        $routeMatch       = new RouteMatch(array('controller' => 'input', 'input-type' => 'transaction'));
        $this->event      = new MvcEvent();
        $this->event->setRouteMatch($routeMatch);

        parent::setUp();
    }

    public function testGetList()
    {
        $this->request->setMethod(Request::METHOD_GET);
        $this->event->setRequest($this->request);
        $this->controller->setEvent($this->event);
        
        $result = $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertInstanceOf('\Zend\View\Model\JsonModel', $result);
        $this->assertInternalType('array', $result->data);
        $this->assertInternalType('array', $result->data[0]);
        $this->assertEquals(1, $result->data[0]['id']);
        $this->assertEquals('company-id-1', $result->data[0]['company_id']);
        $this->assertEquals('account-id-1', $result->data[0]['account_id']);
        $this->assertEquals(100.4, $result->data[0]['amount']);
        $this->assertEquals('GBP', $result->data[0]['currency']);
        $this->assertEquals('tax-id-1', $result->data[0]['tax_id']);
        $this->assertInstanceOf('\DateTime', $result->data[0]['date']);
        $this->assertEquals(new \DateTime('2014-01-04T23:11:00Z'), $result->data[0]['date']);
    }

    public function testGet()
    {
        $this->request->setMethod(Request::METHOD_GET)
                      ->setQuery(new \Zend\Stdlib\Parameters(array('id'=>'1')));
        $this->event->setRequest($this->request);
        $this->controller->setEvent($this->event);
        
        $result = $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertInstanceOf('\Zend\View\Model\JsonModel', $result);
        $this->assertInternalType('array', $result->data);
        $this->assertEquals('company-id-1', $result->data['company_id']);
        $this->assertEquals('account-id-1', $result->data['account_id']);
        $this->assertEquals(100.4, $result->data['amount']);
        $this->assertEquals('GBP', $result->data['currency']);
        $this->assertEquals('tax-id-1', $result->data['tax_id']);
        $this->assertInstanceOf('\DateTime', $result->data['date']);
        $this->assertEquals(new \DateTime('2014-01-04T23:11:00Z'), $result->data['date']);
    }

    public function testCreate()
    {
        $createData = array('company_id' => 'company-id-1',
                            'account_id' => 'account-id-1',
                            'amount' => 100.4,
                            'currency' => 'GBP',
                            'tax_id' => 'tax-id-1');
        $this->request->setMethod(Request::METHOD_POST)
                      //->setPost(new \Zend\Stdlib\Parameters($createData))
                      ->setContent(\Zend\Json\Json::encode($createData))
                      ->getHeaders()->addHeaders(array(
                          'Content-type' => 'application/json',
                          'Accept' => 'application/json'
                      ));
        $this->event->setRequest($this->request);
        $this->controller->setEvent($this->event);

        $result = $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertInstanceOf('\Zend\View\Model\JsonModel', $result);
        $this->assertInternalType('array', $result->data);
        $this->assertEquals('company-id-1', $result->data['company_id']);
        $this->assertEquals('account-id-1', $result->data['account_id']);
        $this->assertEquals(100.4, $result->data['amount']);
        $this->assertEquals('GBP', $result->data['currency']);
        $this->assertEquals('tax-id-1', $result->data['tax_id']);
        $this->assertInstanceOf('\DateTime', $result->data['date']);
        $this->assertEquals(new \DateTime('2014-01-04T23:11:00Z'), $result->data['date']);
    }

    public function testUpdate()
    {
        $array = array('company_id' => 'company-id-1',
                        'account_id' => 'account-id-1',
                        'amount' => 100.4,
                        'currency' => 'GBP',
                        'tax_id' => 'tax-id-1');
        //$raw_data = http_build_query($array);
        $this->request->setMethod(Request::METHOD_PUT)
                      //->setContent($raw_data)
                      ->setContent(\Zend\Json\Json::encode($array))
                      ->setQuery(new \Zend\Stdlib\Parameters(array('id'=>'1')))
                      ->getHeaders()->addHeaders(array(
                          'Content-type' => 'application/json',
                          'Accept' => 'application/json'
                      ));

        $this->event->setRequest($this->request);
        $this->controller->setEvent($this->event);

        $result = $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertInstanceOf('\Zend\View\Model\JsonModel', $result);
        $this->assertInternalType('array', $result->data);
        $this->assertEquals('company-id-1', $result->data['company_id']);
        $this->assertEquals('account-id-1', $result->data['account_id']);
        $this->assertEquals(100.4, $result->data['amount']);
        $this->assertEquals('GBP', $result->data['currency']);
        $this->assertEquals('tax-id-1', $result->data['tax_id']);
        $this->assertInstanceOf('\DateTime', $result->data['date']);
        $this->assertEquals(new \DateTime('2014-01-04T23:11:00Z'), $result->data['date']);
    }

    public function testDelete()
    {
        $this->request->setMethod(Request::METHOD_DELETE)
                      ->setQuery(new \Zend\Stdlib\Parameters(array('id'=>'1')))
                      ->getHeaders()->addHeaders(array(
                          'Content-type' => 'application/json',
                          'Accept' => 'application/json'
                      ));

        $this->event->setRequest($this->request);
        $this->controller->setEvent($this->event);

        $result = $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertInstanceOf('\Zend\View\Model\JsonModel', $result);
        $this->assertEquals('1', $result->data);
    }
}
