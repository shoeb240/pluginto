<?php
return array(
 'db' => array(
    'driver'         => 'Pdo',
     'dbn' => 'mysql:dbname=pluginto;host=localhost',
     'username' => 'root',
     'password' => '',
     'driver_options' => array(),
 ),
'service_manager' => array(
'aliases' => array(
'adapter' => 'Zend\Db\Adapter\Adapter',
),
),);