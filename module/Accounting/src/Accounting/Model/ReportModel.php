<?php
namespace Accounting\Model;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

abstract class ReportModel implements ServiceLocatorAwareInterface
{
    private $serviceLocator;

    abstract protected function getReport($userId, $reportName);
    
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }

    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

}
