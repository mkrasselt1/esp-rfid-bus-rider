<?php

namespace Rider\Form;

use Doctrine\ORM\EntityManager;
use Laminas\Form\Element\Button;
use Laminas\Form\Element\Csrf;
use Laminas\Form\Form;
use Doctrine\Laminas\Hydrator\DoctrineObject as DoctrineHydrator;
use Rider\Fieldset\CardFieldset;

class CardEditForm extends Form
{
    /**
     * Constructor.     
     */
    public function __construct(EntityManager $entityManager)
    {
        // Define form name
        parent::__construct('edit-card-form');

        // Set POST method for this form
        $this->setAttribute('method', 'post');

        $this->setHydrator(new DoctrineHydrator($entityManager));

        $Card = (new CardFieldset("card", $entityManager))
            ->setUseAsBaseFieldset(true)
            ->setName('card');
        $this->add($Card);
        $this->addElements();
    }

    /**
     * This method adds elements to form (input fields and submit button).
     */
    protected function addElements()
    {
        // Add "csrf" field
        $this->add([
            'type'  => Csrf::class,
            'name' => 'csrf',
            'options' => [
                'csrf_options' => [
                    'timeout' => 600
                ]
            ],
        ]);

        // Add the Submit button
        $this->add([
            'type'  => Button::class,
            'name' => 'submit',
            'attributes' => [
                'class' => 'btn btn-primary',
                'type' => 'submit'
            ],
            'options' => [
                'label' => '<i class="bi bi-floppy"></i> save',
                'label_options' => [
                    'disable_html_escape' => true
                ]
            ]
        ]);
    }
}
