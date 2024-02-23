<?php

namespace Site\Service;

use Laminas\Session\Container;
use Laminas\View\Helper\Url;
use User\Service\UserManager;

/**
 * This service is responsible for determining which items should be in the main menu.
 * The items may be different depending on whether the user is authenticated or not.
 */
class NavManager
{
    /**
     * UserManager
     * @var UserManager
     */
    private $userManager;

    /**
     * Url view helper.
     * @var Url
     */
    private $urlHelper;

    /**
     * Right in Session Container
     * @var Container
     */
    private $sessionContainer;

    /**
     * Right in Session Container
     * @var string
     */
    private $route;

    /**
     * Constructs the service.
     */
    public function __construct(Url $urlHelper, Container $sessionContainer, string $route)
    {
        $this->urlHelper = $urlHelper;
        $this->sessionContainer = $sessionContainer;
        $this->route = $route;
    }

    /**
     * This method returns menu items depending on whether user has logged in or not.
     */
    public function getMenuItems()
    {
        $url = $this->urlHelper;
        $items = [];

        $items[] = [
            'id' => 'dashboard',
            'label' => 'Dashboard',
            'icon' => 'house-fill',
            'link' => $url('home'),
        ];
        $items[] = [
            'id' => 'orders',
            'icon' => 'file-earmark',
            'label' => 'Orders'
        ];
        $items[] = [
            'id' => 'products',
            'icon' => 'cart',
            'label' => 'Products'
        ];
        $items[] = [
            'id' => 'reports',
            'icon' => 'graph-up',
            'label' => 'Reports'
        ];
        $items[] = [
            'id' => 'integrations',
            'icon' => 'puzzle',
            'label' => 'Integrations'
        ];
        $items[] = [
            'id' => 'people',
            'icon' => 'people',
            'label' => 'Users',
            'dropdown' => [
                [
                    'id' => 'integrations',
                    'icon' => 'puzzle',
                    'label' => 'Integrations'
                ],
                [
                    'id' => 'integrations',
                    'icon' => 'puzzle',
                    'label' => 'Integrations'
                ]
            ]
        ];
        return $items;
    }
}
