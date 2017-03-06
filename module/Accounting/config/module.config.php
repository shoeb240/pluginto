<?php
return array(
	'controllers' => array(
		'invokables' => array(
			'Accounting\Controller\Qb' => 'Accounting\Controller\QbController',
		),
	),

    // session - http://stackoverflow.com/a/12776246
    'session' => array(
        'remember_me_seconds' => 2419200,
        'use_cookies' => true,
        'cookie_httponly' => true,
    ),

    // The following section is new and should be added to your file
    'router' => array(
         'routes' => array(
            'qb' => array(
                'type'    => 'segment',
                'options' => array(
                    'route'    => '/qb/:action[/:id][/:returnurl][/:token]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ),
                    'defaults' => array(
                        'controller' => 'Accounting\Controller\Qb',
                        'action'     => 'oauth'
                    ),
                ),
            ),
                    ),
                    ),

    'view_manager' => array(
        'template_path_stack' => array(
            'qb' => __DIR__ . '/../view',
        ),
    ),
);