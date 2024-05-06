<?php

namespace Site;

use Laminas\I18n\Translator\Translator;
use Laminas\I18n\Translator\TranslatorServiceFactory;
use Site\Controller\IndexController;
use Laminas\Mvc\Controller\LazyControllerAbstractFactory;
use Laminas\Mvc\I18n\Translator as I18nTranslator;
use Laminas\Mvc\Service\ViewHelperManagerFactory;
use Laminas\Router\Http\Literal;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Site\Service\Factory\NavManagerFactory;
use Site\Service\NavManager;
use Site\View\Helper\CompanyMenu;
use Site\View\Helper\Factory\CompanyMenuFactory;
use Site\View\Helper\Factory\MenuFactory;
use Site\View\Helper\Menu;
use Site\View\Helper\FormErrorMessage;

return [
    'router' => [
        'routes' => [
            'home' => [
                'type' => Literal::class,
                'options' => [
                    'route'    => '/',
                    'defaults' => [
                        'controller' => IndexController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            IndexController::class => LazyControllerAbstractFactory::class
        ],
    ],
    'service_manager' => [
        'factories' => [
            // AuthenticationService::class => AuthenticationServiceFactory::class,
            NavManager::class => NavManagerFactory::class,
            // MailSendManager::class => MailSendManagerFactory::class,
            \Laminas\I18n\Translator\TranslatorInterface::class => \Laminas\I18n\Translator\TranslatorServiceFactory::class,
            Translator::class => TranslatorServiceFactory::class,
            I18nTranslator::class => TranslatorServiceFactory::class
        ]
    ],
    'doctrine' => [
        'driver' => [
            'site_entities' => [
                'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                //'cache' => 'filesystem',
                'paths' => [__DIR__ . '/../src/Entity']
            ],

            'orm_default' => [
                'drivers' => [
                    'Site\Entity'  => 'site_entities',
                ]
            ],
        ],
    ],
    'view_helpers' => [
        'invokables' => [
            'translate' => \Laminas\I18n\View\Helper\Translate::class
        ],
        'factories' => [
            FormErrorMessage::class => InvokableFactory::class,
            CompanyMenu::class => CompanyMenuFactory::class,
            Menu::class => MenuFactory::class,
        ],
        'aliases' => [
            'mainMenu' => Menu::class,
            'FormErrorMessage' => FormErrorMessage::class,
            'CompanyMenu' => CompanyMenu::class
        ],
    ],
    'access_filter' => [
        'controllers' => [
            IndexController::class => [
                ['actions' => [
                    'index',
                ], 'allow' => '*'],
            ],
        ]
    ],
    'view_manager' => [
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'template_map' => [
            // 'layout/layout'           => __DIR__ . '/../view/layout/layout.phtml',
            'layout/layout'        => __DIR__ . '/../view/layout/dashboard.phtml',
            'layout/api'              => __DIR__ . '/../view/layout/api.phtml',
            'error/404'               => __DIR__ . '/../view/error/404.phtml',
            'error/index'             => __DIR__ . '/../view/error/index.phtml',
            'site/site/index'         => __DIR__ . '/../view/site/site/index.phtml',
        ],
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
    'view_helper_config' => [
        'flashmessenger' => [
            'message_open_format'      => '<div><ul%s><li>',
            'message_close_string'     => '</li></ul></div>',
            'message_separator_string' => '</li><li>'
        ]
    ],
];
