<?php

namespace InputTest\Controller;

use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use Input\Controller\InputController;

use Zend\Http\Request;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\RouteMatch;

class IndexControllerCompanyTest extends AbstractHttpControllerTestCase
{
    protected $traceError = true;
    protected $controller;
    protected $company;
    protected $request;
    protected $event;
    
    public function setUp()
    {
        $company = $this->getMock('\Application\Entity\Company', 
                                  array('getId', 'getVatId', 'setVatId', 'getCompanyName', 'setCompanyName', 'getName', 'setName', 'getSurname', 'setSurname', 'getAddress', 'setAddress', 'getCity', 'setCity', 'getPostcode', 'setPostcode', 'getCountry', 'setCountry'));
        $company->expects($this->any())
                 ->method('getId')
                 ->will($this->returnValue('company-id-1'));
        $company->expects($this->any())
                 ->method('getVatId')
                 ->will($this->returnValue('PL8171879931'));
        $company->expects($this->any())
                 ->method('setVatId')
                 ->with($this->equalTo('PL8171879931'));
        $company->expects($this->any())
                 ->method('getCompanyName')
                 ->will($this->returnValue('HSTechnology Paweł Puterla'));
        $company->expects($this->any())
                 ->method('setCompanyName')
                 ->with($this->equalTo('HSTechnology Paweł Puterla'));
        $company->expects($this->any())
                 ->method('getName')
                 ->will($this->returnValue('Paweł'));
        $company->expects($this->any())
                 ->method('setName')
                 ->with($this->equalTo('Paweł'));
        $company->expects($this->any())
                 ->method('getSurname')
                 ->will($this->returnValue('Puterla'));
        $company->expects($this->any())
                 ->method('setSurname')
                 ->with($this->equalTo('Puterla'));
        $company->expects($this->any())
                 ->method('getAddress')
                 ->will($this->returnValue('ul. Skarbka z Gór 25B/18'));
        $company->expects($this->any())
                 ->method('setAddress')
                 ->with($this->equalTo('ul. Skarbka z Gór 25B/18'));
        $company->expects($this->any())
                 ->method('getCity')
                 ->will($this->returnValue('Warszawa'));
        $company->expects($this->any())
                 ->method('setCity')
                 ->with($this->equalTo('Warszawa'));
        $company->expects($this->any())
                 ->method('getPostcode')
                 ->will($this->returnValue('03-287'));
        $company->expects($this->any())
                 ->method('setPostcode')
                 ->with($this->equalTo('03-287'));
        $company->expects($this->any())
                 ->method('getCountry')
                 ->will($this->returnValue('PL'));
        $company->expects($this->any())
                 ->method('setCountry')
                 ->with($this->equalTo('PL'));

        // Now, mock the repository so it returns the mock of the employee
        $companyRepository = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
                                      ->setMethods(array('findAll'))
                                      ->disableOriginalConstructor()
                                      ->getMock();
        $companyRepository->expects($this->any())
                              ->method('findAll')
                              ->will($this->returnValue(array($company)));
        

        // Last, mock the EntityManager to return the mock of the repository
        $entityManager = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
                              ->setMethods(array('getRepository', 'find', 'persist', 'remove', 'flush'))
                              ->disableOriginalConstructor()
                              ->getMock();
        $entityManager->expects($this->any())
                      ->method('getRepository')
                      ->will($this->returnValue($companyRepository));
        $entityManager->expects($this->any())
                      ->method('find')
                      ->will($this->returnValue($company));

        // Last, mock the EntityManager to return the mock of the repository
        $hydrator = $this->getMockBuilder('\DoctrineModule\Stdlib\Hydrator\DoctrineObject')
                              ->setMethods(array('extract'))
                              ->disableOriginalConstructor()
                              ->getMock();
        $hydrator->expects($this->any())
                 ->method('extract')
                 ->will($this->returnValue(array('id' => 'company-id-1',
                                                 'vat_id' => 'PL8171879931',
                                                 'company_name' => 'HSTechnology Paweł Puterla',
                                                 'name' => 'Paweł',
                                                 'surname' => 'Puterla',
                                                 'address' => 'ul. Skarbka z Gór 25B/18',
                                                 'city' => 'Warszawa',
                                                 'postcode' => '03-287',
                                                 'country' => 'PL')));
        // validateCompany is removed, so need to remove from here also
        $validator = $this->getMock('\Input\Model\Validator', array('validateCompany'));
        $validator->expects($this->any())
                  ->method('validateCompany')
                  ->will($this->returnValue(true));

        $model = new \Input\Model\Company();
        $model->setObjectManager($entityManager);
        $model->setHydrator($hydrator);
        $model->setEntity($company);
        $this->controller = new InputController($model, $validator);

        $this->request    = new Request();
        $routeMatch       = new RouteMatch(array('controller' => 'input', 'input-type' => 'company'));
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
        $this->assertEquals('company-id-1', $result->data[0]['id']);
        $this->assertEquals('PL8171879931', $result->data[0]['vat_id']);
        $this->assertEquals('HSTechnology Paweł Puterla', $result->data[0]['company_name']);
        $this->assertEquals('Paweł', $result->data[0]['name']);
        $this->assertEquals('Puterla', $result->data[0]['surname']);
        $this->assertEquals('ul. Skarbka z Gór 25B/18', $result->data[0]['address']);
        $this->assertEquals('Warszawa', $result->data[0]['city']);
        $this->assertEquals('03-287', $result->data[0]['postcode']);
        $this->assertEquals('PL', $result->data[0]['country']);
        
    }

