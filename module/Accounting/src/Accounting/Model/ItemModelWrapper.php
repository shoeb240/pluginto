<?php
namespace Accounting\Model;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ItemModelWrapper implements ServiceLocatorAwareInterface
{
    private $serviceLocator;

    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }

    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }


    public function get($vendor) {
	$model = $modelName = "\\Accounting\\Model\\Impl\\Item{$vendor}Model";
	$model = new $modelName();
	$model->setServiceLocator($this->getServiceLocator());
	return $model;
    }


}
