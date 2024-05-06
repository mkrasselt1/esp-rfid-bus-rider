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
use Rider\Entity\Card;

class CardFieldset extends Fieldset implements InputFilterProviderInterface
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
            ->setObject(new Card());

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
                'title' => 'name of the card',
            ],
            'options' => [
                'label' => 'name'
            ]
        ]);

        $this->add([
            'type' => Text::class,
            'name' => 'number',
            'attributes' => [
                'class' => 'form-control',
                'title' => 'number of the card',
            ],
            'options' => [
                'label' => 'number of card'
            ]
        ]);

        $this->add([
            'type' => Text::class,
            'name' => 'UID',
            'attributes' => [
                'class' => 'form-control',
                'title' => 'uid of chip',
            ],
            'options' => [
                'label' => 'card UID'
            ]
        ]);

        $this->add([
            'type' => Select::class,
            'name' => 'status',
            'options' => [
                'label' => 'status',
                // 'empty_option' => Card::STATUS_LIST[Card::STATUS_UNINITIATED],
                'value_options' => Card::STATUS_LIST,
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
