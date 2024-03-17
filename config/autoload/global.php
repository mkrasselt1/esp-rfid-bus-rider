<?php

use Laminas\I18n\Translator\Loader\PhpArray;
use Laminas\Session\Storage\SessionArrayStorage;
use Laminas\Session\Container;
use Laminas\Session\Validator\HttpUserAgent;
use Laminas\Session\Validator\RemoteAddr;

return [
    // Session storage configuration.
    'session_containers' => [
        Laminas\Session\Container::class,
    ],
    'session_storage' => [
        'type' => SessionArrayStorage::class
    ],
    'session_config' => [
        'name'                  => "BusRiderSession",
        'cookie_secure'         => true,
        'cookie_samesite'       => "lax",
        'cache_expire'          => 60 * 5,      // Cache expiration in Minutes
        'cookie_lifetime'       => 60 * 60 * 5,   // Session cookie will expire in 5 hour.
        'remember_me_seconds'   => 60 * 60 * 24 * 7,   // How long User Shall be Logged in
        'gc_maxlifetime'        => 60 * 60 * 24 * 30, // How long to store session data on server (for 1 month).        
    ],
    'session_manager' => [
        'config' => [
            'class' => Laminas\Session\Config\SessionConfig::class,
            'options' => [
                'name' => 'mm3bb',
            ],
        ],
        // Session validators (used for security).
        'validators' => [
            RemoteAddr::class,
            HttpUserAgent::class,
        ]
    ],
    'session_containers' => [
        Container::class,
    ],
    'translator' => [
        'locale' => 'de_DE',
        'translation_file_patterns' => [
            [
                'type'     => PhpArray::class,
                'base_dir' => getcwd() .  '/languages',
                'pattern'  => '%s.php',
            ],
        ],
    ],
];