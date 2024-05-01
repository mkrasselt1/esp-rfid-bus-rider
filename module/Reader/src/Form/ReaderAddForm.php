<?php

namespace Reader\Form;

use Doctrine\ORM\EntityManager;
use Laminas\Form\Element\Button;
use Laminas\Form\Element\Csrf;
use Laminas\Form\Form;
use Doctrine\Laminas\Hydrator\DoctrineObject as DoctrineHydrator;
use Reader\Fieldset\ReaderFieldset;

class ReaderAddForm extends Form
{
    /**
     * Constructor.     
     */
    public function __construct(EntityManager $entityManager)
    {
        // Define form name
        parent::__construct('add-reader-form');

        // Set POST method for this form
        $this->setAttribute('method', 'post');

        $this->setHydrator(new DoctrineHydrator($entityManager));

        $reader = (new ReaderFieldset("reader", $entityManager))
            ->setUseAsBaseFieldset(true)
            ->setName('reader');
        $this->add($reader);
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
                'label' => '<i class="bi bi-floppy"></i><i class="bi bi-plus-circle"></i> Add',
                'label_options' => [
                    'disable_html_escape' => true
                ]
            ]
        ]);
    }
}
