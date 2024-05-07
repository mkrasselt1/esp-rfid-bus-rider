<?php

namespace Rider\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Doctrine\ORM\EntityManager;
use Laminas\Http\Request;
use Rider\Entity\Company;
use Rider\Entity\Employee;
use Rider\Entity\Ride;
use Rider\Form\EmployeeAddForm;
use Rider\Form\EmployeeEditForm;
use Rider\Form\DeleteForm;

class RideController extends AbstractActionController
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
        return [
            "rides" => $this->entityManager->getRepository(Ride::class)->findAll()
        ];
    }

    public function companyAction()
    {
        //get company preset
        $CompanyId = (int) $this->params()->fromRoute('id', 0);
        /** @var Company */
        $Company = $this->entityManager->find(Company::class, $CompanyId);

        return [
            "company" => $Company,
            "rides" => $Company->getEmployees()->map(fn (Employee $e) => $e->getRides())
        ];
    }

    private function getHTTPRequest(): Request
    {
        return $this->getRequest();
    }
}
