<?php
namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;

/**
 * Controller class for All common methods used in Application module controllers
 */
class ApplicationActionController extends AbstractActionController
{
	/**
     * Entity manager object.
     *
     * @var object
     */
    protected $_objectManager;

    /**
     * Get doctrine EntityManager object
     *
     * @return object EntityManager
     */
    protected function getObjectManager()
    {
        if (!$this->_objectManager) {
            $this->_objectManager = $this->getServiceLocator()->get('Doctrine\ORM\EntityManager');
        }

        return $this->_objectManager;
    }

}