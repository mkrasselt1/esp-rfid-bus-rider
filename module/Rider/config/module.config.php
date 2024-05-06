<?php

namespace Rider;

use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Laminas\Mvc\Controller\LazyControllerAbstractFactory;
use Laminas\Router\Http\Segment;
use Rider\Controller\BusRouteController;
use Rider\Controller\CardController;
use Rider\Controller\CompanyController;
use Rider\Controller\RiderController;

$dateRegex = '((((19|20)([2468][048]|[13579][26]|0[48])|2000)-02-29|((19|20)[0-9]{2}-(0[4678]|1[02])-(0[1-9]|[12][0-9]|30)|(19|20)[0-9]{2}-(0[1359]|11)-(0[1-9]|[12][0-9]|3[01])|(19|20)[0-9]{2}-02-(0[1-9]|1[0-9]|2[0-8])))T([01][0-9]|2[0-3]):([012345][0-9]):([012345][0-9]))|[0-9]*';
return [
    'router' => [
        'routes' => [
            'company' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/company[/action-:action[/id-:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]*',
                    ],
                    'defaults' => [
                        'controller'    => CompanyController::class,
                        'action'        => 'index',
                    ],
                ],
            ],
            'routes' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/routes[/action-:action[/id-:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]*',
                    ],
                    'defaults' => [
                        'controller'    => BusRouteController::class,
                        'action'        => 'index',
                    ],
                ],
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
            'rider' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/rider',
                    'defaults' => [
                        'controller'    => RiderController::class,
                        'action'        => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'card' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/card[/action-:action[/id-:id]]',
                            'constraints' => [
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'id' => '[a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                'controller'    => CardController::class,
                                'action'        => 'index',
                            ],
                        ],
                    ],
                    'rider' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/person[/action-:action[/id-:id]]',
                            'constraints' => [
                                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'id'     => '[0-9]*',
                            ],
                            'defaults' => [
                                'controller'    => RiderController::class,
                                'action'        => 'index',
                            ],
                        ],
                    ],
                ]
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            CompanyController::class => LazyControllerAbstractFactory::class,
            BusRouteController::class => LazyControllerAbstractFactory::class,
            RiderController::class => LazyControllerAbstractFactory::class,
            CardController::class => LazyControllerAbstractFactory::class,
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
