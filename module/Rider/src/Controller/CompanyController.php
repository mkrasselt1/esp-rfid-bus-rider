<?php

namespace Rider\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Doctrine\ORM\EntityManager;
use Laminas\Http\Request;
use Rider\Entity\Company;
use Rider\Form\CompanyAddForm;
use Rider\Form\CompanyEditForm;
use Rider\Form\DeleteForm;

class CompanyController extends AbstractActionController
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
            "companies" => $this->entityManager->getRepository(Company::class)->findAll()
        ];
    }

    public function addAction()
    {
        $newCompany = new Company();
        $form = new CompanyAddForm($this->entityManager, "add-Company");
        $form->bind($newCompany);

        // Check if user has submitted the form
        if ($this->getHTTPRequest()->isPost()) {

            // Fill in the form with POST data
            $form->setData($this->params()->fromPost());
            // Validate form
            if ($form->isValid()) {

                $this->entityManager->persist($newCompany);
                $this->entityManager->flush();
                $this->flashMessenger()->addSuccessMessage('Company added successfully');
                return $this->redirect()->toRoute('company', ['action' => 'index']);
            }
        }
        return [
            "form" => $form
        ];
    }

    public function editAction()
    {
        $companyId = (int) $this->params()->fromRoute('id', 0);
        /** @var Company */
        $company = $this->entityManager->find(Company::class, $companyId);

        $form = new CompanyEditForm($this->entityManager, "edit-Company");
        $form->bind($company);

        // Check if user has submitted the form
        if ($this->getHTTPRequest()->isPost()) {

            // Fill in the form with POST data
            $form->setData($this->params()->fromPost());
            // Validate form
            if ($form->isValid()) {

                $this->entityManager->flush();
                $this->flashMessenger()->addSuccessMessage('company changed successfully');
                return $this->redirect()->toRoute('company', ['action' => 'index']);
            }
        }
        return [
            "form" => $form
        ];
    }

    public function deleteAction()
    {
        $companyId = (int) $this->params()->fromRoute('id', 0);
        /** @var Company */
        $company = $this->entityManager->find(Company::class, $companyId);

        $form = new DeleteForm();

        // Check if user has submitted the form
        if ($this->getHTTPRequest()->isPost()) {

            // Fill in the form with POST data
            $form->setData($this->params()->fromPost());
            // Validate form
            if ($form->isValid()) {
                $company->setStatus(Company::STATUS_RETIRED);
                $this->entityManager->flush();
                $this->flashMessenger()->addSuccessMessage('company deleted successfully');
                return $this->redirect()->toRoute('company', ['action' => 'index']);
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
