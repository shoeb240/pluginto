<?php
return array(
    'doctrine' => array(
      'driver' => array(
          'application_entities' => array(
                'class' =>'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'cache' => 'array',
                'paths' => array(__DIR__ . '/../../Application/src/Application/Entity')
                ),
           'orm_default' => array(
                'drivers' => array(
                'Application\Entity' => 'application_entities'
                ),
            ),
      ),
    ),
    'router' => array(
        'routes' => array(
            'apiclient' => array(
                'type'    => 'Literal',
                'options' => array(
                    'route'    => '/apiclient',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Apiclient\Controller',
                        'controller'    => 'User',
                        'action'        => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'default' => array(
                        'type'    => 'Segment',
                        'options' => array(
                            'route'    => '/[:controller[/:action][/:id]]',
                            'constraints' => array(
                                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ),
                            'defaults' => array(
                                'action'        => 'index',
                            ),
                        ),
                    ),
                ),
            ),
        ),
    ),
    'service_manager' => array(
        'abstract_factories' => array(
            'Zend\Cache\Service\StorageCacheAbstractServiceFactory',
            'Zend\Log\LoggerAbstractServiceFactory',
        ),
        'aliases' => array(
            'translator' => 'MvcTranslator',
        ),
    ),
    'controllers' => array(
        'invokables' => array(
            'Apiclient\Controller\Transaction' => 'Apiclient\Controller\TransactionController',
            'Apiclient\Controller\Customer' => 'Apiclient\Controller\CustomerController',
            'Apiclient\Controller\Supplier' => 'Apiclient\Controller\SupplierController',
            'Apiclient\Controller\Item' => 'Apiclient\Controller\ItemController',
            'Apiclient\Controller\BankAccount' => 'Apiclient\Controller\BankAccountController',
            'Apiclient\Controller\User' => 'Apiclient\Controller\UserController',
            'Apiclient\Controller\Account' => 'Apiclient\Controller\AccountController',
            'Apiclient\Controller\Service' => 'Apiclient\Controller\ServiceController',
            'Apiclient\Controller\Tax' => 'Apiclient\Controller\TaxController',
            'Apiclient\Controller\TaxAgency' => 'Apiclient\Controller\TaxAgencyController',
            'Apiclient\Controller\TaxRate' => 'Apiclient\Controller\TaxRateController',
            'Apiclient\Controller\Report' => 'Apiclient\Controller\ReportController',
            'Apiclient\Controller\Default' => 'Apiclient\Controller\DefaultController',
            'Apiclient\Controller\Bulk' => 'Apiclient\Controller\BulkController',
            'Apiclient\Controller\Sync' => 'Apiclient\Controller\SyncController',
        ),
    ),
    'view_manager' => array(
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
    ),
);
