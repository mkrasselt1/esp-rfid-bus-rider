<?php

namespace Rider\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Doctrine\ORM\EntityManager;
use Laminas\Http\Request;
use Rider\Entity\BusRoute;
use Rider\Entity\BusStop;
use Rider\Form\BusRouteAddForm;
use Rider\Form\BusRouteEditForm;
use Rider\Form\BusStopAddForm;
use Rider\Form\DeleteForm;

class BusRouteController extends AbstractActionController
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
            "busRoutes" => $this->entityManager->getRepository(BusRoute::class)->findAll()
        ];
    }

    public function addAction()
    {
        $newBusRoute = new BusRoute();
        $form = new BusRouteAddForm($this->entityManager, "add-busRoute");
        $form->bind($newBusRoute);

        // Check if user has submitted the form
        if ($this->getHTTPRequest()->isPost()) {

            // Fill in the form with POST data
            $form->setData($this->params()->fromPost());
            // Validate form
            if ($form->isValid()) {

                $this->entityManager->persist($newBusRoute);
                $this->entityManager->flush();
                $this->flashMessenger()->addSuccessMessage('bus route  added successfully');
                return $this->redirect()->toRoute('routes', ['action' => 'index']);
            }
        }
        return [
            "form" => $form
        ];
    }

    public function addStopAction()
    {
        $busRouteId = (int) $this->params()->fromRoute('id', 0);
        /** @var busRoute */
        $busRoute = $this->entityManager->find(BusRoute::class, $busRouteId);
        
        $newBusStop = new BusStop();
        $newBusStop->setBusRoute($busRoute);
        $form = new BusStopAddForm($this->entityManager, "add-busStop");
        $form->bind($newBusStop);

        // Check if user has submitted the form
        if ($this->getHTTPRequest()->isPost()) {

            // Fill in the form with POST data
            $form->setData($this->params()->fromPost());
            // Validate form
            if ($form->isValid()) { 
                $this->entityManager->persist($newBusStop);
                $this->entityManager->flush();
                $this->flashMessenger()->addSuccessMessage('bus stop added successfully');
                return $this->redirect()->toRoute('routes', ['action' => 'index']);
            }
        }
        return [
            "form" => $form
        ];
    }

    public function editAction()
    {
        $busRouteId = (int) $this->params()->fromRoute('id', 0);
        /** @var busRoute */
        $busRoute = $this->entityManager->find(BusRoute::class, $busRouteId);

        $form = new BusRouteEditForm($this->entityManager, "edit-busRoute");
        $form->bind($busRoute);

        // Check if user has submitted the form
        if ($this->getHTTPRequest()->isPost()) {

            // Fill in the form with POST data
            $form->setData($this->params()->fromPost());
            // Validate form
            if ($form->isValid()) {

                $this->entityManager->flush();
                $this->flashMessenger()->addSuccessMessage('busRoute changed successfully');
                return $this->redirect()->toRoute('routes', ['action' => 'index']);
            }
        }
        return [
            "form" => $form
        ];
    }

    public function deleteAction()
    {
        $busRouteId = (int) $this->params()->fromRoute('id', 0);
        /** @var busRoute */
        $busRoute = $this->entityManager->find(busRoute::class, $busRouteId);

        $form = new DeleteForm();

        // Check if user has submitted the form
        if ($this->getHTTPRequest()->isPost()) {

            // Fill in the form with POST data
            $form->setData($this->params()->fromPost());
            // Validate form
            if ($form->isValid()) {
                $busRoute->setStatus(BusRoute::STATUS_RETIRED);
                $this->entityManager->flush();
                $this->flashMessenger()->addSuccessMessage('busRoute deleted successfully');
                return $this->redirect()->toRoute('busRoute', ['action' => 'index']);
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
