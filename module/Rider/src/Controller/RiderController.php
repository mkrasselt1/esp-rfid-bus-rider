<?php

namespace Rider\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Doctrine\ORM\EntityManager;
use Laminas\Http\Request;
use Rider\Entity\Company;
use Rider\Entity\Employee;
use Rider\Form\EmployeeAddForm;
use Rider\Form\EmployeeEditForm;
use Rider\Form\DeleteForm;

class RiderController extends AbstractActionController
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
            "riders" => $this->entityManager->getRepository(Employee::class)->findAll()
        ];
    }

    public function addAction()
    {
        //get company preset
        $CompanyId = (int) $this->params()->fromRoute('id', 0);
        /** @var Company */
        $Company = $this->entityManager->find(Company::class, $CompanyId);

        $newEmployee = new Employee();
        $newEmployee->setCompany($Company);
        $form = new EmployeeAddForm($this->entityManager, "add-employee");
        $form->bind($newEmployee);

        // Check if user has submitted the form
        if ($this->getHTTPRequest()->isPost()) {
            // Fill in the form with POST data
            $form->setData($this->params()->fromPost());
            // Validate form
            if ($form->isValid()) {
                $this->entityManager->persist($newEmployee);
                $this->entityManager->flush();
                $this->flashMessenger()->addSuccessMessage('rider added successfully');
                return $this->redirect()->toRoute('rider/rider', ['action' => 'index']);
            }
        }
        return [
            "form" => $form
        ];
    }

    public function editAction()
    {
        $EmployeeId = (int) $this->params()->fromRoute('id', 0);
        /** @var Employee */
        $Employee = $this->entityManager->find(Employee::class, $EmployeeId);

        $form = new EmployeeEditForm(
            entityManager: $this->entityManager,
            employee: $Employee,
        );
        $form->bind($Employee);

        // Check if user has submitted the form
        if ($this->getHTTPRequest()->isPost()) {

            // Fill in the form with POST data
            $form->setData($data = $this->params()->fromPost());
            // Validate form
            if ($form->isValid()) {
                $this->entityManager->flush();
                $this->flashMessenger()->addSuccessMessage('rider changed successfully');
                return $this->redirect()->toRoute('rider', ['action' => 'index']);
            }
        }
        return [
            "form" => $form
        ];
    }

    public function deleteAction()
    {
        $EmployeeId = (int) $this->params()->fromRoute('id', 0);
        /** @var Employee */
        $Employee = $this->entityManager->find(Employee::class, $EmployeeId);

        $form = new DeleteForm();

        // Check if user has submitted the form
        if ($this->getHTTPRequest()->isPost()) {

            // Fill in the form with POST data
            $form->setData($this->params()->fromPost());
            // Validate form
            if ($form->isValid()) {
                $Employee->setStatus(Employee::STATUS_RETIRED);
                $this->entityManager->flush();
                $this->flashMessenger()->addSuccessMessage('rider deleted successfully');
                return $this->redirect()->toRoute('rider', ['action' => 'index']);
            }
        }
        return [
            "form" => $form
        ];
    }

    private function getHTTPRequest(): Request
    {
        return $this->getRequest();
    }
}
