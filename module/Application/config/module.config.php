<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

return array(
    'doctrine' => array(
      'driver' => array(
          'application_entities' => array(
                'class' =>'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'cache' => 'array',
                'paths' => array(__DIR__ . '/../src/Application/Entity')
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
            'home' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/',
                    'defaults' => array(
                        'controller' => 'Apiclient\Controller\User',
                        'action'     => 'index',
                    ),
                ),
            ),
            // The following is a route to simplify getting started creating
            // new controllers and actions without needing to create a new
            // module. Simply drop new controllers in, and you can access them
            // using the path /application/:controller/:action
            'application' => array(
                'type'    => 'Literal',
                'options' => array(
                    'route'    => '/application',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Application\Controller',
                        'controller'    => 'Index',
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
                            ),
                        ),
                    ),
                ),
            ),
        ),
    ),
    'service_manager' => array(
	'factories' => array(
	    'UserModel' => function($sm){
		    $model = new \Input\Model\User();
		    $model->setServiceLocator($sm);
		    return $model;
		    },
            'CompanyModel' => function($sm){
		    $model = new \Input\Model\Company();
		    $model->setServiceLocator($sm);
		    return $model;
		    },
            'CustomerModel' => function($sm){
		    $model = new \Input\Model\Customer();
		    $model->setServiceLocator($sm);
		    return $model;
		    },
            'SupplierModel' => function($sm){
		    $model = new \Input\Model\Supplier();
		    $model->setServiceLocator($sm);
		    return $model;
		    },
            'ItemModel' => function($sm){
		    $model = new \Input\Model\Item();
		    $model->setServiceLocator($sm);
		    return $model;
		    },
            'BankAccountModel' => function($sm){
		    $model = new \Input\Model\BankAccount();
		    $model->setServiceLocator($sm);
		    return $model;
		    },
	    'TransactionModel' => function($sm){
		    $model = new \Input\Model\Transaction();
		    $model->setServiceLocator($sm);
		    return $model;
		    },
	    'TransactionLineModel' => function($sm){
		    $model = new \Input\Model\TransactionLine();
		    $model->setServiceLocator($sm);
		    return $model;
		    },
            'AccountModel' => function($sm){
		    $model = new \Input\Model\Account();
		    $model->setServiceLocator($sm);
		    return $model;
		    },
            'ReportModel' => function($sm){
		    $model = new \Input\Model\Report();
		    $model->setServiceLocator($sm);
		    return $model;
		    },
            'TaxModel' => function($sm){
		    $model = new \Input\Model\Tax();
		    $model->setServiceLocator($sm);
		    return $model;
		    },
            'TaxRateModel' => function($sm){
		    $model = new \Input\Model\TaxRate();
		    $model->setServiceLocator($sm);
		    return $model;
		    },
            'TaxAgencyModel' => function($sm){
		    $model = new \Input\Model\TaxAgency();
		    $model->setServiceLocator($sm);
		    return $model;
		    },
	    'ServiceModel' => function($sm){
		    $model = new \Application\Model\Service();
		    $model->setServiceLocator($sm);
		    return $model;
		    },
	    'ServiceLineModel' => function($sm){
		    $model = new \Application\Model\ServiceLine();
		    $model->setServiceLocator($sm);
		    return $model;
		    },
            'AccountingModelWrapper' => function($sm){
    		    $model = new \Accounting\Model\AccountingModelWrapper();
		    $model->setServiceLocator($sm);
		    return $model;
		    },
	    'CompanyModelWrapper' => function($sm){
		    $model = new \Accounting\Model\CompanyModelWrapper();
		    $model->setServiceLocator($sm);
		    return $model;
		    },
	    'CustomerModelWrapper' => function($sm){
		    $model = new \Accounting\Model\CustomerModelWrapper();
		    $model->setServiceLocator($sm);
		    return $model;
		    },
	    'SupplierModelWrapper' => function($sm){
		    $model = new \Accounting\Model\SupplierModelWrapper();
		    $model->setServiceLocator($sm);
		    return $model;
		    },
	    'ItemModelWrapper' => function($sm){
		    $model = new \Accounting\Model\ItemModelWrapper();
		    $model->setServiceLocator($sm);
		    return $model;
		    },
	    'BankAccountModelWrapper' => function($sm){
		    $model = new \Accounting\Model\BankAccountModelWrapper();
		    $model->setServiceLocator($sm);
		    return $model;
		    },
            'AccountModelWrapper' => function($sm){
    		    $model = new \Accounting\Model\AccountModelWrapper();
		    $model->setServiceLocator($sm);
		    return $model;
		    },
            'ReportModelWrapper' => function($sm){
    		    $model = new \Accounting\Model\ReportModelWrapper();
		    $model->setServiceLocator($sm);
		    return $model;
		    },
            'TaxModelWrapper' => function($sm){
    		    $model = new \Accounting\Model\TaxModelWrapper();
		    $model->setServiceLocator($sm);
		    return $model;
		    },
            'TaxAgencyModelWrapper' => function($sm){
    		    $model = new \Accounting\Model\TaxAgencyModelWrapper();
		    $model->setServiceLocator($sm);
		    return $model;
		    },
	    'LogModel' => function($sm){
		    $model = new \Input\Model\Log();
		    $model->setServiceLocator($sm);
		    return $model;
		    },
	    'GlobalSettings' => function($sm){
		    $model = new \Application\Model\GlobalSettings();
		    return $model;
		    },
	),
        'abstract_factories' => array(
            'Zend\Cache\Service\StorageCacheAbstractServiceFactory',
            'Zend\Log\LoggerAbstractServiceFactory',
        ),
        'aliases' => array(
            'translator' => 'MvcTranslator',
        ),
    ),
    'translator' => array(
        'locale' => 'en_US',
        'translation_file_patterns' => array(
            array(
                'type'     => 'gettext',
                'base_dir' => __DIR__ . '/../language',
                'pattern'  => '%s.mo',
            ),
        ),
    ),
    'controllers' => array(
        'invokables' => array(
            'Application\Controller\Index' => 'Application\Controller\IndexController',
            'Application\Controller\Company' => 'Application\Controller\CompanyController',
            'Application\Controller\Transaction' => 'Application\Controller\TransactionController'
        ),
    ),
    'view_manager' => array(
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'template_map' => array(
            'layout/layout'           => __DIR__ . '/../view/layout/layout.phtml',
            'application/index/index' => __DIR__ . '/../view/application/index/index.phtml',
            'error/404'               => __DIR__ . '/../view/error/404.phtml',
            'error/index'             => __DIR__ . '/../view/error/index.phtml',
        ),
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
    ),
    // Placeholder for console routes
    'console' => array(
        'router' => array(
            'routes' => array(
            ),
        ),
    ),
);
