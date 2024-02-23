<?php

namespace Site\View\Helper\Factory;

use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Site\View\Helper\Menu;
use Site\Service\NavManager;

/**
 * This is the factory for Menu view helper. Its purpose is to instantiate the
 * helper and init menu items.
 */
class MenuFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $navManager = $container->get(NavManager::class);

        // Get menu items.
        $items = $navManager->getMenuItems();

        // Instantiate the helper.
        return new Menu($items);
    }
}
