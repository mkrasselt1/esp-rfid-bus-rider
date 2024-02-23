<?php

namespace Site;

use Laminas\EventManager\Event;
use Laminas\Http\Header\Authorization;
use Laminas\Http\Request;
use Laminas\Mvc\MvcEvent;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Session\SessionManager;

class Module
{
    const VERSION = '1.0.0';
    /**
     * This method is called once the MVC bootstrapping is complete and allows
     * to register event listeners. 
     */

    public function onBootstrap(MvcEvent $event)
    {
        // Get event manager.
        $eventManager = $event->getApplication()->getEventManager();
        $sharedEventManager = $eventManager->getSharedManager();
        // Register the event listener method. 
        $sharedEventManager->attach(
            AbstractActionController::class,
            MvcEvent::EVENT_DISPATCH,
            [$this, 'onDispatch'],
            100
        );


        //Attach render errors
        $eventManager->attach(MvcEvent::EVENT_RENDER_ERROR, function ($e) {
            if ($e->getParam('exception')) {
                $this->exception($e); //Custom error render function.
            }
        });
        //Attach dispatch errors
        $eventManager->attach(MvcEvent::EVENT_DISPATCH_ERROR, function ($e) {
            if ($e->getParam('exception')) {
                $this->exception($e); //Custom error render function.
            }
        });
    }

    public function exception(MvcEvent $event)
    {
        $controller = $event->getTarget();
        if (!\is_null($event->getRouteMatch())) {
            $controllerName = $event->getRouteMatch()->getParam('controller', null);
            $controllerName = \str_replace("\\", "-", $controllerName);
            $actionNameRaw = $event->getRouteMatch()->getParam('action', null);
        } else {
            $actionNameRaw =  $controllerName = "Unknown";
        }

        /** @var Exception */
        $e = $event->getParam('exception');
        $class = get_class($e);

        // syslog(\LOG_ERR, "
        //     Controller:" . $controllerName . "->" . $actionNameRaw . "\n
        //     File:" . $e->getFile() . ":" . $e->getLine() . "\n
        //     Exception:" . $class . "\n
        //     Message:" . $e->getMessage() . "\n
        //     Stacktrace:" . $e->getTraceAsString() . "\n");

        if (defined("$class::BusRiderCustomException")) {
            $path = explode('\\', $class);
            return $controller->redirect()->toRoute('error', [
                "action" => \array_pop($path),
                "element" => $e->element,
            ]);
        } else {
            echo "<span style='font-family: courier new; padding: 2px 5px; background:red; color: white;'> " . $e->getMessage() . '</span><br/>';
            //echo "<pre>" . $e->getTraceAsString() . '</pre>' ;
        }
        return false;
    }

    /**
     * Event listener method for the 'Dispatch' event. We listen to the Dispatch
     * event to call the access filter. The access filter allows to determine if
     * the current visitor is allowed to see the page or not. If he/she
     * is not authorized and is not allowed to see the page, we redirect the user 
     * to the login page.
     */
    public function onDispatch(MvcEvent $event)
    {


        // Get controller and action to which the HTTP request was dispatched.
        $controller = $event->getTarget();
        $controllerName = $event->getRouteMatch()->getParam('controller', null);
        $actionName_raw = $event->getRouteMatch()->getParam('action', null);

        // Convert dash-style action name to camel-case.
        $actionName = str_replace('-', '', lcfirst(ucwords($actionName_raw, '-')));

        //getAdditionalParameter
        $route = $event->getRouteMatch()->getMatchedRouteName();
        // $param = $event->getRouteMatch()->getParam('id', null);

        $serviceManager = $event->getApplication()->getServiceManager();
        // Get the instance of AuthManager service.
        $sessionManager = $serviceManager->get(SessionManager::class);
        return; //TODO add Authorisation
        // $this->forgetInvalidSession($sessionManager);

        // /** @var AuthManager */
        // $authManager = $serviceManager->get(AuthManager::class);

        // /** @var AuthenticationService */
        // $authService = $serviceManager->get(AuthenticationService::class);

        // /** @var AuthAdapter */
        // $authAdapter = $authService->getAdapter();


        // /** @var PhpEnvironmentRequest */
        // $request = $event->getRequest();
        // $token = $request->getQuery('api_key', null);
        // if (!is_null($token)) {
        //     $authAdapter->setUsername($token);
        //     $authAdapter->setPassword("");
        //     $result = $authService->authenticate();
        //     if ($result->getCode() == Result::SUCCESS) {
        //         $sessionContainer = $serviceManager->get(Container::class);
        //         $sessionContainer->auth = AuthManager::AUTH_TYPE_TOKEN;
        //         return;
        //     }
        // }

        // // Execute the access filter on every controller except AuthController
        // // (to avoid infinite redirect).
        // if (
        //     $controllerName != AuthController::class &&
        //     !$authManager->filterAccess($controllerName, $actionName, $route, $this->getBearerToken($event))
        // ) {

        //     // Remember the URL of the page the user tried to access. We will
        //     // redirect the user to that URL after successful login.
        //     $uri = self::getHttpRequest($event)->getUri();
        //     // Make the URL relative (remove scheme, user info, host name and port)
        //     // to avoid redirecting to other domain by a malicious user.
        //     $uri->setScheme(null)
        //         ->setHost(null)
        //         ->setPort(null)
        //         ->setUserInfo(null);
        //     $redirectUrl = $uri->toString();
        //     // Redirect the user to the "Login" page.
        //     if (substr($event->getRouteMatch()->getMatchedRouteName(), 0, 3) == "API") {
        //         return $controller->redirect()->toRoute('API_auth', ["action" => 'login']);
        //     }
        //     return $controller->redirect()->toRoute(
        //         'login',
        //         [],
        //         ['query' => ['redirectUrl' => $redirectUrl]]
        //     );
        // }
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

    /**
     * get access token from header
     */
    protected function getBearerToken(Event $event)
    {
        /** @var Authorization */
        $headers = $this->getHttpRequest($event)->getHeaders("Authorization");
        // HEADER: Get the access token from the header
        if ($headers && !is_null($headers) && !empty($headers->getFieldValue())) {
            if (preg_match('/Bearer\s(\S+)/', $headers->getFieldValue(), $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

    private static function getHttpRequest(MvcEvent $event): Request
    {
        return $event->getApplication()->getRequest();
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getAutoloaderConfig()
    {
        return ['Laminas\Loader\StandardAutoloader' => ['namespaces' => [__NAMESPACE__ => __DIR__ . '/src/',],],];
    }
}
