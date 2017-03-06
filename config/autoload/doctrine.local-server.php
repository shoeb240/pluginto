<?php
return array(
    'doctrine' => array(
      'connection' => array(
        'orm_default' => array(
           'driverClass' =>'Doctrine\DBAL\Driver\PDOMySql\Driver',
               'params' => array(
                'driver'   => 'pdo_mysql',
                'user'     => 'shoeb',
                'password' => 'sh!@ab#$12',//Pluginto123#$+
                'dbname'   => 'admin_pluginto',
            ),
            ),
      ),
    ),
);