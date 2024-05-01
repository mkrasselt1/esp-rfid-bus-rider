<?php

namespace Rider\Form;

use Laminas\Form\Element\Button;
use Laminas\Form\Element\Csrf;
use Laminas\Form\Element\Hidden;
use Laminas\Form\Form;

/**
 * This form is used to collect user's username, full name, password and status. The form 
 * can work in two scenarios - 'create' and 'update'. In 'create' scenario, user
 * enters password, in 'update' scenario he/she doesn't enter password.
 */

class DeleteForm extends Form
{
    /**
     * Constructor.     
     */
    public function __construct()
    {
        // Define form name
        parent::__construct('delete-form');

        // Set POST method for this form
        $this->setAttribute('method', 'post');

        $this->addElements();
    }

    /**
     * This method adds elements to form (input fields and submit button).
     */
    protected function addElements()
    {
        // Add "id" field
        $this->add([
            'type'  => Hidden::class,
            'name' => 'id',
        ]);

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
                'label' => '<i class="bi bi-trash"></i> delete',
                'label_options' => [
                    'disable_html_escape' => true
                ]
            ]
        ]);
    }
}
