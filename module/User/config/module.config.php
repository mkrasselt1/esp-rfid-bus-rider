<?php

namespace User;

use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Laminas\Mvc\Controller\LazyControllerAbstractFactory;
use Laminas\Router\Http\Segment;

return [
    'router' => [
        'routes' => [
            'user' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/user[/action-:action[/id-:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]*',
                    ],
                    'defaults' => [
                        'controller'    => UserController::class,
                        'action'        => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'register' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/register[/action-:action[/id-:id]]',
                            'constraints' => [
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'id' => '[a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                'controller'    => RegisterController::class,
                                'action'        => 'index',
                            ],
                        ],
                    ],
                ]
            ],
            // 'auth' => [
            //     'type'    => Segment::class,
            //     'options' => [
            //         'route'    => '/auth[/:action]',
            //         'constraints' => [
            //             'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
            //         ],
            //         'defaults' => [
            //             'controller'    => AuthController::class,
            //             'action'        => 'index',
            //         ],
            //     ],
            // ],
        ],
    ],
    'controllers' => [
        'factories' => [
            UserController::class => LazyControllerAbstractFactory::class,
        ],
    ],
    'service_manager' => [
        'factories' => [
            // BackendLogManager::class => BackendLogManagerFactory::class,
        ],
    ],

    // The 'access_filter' key is used by the Machine module to restrict or permit
    // access to certain controller actions for unauthorized visitors.
    'access_filter' => [
        'controllers' => [
            // IndexController::class => [
            //     ['actions' => ['index', 'sales', 'details', 'simplestatistics', 'map'], 'allow' => '@'],
            // ],

        ]
    ],
    'doctrine' => [
        'driver' => [
            __NAMESPACE__ . '_driver' => [
                'class' => AnnotationDriver::class,
                'cache' => 'array',
                'paths' => [__DIR__ . '/../src/Entity']
            ],
            'orm_default' => [
                'drivers' => [
                    __NAMESPACE__ . '\Entity' => __NAMESPACE__ . '_driver'
                ]
            ]
        ]
    ],
    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
        'strategies' => [
            'ViewJsonStrategy',
        ],
    ],
];
