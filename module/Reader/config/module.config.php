<?php

namespace Reader;

use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Laminas\Mvc\Controller\LazyControllerAbstractFactory;
use Laminas\Router\Http\Segment;
use Reader\Controller\ApiController;
use Reader\Controller\BackendController;
use Reader\Controller\FirmwareController;
use Reader\Controller\FlashController;
use Reader\Controller\ReaderController;
use Rider\Service\CardService;
use Rider\Service\Factory\CardServiceFactory;

$dateRegex = '((((19|20)([2468][048]|[13579][26]|0[48])|2000)-02-29|((19|20)[0-9]{2}-(0[4678]|1[02])-(0[1-9]|[12][0-9]|30)|(19|20)[0-9]{2}-(0[1359]|11)-(0[1-9]|[12][0-9]|3[01])|(19|20)[0-9]{2}-02-(0[1-9]|1[0-9]|2[0-8])))T([01][0-9]|2[0-3]):([012345][0-9]):([012345][0-9]))|[0-9]*';
return [
    'router' => [
        'routes' => [
            'api' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/api',
                    'defaults' => [
                        'controller'    => ApiController::class,
                        'action'        => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'backend' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/backend[/action-:action][/number-:number][/image-:image]',
                            'constraints' => [
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'number' => '[a-zA-Z0-9_-]*',
                                'image' => '[a-zA-Z]*',
                            ],
                            'defaults' => [
                                'controller'    => BackendController::class,
                                'action'        => 'index',
                            ],
                        ],
                    ],
                ],
            ],
            'reader' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/reader[/action-:action[/id-:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]*',
                    ],
                    'defaults' => [
                        'controller'    => ReaderController::class,
                        'action'        => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'card' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/card[/action-:action][/number-:number]',
                            'constraints' => [
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'number' => '[a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                'controller'    => ReaderController::class,
                                'action'        => 'index',
                            ],
                        ],
                    ],
                    'flash' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/flash[/action-:action][/version-:version]',
                            'constraints' => [
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'number' => '[a-zA-Z0-9_-]*',
                                'version' => '[a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                'controller'    => FlashController::class,
                                'action'        => 'index',
                            ],
                        ],
                    ],
                    'firmware' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/firmware[/action-:action][/version-:version]',
                            'constraints' => [
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'number' => '[a-zA-Z0-9_-]*',
                                'version' => '[a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                'controller'    => FirmwareController::class,
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
            ApiController::class => LazyControllerAbstractFactory::class,
            BackendController::class => LazyControllerAbstractFactory::class,
            FlashController::class => LazyControllerAbstractFactory::class,
            FirmwareController::class => LazyControllerAbstractFactory::class,
            ReaderController::class => LazyControllerAbstractFactory::class
        ],
    ],
    'service_manager' => [
        'factories' => [
            CardService::class => CardServiceFactory::class,
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
