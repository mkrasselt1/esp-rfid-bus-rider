<?php

namespace Rider\Fieldset;

use Doctrine\Laminas\Hydrator\DoctrineObject as DoctrineHydrator;
use Doctrine\ORM\EntityManager;
use Laminas\Filter\StringTrim;
use Laminas\Filter\StripNewlines;
use Laminas\Filter\StripTags;
use Laminas\Form\Element\Hidden;
use Laminas\Form\Element\Select;
use Laminas\Form\Element\Text;
use Laminas\Form\Fieldset;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\StringLength;
use Rider\Entity\Company;

class CompanyFieldset extends Fieldset implements InputFilterProviderInterface
{
    /**
     * Entity Manager
     * @var EntityManager
     */
    private $entityManager = null;

    public function __construct(string $name, EntityManager $entityManager)
    {
        parent::__construct($name);

        $this->entityManager = $entityManager;
        $this->setHydrator(new DoctrineHydrator($this->entityManager))
            ->setObject(new Company());

        $this->init();
    }


    public function init()
    {
        $this->add([
            'type' => Hidden::class,
            'name' => 'id',
        ]);

        $this->add([
            'type' => Text::class,
            'name' => 'name',
            'attributes' => [
                'class' => 'form-control',
                'title' => 'name of the company',
            ],
            'options' => [
                'label' => 'name'
            ]
        ]);
        $this->add([
            'type' => Select::class,
            'name' => 'status',
            'options' => [
                'label' => 'status',
                // 'empty_option' => Company::STATUS_LIST[Company::STATUS_UNINITIATED],
                'value_options' => Company::STATUS_LIST,
            ],
            'attributes' => [
                'readonly' => false,
                'class' => 'form-control'
            ]
        ]);
        
    }
    public function getInputFilterSpecification()
    {
        return [
            'id' => [
                'required' => false,
            ],
            'name' => [
                'required' => true,
                'filters'  => [
                    ['name' => StringTrim::class],
                    ['name' => StripTags::class],
                    ['name' => StripNewlines::class],
                ],
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 5,
                            'max' => 25
                        ],
                    ],
                ],
            ],
            'status' => [
                'required' => false,
            ],
        ];
    }
}
