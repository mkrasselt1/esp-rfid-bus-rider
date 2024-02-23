<?php

namespace Rider;

use Doctrine\ORM\Mapping\Driver\AnnotationDriver;

$dateRegex = '((((19|20)([2468][048]|[13579][26]|0[48])|2000)-02-29|((19|20)[0-9]{2}-(0[4678]|1[02])-(0[1-9]|[12][0-9]|30)|(19|20)[0-9]{2}-(0[1359]|11)-(0[1-9]|[12][0-9]|3[01])|(19|20)[0-9]{2}-02-(0[1-9]|1[0-9]|2[0-8])))T([01][0-9]|2[0-3]):([012345][0-9]):([012345][0-9]))|[0-9]*';
return [
    'router' => [
        'routes' => [
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
            // 'rider' => [
            //     'type'    => Segment::class,
            //     'options' => [
            //         'route'    => '/rider',
            //         'defaults' => [
            //             'controller'    => RiderController::class,
            //             'action'        => 'index',
            //         ],
            //     ],
            //     'may_terminate' => true,
            //     'child_routes' => [
            //         'card' => [
            //             'type' => Segment::class,
            //             'options' => [
            //                 'route' => '/rider[/action-:action][/number-:number]',
            //                 'constraints' => [
            //                     'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
            //                     'number' => '[a-zA-Z0-9_-]*',
            //                     'terminal'   => '[0-9]*',
            //                     'page'  => '[0-9]*',
            //                     'size'  => '[0-9]*',
            //                 ],
            //                 'defaults' => [
            //                     'controller'    => RiderController::class,
            //                     'action'        => 'index',
            //                 ],
            //             ],
            //         ],
            //         'employee' => [
            //             'type' => Segment::class,
            //             'options' => [
            //                 'route' => '/EVA-DTS[/action-:action][/number-:number][/terminal-:terminal]',
            //                 'constraints' => [
            //                     'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
            //                     'number'     => '[0-9]*',
            //                     'terminal'   => '[0-9]*',
            //                 ],
            //                 'defaults' => [
            //                     'controller'    => RiderController::class,
            //                     'action'        => 'index',
            //                 ],
            //             ],
            //         ],
            //     ]
            // ],
        ],
    ],
    'controllers' => [
        'factories' => [
            // IndexController::class => LazyControllerAbstractFactory::class,
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
