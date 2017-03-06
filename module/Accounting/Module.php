<?php
namespace Accounting;

use Zend\Session\Config\SessionConfig;
use Zend\Session\SessionManager;
use Zend\Session\Container;
use Zend\EventManager\EventInterface;

class Module
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }

    public function onBootstrap(EventInterface $evm)
    {
        $config = $evm->getApplication()
                      ->getServiceManager()
                      ->get('Configuration');

        $sessionConfig = new SessionConfig();
        $sessionConfig->setOptions($config['session']);
        $sessionManager = new SessionManager($sessionConfig);
        $sessionManager->start();

        /**
         * Optional: If you later want to use namespaces, you can already store the 
         * Manager in the shared (static) Container (=namespace) field
         */
        Container::setDefaultManager($sessionManager);
    }
}
