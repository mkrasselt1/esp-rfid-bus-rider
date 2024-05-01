<?php

namespace Site\Service;

use Laminas\I18n\Translator\Translator;
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
     * Translator
     * @var Translator
     */
    private $translator;

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
    public function __construct(
        Url $urlHelper,
        Container $sessionContainer,
        string $route,
        Translator $translator
    ) {
        $this->urlHelper = $urlHelper;
        $this->sessionContainer = $sessionContainer;
        $this->route = $route;
        $this->translator = $translator;
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
            'label' => $this->translator->translate('Dashboard'),
            'icon' => 'house-fill',
            'link' => $url('home'),
        ];
        $items[] = [
            'id' => 'orders',
            'icon' => 'file-earmark',
            'label' => $this->translator->translate('orders')
        ];
        $items[] = [
            'id' => 'products',
            'icon' => 'cart',
            'label' => $this->translator->translate('products')
        ];
        $items[] = [
            'id' => 'reports',
            'icon' => 'graph-up',
            'label' => $this->translator->translate('reports')
        ];
        $items[] = [
            'id' => 'integrations',
            'icon' => 'puzzle',
            'label' => $this->translator->translate('integrations')
        ];
        $items[] = [
            'id' => 'people',
            'icon' => 'people',
            'label' => $this->translator->translate('users'),
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
