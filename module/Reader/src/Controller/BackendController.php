<?php

namespace Reader\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Doctrine\ORM\EntityManager;
use Laminas\Http\Request;
use Laminas\View\Model\JsonModel;

class BackendController extends AbstractActionController
{
    /**
     * Constructor is used for injecting dependencies into the controller.
     */
    public function __construct(
        EntityManager $entityManager,
    ) {
        $this->entityManager = $entityManager;
    }

    /**
     * Entity manager.
     * @var EntityManager 
     */
    public $entityManager;

    public function indexAction()
    {
        $this->layout()->setTemplate('layout/api');

        // $message = json_decode(file_get_contents("php://input"), true);
        $message = $this->getHTTPRequest()->getContent();
        $message = \json_decode($this->getHTTPRequest()->getContent(), true);
        $message = [
            "login"
        ];
        return new JsonModel(
            $message
        );
    }

    private function getHTTPRequest(): Request
    {
        return $this->getRequest();
    }
}
