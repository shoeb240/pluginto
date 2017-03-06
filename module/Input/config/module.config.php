<?php 
return array(
    // doctrine
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

    // controllers
    'controllers' => array(
           'invokables' => array(
                   'Input\Controller\Input' => 'Input\Controller\InputController',
                   'Input\Controller\Access' => 'Input\Controller\AccessController',
                   'Input\Controller\Sync' => 'Input\Controller\SyncController',
                   'Input\Controller\Report' => 'Input\Controller\ReportController',
           ),
    ),

    // router
    'router' => array(
         'routes' => array(
            'input-user' => array(
                'type'    => 'segment',
                'options' => array(
                    'route'    => '/input/:input-type',
                    'constraints' => array(
                        'input-type' => 'user'
                    ),
                    'defaults' => array(
                        'controller' => 'Input\Controller\Input'
                    ),
                ),
            ),
            'input' => array(
                'type'    => 'segment',
                'options' => array(
                    'route'    => '/input/:input-type[/:id]',
                    'constraints' => array(
                        'input-type' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]*',
                    ),
                    'defaults' => array(
                        'controller' => 'Input\Controller\Input'
                    ),
                ),
            ),
            'access-action' => array(
                'type'    => 'segment',
                'options' => array(
                    'route'    => '/access/:action[/:id]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]*',
                    ),
                    'defaults' => array(
                        'controller' => 'Input\Controller\Access',
                    ),
                ),
            ),
            'sync-action' => array(
                'type'    => 'segment',
                'options' => array(
                    'route'    => '/sync/:action[/:user_id]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ),
                    'defaults' => array(
                        'controller' => 'Input\Controller\Sync',
                        'module' => 'Input'
                    ),
                ),
            ),
            'report' => array(
                'type'    => 'segment',
                'options' => array(
                    'route'    => '/report/:action[/:id]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]*',
                    ),
                    'defaults' => array(
                        'controller' => 'Input\Controller\Report',
                        'action' => 'report'
                    ),
                ),
            ),
        ),
    ),

    // view manager
    'view_manager' => array(
    	'strategies' => array(
    		'ViewJsonStrategy'
    	),
    ),
);