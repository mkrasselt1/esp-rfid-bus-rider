<?php

namespace Rider\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Doctrine\ORM\EntityManager;
use Laminas\Http\Request;
use Rider\Entity\Card;
use Rider\Form\CardAddForm;
use Rider\Form\CardEditForm;
use Rider\Form\DeleteForm;

class CardController extends AbstractActionController
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
            "cards" => $this->entityManager->getRepository(Card::class)->findAll()
        ];
    }

    public function addAction()
    {
        $newCard = new Card();
        $form = new CardAddForm($this->entityManager, "add-card");
        $form->bind($newCard);

        // Check if user has submitted the form
        if ($this->getHTTPRequest()->isPost()) {

            // Fill in the form with POST data
            $form->setData($this->params()->fromPost());
            // Validate form
            if ($form->isValid()) {

                $this->entityManager->persist($newCard);
                $this->entityManager->flush();
                $this->flashMessenger()->addSuccessMessage('card added successfully');
                return $this->redirect()->toRoute('card', ['action' => 'index']);
            }
        }
        return [
            "form" => $form
        ];
    }

    public function editAction()
    {
        $cardId = (int) $this->params()->fromRoute('id', 0);
        /** @var Card */
        $card = $this->entityManager->find(Card::class, $cardId);

        $form = new CardEditForm($this->entityManager, "edit-card");
        $form->bind($card);

        // Check if user has submitted the form
        if ($this->getHTTPRequest()->isPost()) {

            // Fill in the form with POST data
            $form->setData($this->params()->fromPost());
            // Validate form
            if ($form->isValid()) {

                $this->entityManager->flush();
                $this->flashMessenger()->addSuccessMessage('card changed successfully');
                return $this->redirect()->toRoute('card', ['action' => 'index']);
            }
        }
        return [
            "form" => $form
        ];
    }

    public function deleteAction()
    {
        $cardId = (int) $this->params()->fromRoute('id', 0);
        /** @var Card */
        $card = $this->entityManager->find(Card::class, $cardId);

        $form = new DeleteForm();

        // Check if user has submitted the form
        if ($this->getHTTPRequest()->isPost()) {

            // Fill in the form with POST data
            $form->setData($this->params()->fromPost());
            // Validate form
            if ($form->isValid()) {
                $card->setStatus(Card::STATUS_RETIRED);
                $this->entityManager->flush();
                $this->flashMessenger()->addSuccessMessage('card deleted successfully');
                return $this->redirect()->toRoute('card', ['action' => 'index']);
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
