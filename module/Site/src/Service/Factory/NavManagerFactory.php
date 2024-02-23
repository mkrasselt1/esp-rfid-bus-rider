<?php

namespace Site\Service\Factory;

use Psr\Container\ContainerInterface;
use Laminas\Session\Container;
use Laminas\Session\SessionManager;
use Site\Service\NavManager;

/**
 * This is the factory class for NavManager service. The purpose of the factory
 * is to instantiate the service and pass it dependencies (inject dependencies).
 */
class NavManagerFactory
{
    /**
     * This method creates the NavManager service and returns its instance. 
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        // $userManager = $container->get(UserManager::class);
        $viewHelperManager = $container->get('ViewHelperManager');
        $urlHelper = $viewHelperManager->get('url');

        $sessionManager = $container->get(SessionManager::class);
        $this->forgetInvalidSession($sessionManager);
        $sessionContainer = $container->get(Container::class);

        $routeMatch = $container->get('Application')->getMvcEvent()->getRouteMatch();
        $route      = "unknown Route";
        if ($routeMatch) {
            $route      = $routeMatch->getMatchedRouteName();
        }


        return new NavManager($urlHelper, $sessionContainer, $route);
    }

    protected function forgetInvalidSession($sessionManager)
    {
        try {
            $sessionManager->start();
            return;
        } catch (\Exception $e) {
        }
        /**
         * Session validation failed: toast it and carry on.
         */
        session_unset();
    }
}
