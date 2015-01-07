<?php
return array(
    'router' => array(
        'routes' => array(
            'battleship' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/battleship',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Battleship\Controller',
                        'controller' => 'Index',
                        'action' => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'default' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/[:controller][/:action][/][:cheat]',
                            'constraints' => array(
                                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ),
                            'defaults' => array('cheat' => 0),
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
    'translator' => array(
        'locale' => 'en_US',
        'translation_file_patterns' => array(
            array(
                'type' => 'gettext',
                'base_dir' => __DIR__ . '/../language',
                'pattern' => '%s.mo',
            ),
        ),
    ),
    'controllers' => array(
        'invokables' => array(
            'Battleship\Controller\Index' => 'Battleship\Controller\IndexController',
        ),
    ),
    'view_manager' => array(
        'display_not_found_reason' => true,
        'display_exceptions' => true,
        'doctype' => 'HTML5',
        'not_found_template' => 'error/404',
        'exception_template' => 'error/index',
        'template_map' => array(
            'layout/layout' => __DIR__ . '/../view/layout/layout.phtml',
            'battleship/index/index' => __DIR__ . '/../view/battleship/index/index.phtml',
            'error/404' => __DIR__ . '/../view/error/404.phtml',
            'error/index' => __DIR__ . '/../view/error/index.phtml',
        ),
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
    ),
    // Placeholder for console routes
    'console' => array(
        'router' => array(
            'routes' => array(
                'battleship' => array(
                    'options' => array(
                        'route' => 'game start',
                        'defaults' => array(
                            '__NAMESPACE__' => 'Battleship\Controller',
                            'controller' => 'Index',
                            'action' => 'console',
                        ),
                    ),
                ),
                'battleship-fire' => array(
                    'options' => array(
                        'route' => 'game fire <id> <coordinates> <cheat>',
                        'defaults' => array(
                            '__NAMESPACE__' => 'Battleship\Controller',
                            'controller' => 'Index',
                            'action' => 'console',
                        ),
                    ),
                ),
            )
        ),
    ),
    'doctrine' => array(
        'driver' => array(
            'battleship_entities' => array(
                'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'cache' => 'array',
                'paths' => array(__DIR__ . '/../src/Battleship/Entity')
            ),

            'orm_default' => array(
                'drivers' => array(
                    'Battleship\Entity' => 'battleship_entities'
                )
            )
        )
    ),

    'php-debug-bar' => array(

        // Enables/disables PHP Debug Bar
        'enabled' => true,

        // ServiceManager keys to inject collectors
        // http://phpdebugbar.com/docs/data-collectors.html
        'collectors' => array(),

        // ServiceManager key to inject storage
        // http://phpdebugbar.com/docs/storage.html
        'storage' => null,
    ),
);
