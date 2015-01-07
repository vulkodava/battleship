<?php
return array(
    'doctrine' => array(
        'driver' => array(
            'application_entities' => array(
                'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'cache' => 'array',
                'paths' => array(__DIR__ . '/../../module/Battleship/src/Battleship/Entity')
            ),
            'application_repositories' => array(
                'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'cache' => 'array',
                'paths' => array(__DIR__ . '/../../module/Battleship/src/Battleship/Repository')
            ),
            'orm_default' => array(
                'drivers' => array(
                    'Battleship\Entity'     => 'application_entities',
                    'Battleship\Repository' => 'application_repositories'
                )
            ),
        ),
        'connection' => array(
            'orm_default' => array(
                'driverClass' => 'Doctrine\DBAL\Driver\PDOMySql\Driver',
                'params' => array(
                    'host' => 'localhost',
                    'port' => '3306',
                    'user' => 'zend_battleship',
                    'password' => 'zend_battleship',
                    'dbname' => 'zend_battleship2',
                    'driverOptions' => array(
                        1002 => 'SET NAMES utf8'
                    )
                )
            )
        )
    )
);
