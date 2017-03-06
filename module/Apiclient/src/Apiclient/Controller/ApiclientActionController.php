<?php
namespace Apiclient\Controller;

use Zend\Session\Container;

use Zend\Mvc\Controller\AbstractActionController;

/**
 * Controller class for All common methods used in Apiclient module controllers
 */
class ApiclientActionController extends AbstractActionController
{
	/**
     * Entity manager object.
     *
     * @var object
     */
    protected $_objectManager;

    protected $_userId;
    
    protected $_accessToken;
    
    public function __construct() {
        $login = new Container('login');
        $this->_userId = $login->userId;
        $this->_accessToken = $login->accessToken;
    }  
    
    public function getUserId()
    {
        return $this->_userId;
    }
    
    public function getAccessToken()
    {
        return $this->_accessToken;
    }
    
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