    public function testGet()
    {
        $this->request->setMethod(Request::METHOD_GET)
                      ->setQuery(new \Zend\Stdlib\Parameters(array('id'=>'company-id-1')));
        $this->event->setRequest($this->request);
        $this->controller->setEvent($this->event);
        
        $result = $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertInstanceOf('\Zend\View\Model\JsonModel', $result);
        $this->assertInternalType('array', $result->data);
        $this->assertEquals('PL8171879931', $result->data['vat_id']);
        $this->assertEquals('HSTechnology Paweł Puterla', $result->data['company_name']);
        $this->assertEquals('Paweł', $result->data['name']);
        $this->assertEquals('Puterla', $result->data['surname']);
        $this->assertEquals('ul. Skarbka z Gór 25B/18', $result->data['address']);
        $this->assertEquals('Warszawa', $result->data['city']);
        $this->assertEquals('03-287', $result->data['postcode']);
        $this->assertEquals('PL', $result->data['country']);
    }

    public function testCreate()
    {
        $createData = array('vat_id' => 'PL8171879931',
                            'company_name' => 'HSTechnology Paweł Puterla',
                            'name' => 'Paweł',
                            'surname' => 'Puterla',
                            'address' => 'ul. Skarbka z Gór 25B/18',
                            'city' => 'Warszawa',
                            'postcode' => '03-287',
                            'country' => 'PL');
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
        $this->assertInstanceOf('\Application\Entity\Company', $result->data);
        $this->assertEquals('PL8171879931', $result->data->getVatId());
        $this->assertEquals('HSTechnology Paweł Puterla', $result->data->getCompanyName());
        $this->assertEquals('Paweł', $result->data->getName());
        $this->assertEquals('Puterla', $result->data->getSurname());
        $this->assertEquals('ul. Skarbka z Gór 25B/18', $result->data->getAddress());
        $this->assertEquals('Warszawa', $result->data->getCity());
        $this->assertEquals('03-287', $result->data->getPostcode());
        $this->assertEquals('PL', $result->data->getCountry());
    }

    public function testUpdate()
    {
        $array = array('vat_id' => 'PL8171879931',
                       'company_name' => 'HSTechnology Paweł Puterla',
                       'name' => 'Paweł',
                       'surname' => 'Puterla',
                       'address' => 'ul. Skarbka z Gór 25B/18',
                       'city' => 'Warszawa',
                       'postcode' => '03-287',
                       'country' => 'PL');
        //$raw_data = http_build_query($array);
        $this->request->setMethod(Request::METHOD_PUT)
                      //->setContent($raw_data)
                      ->setContent(\Zend\Json\Json::encode($array))
                      ->setQuery(new \Zend\Stdlib\Parameters(array('id'=>'company-id-1')))
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
        $this->assertInstanceOf('\Application\Entity\Company', $result->data);
        $this->assertEquals('PL8171879931', $result->data->getVatId());
        $this->assertEquals('HSTechnology Paweł Puterla', $result->data->getCompanyName());
        $this->assertEquals('Paweł', $result->data->getName());
        $this->assertEquals('Puterla', $result->data->getSurname());
        $this->assertEquals('ul. Skarbka z Gór 25B/18', $result->data->getAddress());
        $this->assertEquals('Warszawa', $result->data->getCity());
        $this->assertEquals('03-287', $result->data->getPostcode());
        $this->assertEquals('PL', $result->data->getCountry());
    }

    public function testDelete()
    {
        $this->request->setMethod(Request::METHOD_DELETE)
                      ->setQuery(new \Zend\Stdlib\Parameters(array('id'=>'company-id-1')))
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
