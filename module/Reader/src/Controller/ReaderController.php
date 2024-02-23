<?php

namespace Reader\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Doctrine\ORM\EntityManager;
use Laminas\Http\Request;

class ReaderController extends AbstractActionController
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
        $this->layout()->setTemplate('layout/dashboard');
        return [        
        ];
    }
    
    private function getHTTPRequest(): Request
    {
        return $this->getRequest();
    }
}
