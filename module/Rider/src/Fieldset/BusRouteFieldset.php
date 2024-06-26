<?php

namespace Rider\Fieldset;

use Doctrine\Laminas\Hydrator\DoctrineObject as DoctrineHydrator;
use Doctrine\ORM\EntityManager;
use DoctrineModule\Form\Element\ObjectSelect;
use Laminas\Filter\StringTrim;
use Laminas\Filter\StripNewlines;
use Laminas\Filter\StripTags;
use Laminas\Form\Element\Hidden;
use Laminas\Form\Element\Select;
use Laminas\Form\Element\Text;
use Laminas\Form\Fieldset;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\StringLength;
use Rider\Entity\BusRoute;
use Rider\Entity\Company;

class BusRouteFieldset extends Fieldset implements InputFilterProviderInterface
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
            ->setObject(new BusRoute());

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
                'title' => 'name of the BusRoute',
            ],
            'options' => [
                'label' => 'name'
            ]
        ]);

        $this->add([
            'type' => ObjectSelect::class,
            'attributes' => [
                'class' => 'form-control',
            ],
            'options' => [
                'label' => 'assign company',
                'object_manager' => $this->entityManager,
                'target_class'   => Company::class,
                //'property'       => 'id',
                'label_generator' => function (Company $company) {
                    return $company->getName();
                },
                'is_method'      => true,
                'find_method'    => [
                    'name'   => 'findBy',
                    'params' => [
                        'criteria' => [
                            'status' => Company::STATUS_ACTIVE,
                        ]
                    ],
                ],
                'display_empty_item' => true,
                'empty_item_label'   => '-no company assigned-',
            ],
            'name' => 'company'
        ]);
        
        $this->add([
            'type' => Select::class,
            'name' => 'status',
            'options' => [
                'label' => 'status',
                // 'empty_option' => BusRoute::STATUS_LIST[BusRoute::STATUS_UNINITIATED],
                'value_options' => BusRoute::STATUS_LIST,
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
