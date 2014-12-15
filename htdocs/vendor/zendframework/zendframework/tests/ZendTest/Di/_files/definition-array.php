<?php return array(
    'My\\DbAdapter' =>
        array(
            'superTypes' =>
                array(),
            'instantiator' => '__construct',
            'methods' =>
                array(
                    '__construct' =>
                        array(
                            'username' => null,
                            'password' => null,
                        ),
                ),
        ),
    'My\\EntityA' =>
        array(
            'supertypes' =>
                array(),
            'instantiator' => null,
            'methods' =>
                array(),
        ),
    'My\\Mapper' =>
        array(
            'supertypes' =>
                array(
                    0 => 'ArrayObject',
                ),
            'instantiator' => '__construct',
            'methods' =>
                array(
                    'setDbAdapter' =>
                        array(
                            'dbAdapter' => 'My\\DbAdapter',
                        ),
                ),
        ),
    'My\\RepositoryA' =>
        array(
            'superTypes' =>
                array(),
            'instantiator' => '__construct',
            'injectionMethods' =>
                array(
                    'setMapper' =>
                        array(
                            'mapper' => 'My\\Mapper',
                        ),
                ),
        ),
    'My\\RepositoryB' =>
        array(
            'superTypes' =>
                array(
                    0 => 'My\\RepositoryA',
                ),
            'instantiator' => null,
            'Methods' =>
                array(),
        ),
);
